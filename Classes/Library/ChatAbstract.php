<?php
/**
* Class ChatAbstract
*
* Copyright (C) Leipzig University Library 2022 <info@ub.uni-leipzig.de>
*
* @author  Frank Morgner <morgner@ub.uni-leipzig.de>
*
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

namespace Ubl\Supportchat\Library;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Ubl\Supportchat\Domain\Repository\ChatsRepository;
use Ubl\Supportchat\Domain\Repository\LogsRepository;
use Ubl\Supportchat\Domain\Repository\MessagesRepository;

abstract class ChatAbstract
{
    /**
     * Constant for table tx_supportchat_domain_model_chats
     *
     * @var string
     */
    const TABLE_CHATS = 'tx_supportchat_domain_model_chats';

    /**
     * Constant for table tx_supportchat_domain_model_messages
     *
     * @var string
     */
    const TABLE_MESSAGES = 'tx_supportchat_domain_model_messages';

    /**
     * Constant for table tx_supportchat_domain_model_logs
     *
     * @var string
     */
    const TABLE_LOGS = 'tx_supportchat_domain_model_logs';

    /**
     * chatsRepository
     *
     * @var \Ubl\Supportchat\Domain\Repository\ChatsRepository
     * @Exbase\Inject
     */
    protected $chatsRepository;

    /**
     * logsRepository
     *
     * @var \Ubl\Supportchat\Domain\Repository\LogsRepository
     * @Exbase\Inject
     */
    protected $logsRepository;

    /**
     * messagesRepository
     *
     * @var \Ubl\Supportchat\Domain\Repository\MessagesRepository
     * @Exbase\Inject
     */
    protected $messagesRepository;

    /**
     * Database connection handle for table chats
     *
     * @var object $dbConnectionInstanceForChats
     * @access protected
     */
    protected $dbConnectionInstanceForChats;

    /**
     * Database connection handle for table messages
     *
     * @var object $dbConnectionInstanceForMessages
     * @access protected
     */
    protected $dbConnectionInstanceForMessages;

    /**
     * Database connection handle for table logs
     *
     * @var object $dbConnectionInstanceForLogs
     * @access protected
     */
    protected $dbConnectionInstanceForLogs;

    /**
     * Object Manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * Constructor initialize repositories
     *
     * @return void
     * @access public
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->chatsRepository = $this->objectManager->get(ChatsRepository::class);
        $this->logsRepository = $this->objectManager->get(LogsRepository::class);
        $this->messagesRepository = $this->objectManager->get(MessagesRepository::class);
    }

    /**
     * Get backend user t3 global
     *
     * @param string $key   Identifier for value to return of user obejct
     *
     * @return mixed
     * @access protected
     */
    protected function getBackendUser(string $key)
    {
        if (empty($key) && !is_string($key)) {
            throw new \InvalidArgumentException(
                'Parameter $key of '. __METHOD__ .' has to be set'
            );
        }
        return $GLOBALS['BE_USER']->user[$key] ?? null;
    }

    /**
     * Get backend user username from t3 global
     *
     * @return string    Returns name of backend user
     * @access protected
     */
    protected function getBackendUsername()
    {
        return (string)($this->getBackendUser("realName"))
            ?: $this->getBackendUser("username");
    }

    /**
     * Get backend user uid from t3 global
     *
     * @return int|null    Returns uid of user as integer, null if not exists.
     * @access protected
     */
    protected function getBackendUserUid()
    {
        return (int)$GLOBALS['BE_USER']->user["uid"] ?: null;
    }

    /**
     * Get connection for tables tx_supportchat_domain_model_chats
     *
     * @return object
     * @access protected
     */
    protected function getConnectionForChats()
    {
        return ($this->dbConnectionInstanceForChats)
            ?: GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_CHATS);
    }

    /**
     * Get connection for tx_supportchat_domain_model_logs
     *
     * @return object
     * @access protected
     */
    protected function getConnectionForLogs()
    {
        return ($this->dbConnectionInstanceForLogs)
            ?: GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_LOGS);
    }

    /**
     * Get connection for tx_supportchat_domain_model_messages
     *
     * @return object
     * @access protected
     */
    protected function getConnectionForMessages()
    {
        return ($this->dbConnectionInstanceForMessages)
            ?: GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_MESSAGES);
    }
}