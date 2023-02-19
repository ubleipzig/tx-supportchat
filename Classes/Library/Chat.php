<?php
/**
 * Class Chat
 *
 * Copyright (C) Leipzig University Library 2020 <info@ub.uni-leipzig.de>
 *
 * @author  Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class Chat extends ChatAbstract
{
    /**
     * Identification for user (FE-User Session OR BE-USER UID)
     *
     * @var int
     * @access public
     */
    public $identification = 0;

    /**
     * Is admin
     *
     * @var boolean $isAdmin
     * @access public
     */
    public $isAdmin;

    /**
     * If admin then other access check user id (BE-USER)
     *
     * @var int $uid
     * @accces public
     */
    public $uid = 0;

    /**
     * Chat process id
     *
     * @var int $pid
     * @access public
     */
    public $pid = 0;

    /**
     * Identifier of the last row. The getMessage() function will only get messages
     * with an uid greater than this var!
     *
     * @var int
     * @access public
     */
     public $lastRow = 0;

    /**
     * Added to control typing indiator controls if typing indicator should show
     * up or not, defaults to false (0)
     *
     * @var boolean $useTypingIndicator
     * @access public
     */
    public $useTypingIndicator = 0;

    /**
     * Insert log message at database. Default true.
     *
     * @var boolean $logging
     * @access public
     */
    public $logging = 1;

    /**
     * Array for the chat at database
     *
     * @var array $db
     * @access protected
     */
    protected $db = [];

    /**
     * Initialize data needed for every chat object
     *
     * @params int $pid                     Process id of single chat
     * @params int $identification          User id from session or BE user id
     * @params boolean $admin               Is admin, default false
     * @params boolean $useTypingIndicator  Typing indicator
     *
     * @access public
     */
    public function initChat(
        $pid,
        $identification,
        $isAdmin = false,
        $useTypingIndicator = 0
    ) {
        $this->pid = (int)$pid;
        $this->identification = $identification; // FE-User session id, or BE-User uid
        $this->isAdmin = $isAdmin;
        $this->useTypingIndicator = $useTypingIndicator;
    }

    /**
     * Get the chat from database and load some needed data
     *
     * @params int $uid     User id
     * @params int $lastRow Last row of single chat
     *
     * @access public
     */
    public function loadChatFromDB($uid, $lastRow)
    {
        $this->uid = $uid;
        $this->lastRow = $lastRow;
        $this->db = $this->chatsRepository->findChatByUid($uid);
    }

    /**
     * Get the chat from @param1 and load some needed data
     *
     * @params $dbChat
     * @params $lastRow     Last row of single chat
     *
     * @access public
     */
    public function loadChatFromData($dbChat, $lastRow)
    {
        $this->uid = $dbChat["uid"];
        $this->lastRow = $lastRow;
        $this->db = $dbChat;
    }

    /**
     * Check if the current user has permission to given chat
     *
     * @return boolean
     * @access public
     */
    public function hasUserRights()
    {
        if (!$this->isAdmin) {
            return (
                $this->db['session'] == $this->identification
                && $this->identification
                && $this->db['active']
                && $this->uid
            ) ? true : false;
        } else {
            return (
                (!$this->db['be_user'] || $this->db['be_user'] == $this->identification)
                && $this->db['active']
                && $this->uid
            ) ? true : false;
        }
    }

    /**
     * Returns the current chat status
     *
     * @return string be_user_destroyed|fe_user_destroyed|timeout|no_access
     * @access public
     */
    public function chatStatus()
    {
        if ($this->db["status"]) {
            return ($this->db["status"]);
        } else {
            return ("no_access");
        }
    }

    /**
     * Creates the chat and store it at database
     *
     * @params int $feLanguageId     Frontend language id
     *
     * @return int $chatPid Returns newly created chat pid
     * @access public
     */
    public function createChat($feLanguageId)
    {
        $insertData = [
            "pid" => $this->pid,
            "crdate" => time(),
            "session" => $this->identification,
            "active" => 1,
            "language_uid" => $feLanguageId,
            "surfer_ip" => ChatHelper::getIpAddressOfUser(),
            "be_user" => '',
            "status" => '',
            "type_status" => "{}"
        ];
        // hook for manipulating the db entry of insert data
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/Chat.php']['overwriteCreateChat'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/Chat.php']['overwriteCreateChat'] as $_funcRef) {
                $_params = [
                    'insertData' => $insertData
                ];
                $insertData = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }
        $chatPid = $this->addChat($insertData);

        if ($this->logging) {
            $this->writeLog("Chat " . $chatPid . " was successfully created");
            $this->logTypingStatus($chatPid);
        }
        return ($chatPid);
    }

    /**
     * Get all messages with uid greater then $this->lastRow
     *
     * @return array $data   A multidimensional messages array
     * @access public
     */
    public function getMessages()
    {
        $resMessages = $this->messagesRepository->findMessagesByUidAndLastRow($this->uid, $this->lastRow);
        $data = [];
        $i = 0;
        foreach ($resMessages as $row) {
            $this->lastRow = $row->getUid();
            $data[$i]['code'] = $row->getCode();
            $data[$i]['name'] = $row->getName();
            $data[$i]['message'] = $row->getMessage();
            $data[$i]['crdate'] = ChatHelper::renderTstamp($row->getCrdate());
            $data[$i]['uid'] = $row->getUid();
            $i++;
        }
        return $data;
    }

    /**
     * Insert a message in database
     *
     * @param string $message
     * @param string $code
     * @param string $name
     *
     * @return int $messageId           Id of the newly created message
     * @access public
     */
    public function insertMessage(string $message, string $code, string $name)
    {
        $message = htmlspecialchars($message);
        $message = ChatHelper::activateHtmlLinks($message);

        $insertData = [
            "crdate" => time(),
            "tstamp"=> time(),
            "pid" => $this->pid,
            "code" => $code,
            "chat_pid" => $this->uid,
            "name" => $name,
            "message" => $message
        ];

        // Hook for post processing of posted message
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/Chat.php']['postProcessPostedMessage'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/Chat.php']['postProcessPostedMessage'] as $_funcRef) {
                $_params = [
                  'insertData' => $insertData
                ];
                $insertData = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        $messageId = $this->addMessage($insertData);
        // If messageId greater than lastRow make it lastRow seems to be true every time?
        if ($messageId > $this->lastRow) {
            $this->lastRow = $messageId;
        }
        return $messageId;
    }

    /**
     * Destroy a chat (active=0)
     *
     * @return void
     * @access public
     */
    public function destroyChat()
    {
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $destroy = $this->chatsRepository->findByUid($this->uid);
        $destroy->setActive(0);
        if ($this->getBackendUserUid()) {
            $destroy->setStatus("be_user_destroyed");
        } else {
            $destroy->setStatus("fe_user_destroyed");
        }
        $this->chatsRepository->update($destroy);
        $persistenceManager->persistAll();

        $this->db["active"] = 0;
        if ($this->logging) {
            $user = $this->isAdmin
                ? $this->getBackendUsername() : "Frontend-User";
            $this->writeLog("Chat ". $this->uid . " was succesfully destroyed by ". $user);
        }
    }

    /**
     * Locks / unlocks the chat for current BE-User
     *
     * @params boolean $lock     Locks or unlocks the chat; default lock chat
     *
     * @return boolean  Returns 1 for chat locked, 0 for chat not locked
     * @access boolean
     */
    public function lockChat($lock = 1)
    {
        $persistenceManager = $this->objectManager->get(PersistenceManager::class);
        $locked = $this->chatsRepository->findByUid($this->uid);
        $locked->setBackendUser(
            ($lock) ? $this->getBackendUserUid() : ""
        );
        $this->chatsRepository->update($locked);
        $persistenceManager->persistAll();

        if ($this->logging) {
            $user = $this->isAdmin
                ? $this->getBackendUsername() : "Frontend-User";
            $str = $lock ? "locked" : "unlocked";
            $this->writeLog("Chat " . $this->uid . " was succesfully " . $str . " for ". $user);
        }
        return ($lock);
    }

    /**
     * Destroys all chats that are inactive - this happens if no messages is been
     * send for a given time defined at typoscript
     *
     * @params int $inactivateTime
     *
     * @return void
     * @access public
     */
    public function destroyInactiveChats($inactivateTime)
    {
        $res = $this->chatsRepository->findActiveChatsByPid($this->pid);
        $limit = time() - ($inactivateTime * 60);
        foreach ($res as $row) {
            $message = $this->messagesRepository->findMessagesWithinPeriod($row->getUid(), $limit);
            if (!$message && $row->getCrdate() < $limit) {
                $persistenceManager = $this->objectManager->get(PersistenceManager::class);
                $destroy = $this->chatsRepository->findByUid($row->getUid());
                $destroy->setActive(0);
                $destroy->setStatus("timeout");
                $this->chatsRepository->update($destroy);
                $persistenceManager->persistAll();
                if ($this->logging) {
                    $user = $this->getBackendUserUid()
                        ? $this->getBackendUsername() : "Frontend-User";
                    $this->writeLog(
                        "Chat " . $row->getUid() . " of " . $user . " was succesfully destroyed by System, timeout exceeded"
                    );
                }
            }
        }
    }

    /**
     * Save info to database if user is currently typing if usage of typing indicator is set.
     * Behaviour of this method is controlled by state of <code>useTypingIndicator</code>.
     *
     * @params boolean $isTyping True if current end is typing, false if it's not
     * @param $isTyping
     *
     * @return boolean  True or false if useTypingIndicator is not set to true (1).
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     * @access public
     * @see #useTypingIndicator
     * @see #initChat($pid, $ident, $admin=0, $useTypingIndicator)
     */
    public function saveTypingStatus($isTyping)
    {
        /** tradem 2012-04-11 Added check of control variable. */
        if ($this->useTypingIndicator == 1) {
            if ($this->uid) {
                $chat = $this->chatsRepository->findByUid($this->uid);
                $status_array = json_decode($chat->getTypeStatus(), true);
                if (!is_array($status_array)) {
                    $status_array = ['feu_typing' => 0, 'beu_typing' => 0];
                }
                if ($this->getBackendUserUid()) {
                    //current user is a backend-user and typing?
                    $status_array['beu_typing'] = ($isTyping == 1) ? 1 : 0;
                } else {
                    //current user is a frontend-user and typing?
                    $status_array['feu_typing'] = ($isTyping == 1) ? 1 : 0;
                }
                $updateArray = ['type_status' => json_encode($status_array)];
                if ($updateArray['type_status'] != $this->db['type_status']) {
                    $persistenceManager = $this->objectManager->get(PersistenceManager::class);
                    $save = $this->chatsRepository->findByUid($this->uid);
                    $save->setTypeStatus($status_array);
                    $this->chatsRepository->update($save);
                    $persistenceManager->persistAll();
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Retrieve typing status of opposite chat-partner.
     * Behavoir of this method is controlled by state of <code>useTypingIndicator</code>.
     *
     * @return mixed True if other end is typing, false if it's not or
     *               if useTypingIndicator is not set to true (1).
     * @access public
     * @see #useTypingIndicator
     * @see #initChat($pid,$ident,$admin=0,$useTypingIndicator)
     */
    public function getTypingStatus()
    {
        if (($this->useTypingIndicator == 1) && $this->uid) {
            $chat = $this->chatsRepository->findByUid($this->uid);
            if ($chat->getActive() === 1) {
                $status_array = json_decode($chat->getTypeStatus(), true);
                if (!is_array($status_array)) {
                    $status_array = ['feu_typing' => 0, 'beu_typing' => 0];
                }
                if ($this->getBackendUserUid()) {
                    //current user is a backend-user and frontend-user is typing?
                    return ($status_array['feu_typing'] == 1) ? 1 : 0;
                } else {
                    //current user is frontend-user and backend-user is typing?
                    return ($status_array['beu_typing'] == 1) ? 1 : 0;
                }
            }
        }
        return false;
    }

    /**
     * Creates a log entry if typing status indicator has been deactivated.
     *
     * @params int $chatId
     *
     * @access public
     */
    public function logTypingStatus($chatId)
    {
        if ($this->useTypingIndicator != 1) {
            $this->writeLog(
                "Info: Chat ".$chatId." has been configured without typing indicator!"
            );
        }
    }

    /**
     * Write log message to database
     *
     * @params string $msg
     *
     * @return boolean Return true
     * @access public
     */
    public function writeLog($msg)
    {
        $dbLogs = $this->getConnectionForLogs();
        $dbLogs->insert(
            ChatAbstract::TABLE_LOGS,
            [
                "crdate" => time(),
                "tstamp"=> time(),
                "pid" => $this->pid,
                "message" => $msg
            ]
        );
        return true;
    }

    /**
     * Creates new chat room to table tx_supportchat_domain_model_chats
     *
     * @param array $insertData New dataset to insert.
     *
     * @return int  Last insert id return chat pid
     * @access protected
     */
    protected function addChat(array $insertData)
    {
        $dbChats = $this->getConnectionForChats();
        $dbChats->insert(
            ChatAbstract::TABLE_CHATS,
            $insertData
        );
        return (int)$dbChats->lastInsertId(Chat::TABLE_CHATS);
    }

    /**
     * Adds message to table tx_supportchat_domain_model_messages
     *
     * @param array $insertData New dataset to insert.
     *
     * @return int  Last insert id
     * @access protected
     */
    protected function addMessage(array $insertData)
    {
        $dbChats = $this->getConnectionForMessages();
        $dbChats->insert(
            ChatAbstract::TABLE_MESSAGES,
            $insertData
        );
        return (int)$dbChats->lastInsertId(Chat::TABLE_MESSAGES);
    }
}
