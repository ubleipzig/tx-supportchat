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
use \TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        if (!$this->objectManager) {
            $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        }
    }

    /**
     * Clean up and removes user from frontend user table to prevent collecting an amount of trusted data
     *
     * @param int $days 	How long user in fe_users should be kept. Default is 7 days.
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
            sleep(3);
        }
        $this->outputLine('%d chats removed before %s', [$cnt, $ts->format('d.m.Y H:i:s')]);
    }

    /**
     * Clean up and removes user from frontend user table to prevent collecting an amount of trusted data
     *
     * @param int $days 	How long user in fe_users should be kept. Default is 30 days.
     *
     * @return void
     */
    public function cleanupChatLogsCommand($days = 30)
    {
        $dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
        $current = $dt->modify('midnight');
        $ts = $current->sub(new \DateInterval("P{$days}D"));
        $cnt = $this->removeChatLogsBeforeTime($ts);
        $this->outputLine('%d chats removed before %s', [$cnt, $ts->format('d.m.Y H:i:s')]);
    }


    /**
     * Get connection for table
     *
     * @param string $tbl	Table name
     *
     * @return TYPO3\CMS\Core\Database\Connection
     * @access protected
     */
    protected function getConnectionForTable($tbl)
    {
        /** @var ConnectionPool $connectionPool */
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tbl);
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
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_chats');
            return $queryBuilder
                ->select('tx_supportchat_chats.uid')
                ->from('tx_supportchat_chats')
                ->rightJoin(
                    'tx_supportchat_chats',
                    'tx_supportchat_messages',
                    'm',
                    $queryBuilder->expr()->eq('m.chat_pid', $queryBuilder->quoteIdentifier('tx_supportchat_chats.uid'))
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_supportchat_chats.active',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->lt(
                        'tx_supportchat_chats.crdate',
                        $time->getTimestamp()
                    )
                )
                ->groupBy('tx_supportchat_chats.uid')
                ->execute()
                ->fetchAll();
        } catch (\Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }

    /**
     * Remove chat messages by given uids
     *
     * @param array $uids	Uids to remove
     *
     * @return int 	Affected row to proceed.
     * @access public
     */
    public function removeChatMessagesByIds(array $uids)
    {
        try {
            $deleteList = implode(
                ', ',
                array_map(function ($item) {
                    return "'" . $item . "'";
                },
                    $uids)
            );
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_messages');
            return $queryBuilder
                ->delete('tx_supportchat_messages')
                ->where(
                    $queryBuilder->expr()->in('chat_pid', $deleteList)
                )
                ->execute();
        } catch (Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }

    /**
     * Remove chat logs before a specified time
     *
     * @param \DateTimeInterface $time Timestamp
     *
     * @return int 	Affected row to proceed.
     * @access public
     */
    public function removeChatLogsBeforeTime(\DateTimeInterface $time)
    {
        try {
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_log');
            return $queryBuilder
                ->delete('tx_supportchat_log')
                ->where(
                    $queryBuilder->expr()->lt(
                        'tstamp',
                        $time->getTimestamp()
                    )
                )
                ->execute();
        } catch (Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }
}