<?php
/**
 * Class CleanupCommandController
 *
 * Copyright (C) Leipzig University Library 2022 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Ubl\Supportchat\Command;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class ChatCleanupCommandController
 *
 * Provides commandline interface to cleanup past bookings
 *
 * @package Ubl\Booking\Command
 */
class CleanupCommandController extends CommandController
{
    /**
     * The typo3 db connection
     *
     * @var \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected $db;

    /**
     * The object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * Size of chunk for large scales sql queries
     *
     * @var int sqlOperatingChunksize
     * @access protected
     */
    protected $sqlOperatingChunksize = 250;

    /**
     * Initializes the repository.
     *
     * @return void
     * @see \TYPO3\CMS\Extbase\Persistence\Repository::initializeObject()
     */
    public function initializeObject()
    {
        $this->db = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Clean up and removes user from frontend user table to prevent collecting an amount of trusted data
     *
     * @param int $days 	How long user in fe_users should be kept. Default is 60 days.
     *
     * @return void
     */
    public function cleanupChatMessagesCommand($days = 7)
    {
        $dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
        $current = $dt->modify('midnight');
        $ts = $current->sub(new \DateInterval("P{$days}D"));
        $chatList = $this->findChatsBeforeTime($ts);
        $uids = array_chunk(
            array_column($chatList, 'uid'),
            $this->sqlOperatingChunksize
        );
        $cnt = 0;
        foreach ($uids as $package) {
            $cnt += $this->removeChatMessagesByIds($package);
            sleep(5);
        }
        $this->outputLine('%d chats removed before %s', [$cnt, $ts->format('d.m.Y H:i:s')]);
    }

    /**
     * Finds chats before a specified time
     *
     * @param \DateTimeInterface $time Time
     *
     * @return array  	Return associative array with result.
     * @access public
     */
    public function findChatsBeforeTime(\DateTimeInterface $time)
    {
        try {
            $res = $this->db->exec_SELECTquery(
                'DISTINCT(tx_supportchat_chats.uid)',
                'tx_supportchat_chats RIGHT JOIN tx_supportchat_messages ON tx_supportchat_chats.uid = tx_supportchat_messages.chat_pid',
                'tx_supportchat_chats.active = 0 AND tx_supportchat_chats.crdate < ' . $time->getTimestamp()
            );
            $results = [];
            while ($arr =  $this->db->sql_fetch_assoc($res)) {
                $results[] = $arr;
            }
            return $results;
        } catch (Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
        }
    }

    /**
     * Remove chat messages before a specified time
     *
     * @param array $uids	Uids to remove
     *
     * @return int 	Affected row to proceed.
     * @access public
     */
    public function removeChatMessagesByIds(array $uids)
    {
        $deleteList = implode(
            ', ',
            array_map(function ($item) {
                return $this->db->fullQuoteStr($item, 'tx_supportchat_messages');
            },
                $uids)
        );
        try {
            $this->db->exec_DELETEquery(
                'tx_supportchat_messages',
                sprintf('chat_pid IN(%s)', $deleteList)
            );
            return $this->db->sql_affected_rows();
        } catch (Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with SQL error:' . $this->db->sql_error();
        }


    }
}