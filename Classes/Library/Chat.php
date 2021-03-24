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

class Chat
{

    /**
     * Is admin
     *
     * @var boolean $admin
     * @access public
     */
    public $admin;

    /**
     * Identification for user (FE-User Session OR BE-USER UID)
     *
     * @var int
     * @access public
     */
    public $identification = 0;

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
        $pid, $identification, $admin = 0, $useTypingIndicator = 0
    ) {
        $this->pid = intval($pid);
        $this->identification = $identification; // FE-User session id, or BE-User uid
        $this->admin = $admin;
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
        $this->db = $this->getChat();
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
     * @access public
     */
    public function hasUserRights()
    {
        if(!$this->admin) {
            if($this->db["session"] == $this->identification && $this->identification && $this->db["active"] && $this->uid) {
                return (1);
            }
            else {
                return (0);
            }
        }
        else {
            if ((!$this->db["be_user"] || $this->db["be_user"]==$this->identification) && $this->db["active"] && $this->uid) {
                return (1);
            }
            else {
                return (0);
            }
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
        }
        else {
            return ("no_access");
        }
    }

    /**
     * Get the chat from database
     *
     * @return array    The Chat
     */
    public function getChat()
    {
        global $TYPO3_DB;
        $tableChats = "tx_supportchat_chats";
        $res = $TYPO3_DB->exec_SELECTquery("*", $tableChats, 'uid=' . $this->uid);
        return ($TYPO3_DB->sql_fetch_assoc($res));
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
        $table = "tx_supportchat_chats";
        $insertData = [
            "pid" => $this->pid,
            "crdate" => time(),
            "session" => $this->identification,
            "active" => 1,
            "language_uid" => $feLanguageId,
            "surfer_ip" => ChatHelper::getIpAddressOfUser(),
            "be_user" => '',
            "status" => '',
            "assume_to_be_user" => ''
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

        $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $insertData);
        $chatPid = $GLOBALS['TYPO3_DB']->sql_insert_id();

        if($this->logging) {
            $this->writeLog("Chat ".$chatPid." was succesfully created!");
            $this->logTypingStatus($chatPid);
        }
        return ($chatPid);
    }

    /**
     * Get all messages with uid greater then $this->lastRow
     *
     * @param string $fields Default returns all fields from db
     *
     * @return array $data   A multidimensional messages array
     * @access public
     */
    public function getMessages($fields = "*")
    {
        global $TYPO3_DB;
        $table = "tx_supportchat_messages";
        $res = $TYPO3_DB->exec_SELECTquery(
            $fields.",uid",
            $table,
            'chat_pid='.$this->uid.' AND uid > '.$this->lastRow,"",
            "crdate"
        );
        $data = [];
        $fieldArray = explode(",",$fields);
        $i=0;
        while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
            $this->lastRow = $row["uid"];
            foreach ($fieldArray as $field) {
                if($field == "crdate") {
                    $data[$i][$field] = ChatHelper::renderTstamp($row[$field]);
                }
                else {
                    $data[$i][$field] = $row[$field];
                }
            }
            $i++;
        }
        return ($data);
    }

    /**
     * Insert a message in database
     *
     * @param string $message
     * @param string $code
     * @param string $name
     * @param string $fromSupporter
     * @param string $toSupporter
     *
     * @return int $messageId           Id of the newly created message
     * @access public
     */
    public function insertMessage(
        $message,
        $code,
        $name,
        $fromSupporter = "",
        $toSupporter = ""
    ) {
        global $TYPO3_DB;

        $message = htmlspecialchars($message);
        $message = ChatHelper::activateHtmlLinks($message);

        $insertData = [
            "crdate" => time(),
            "tstamp"=> time(),
            "pid" => $this->pid,
            "code" => $code,
            "from_supportler" => $fromSupporter,
            "to_supportler" => $toSupporter,
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

        $table = "tx_supportchat_messages";
        $TYPO3_DB->exec_INSERTquery($table,$insertData);
        $messageId = $TYPO3_DB->sql_insert_id();
        if ($messageId > $this->lastRow) {
            $this->lastRow = $messageId;
        }
        return $messageId;
    }

    /**
     * Destroys a chat (active=0)
     *
     * @return void
     * @access public
     */
    public function destroyChat()
    {
        global $TYPO3_DB,$BE_USER;
        $table = "tx_supportchat_chats";
        $updateArray = [
            "active" => 0,
        ];
        if ($BE_USER->user["uid"]) {
            $updateArray["status"] = "be_user_destroyed";
        }
        else {
            $updateArray["status"] = "fe_user_destroyed";
        }
        $TYPO3_DB->exec_UPDATEquery($table,'uid='.$this->uid,$updateArray);
        $this->db["active"] = 0;
        if ($this->logging) {
            $user = $this->admin ? ($BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"]) : "FE-User";
            $this->writeLog("Chat ".$this->uid." was succesfully destroyed by ".$user);
        }
    }

    /**
     * Write log message to DB
     *
     * @params string $msg
     *
     * @return boolean Return 1 for true
     * @access public
     */
    public function writeLog($msg)
    {
        global $TYPO3_DB;
        $insertData = [
            "crdate" => time(),
            "tstamp"=> time(),
            "pid" => $this->pid,
            "message" => $msg
        ];
        $table = "tx_supportchat_log";
        $TYPO3_DB->exec_INSERTquery($table,$insertData);
        return 1;
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
        global $TYPO3_DB,$BE_USER;
        $table = "tx_supportchat_chats";
        $updateArray = [
            "be_user" => $lock ? $BE_USER->user["uid"] : "",
        ];
        $TYPO3_DB->exec_UPDATEquery($table,'uid='.$this->uid,$updateArray);
        if ($this->logging) {
            $user = $this->admin ? ($BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"]) : "FE-User";
            $str = $lock ? "locked" : "unlocked";
            $this->writeLog("Chat ".$this->uid." was succesfully ".$str." for ".$user);
        }
        return ($lock);
    }

    /**
     * Assumes the chat to the be_user
     *
     * @param int $be_user  The be-user to assume the chat to
     *
     * @return void
     * @todo implement it
     */
    function assumeChatToUser($be_user)
    {
    }

    /**
     * Accept or decline the request to assume the chat
     *
     * @params boolean  Accept it or not
     *
     * @return void
     * @todo implement it
     */
    function acceptAssumeChat($accepted = 1)
    {
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
        global $BE_USER;
        $tableChats = "tx_supportchat_chats";
        $tableMessages = "tx_supportchat_messages";
        $res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,crdate',$tableChats,'active=1 AND deleted=0 AND hidden=0 AND pid='.$this->pid);
        while ($row=$GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            $limit = time() - ($inactivateTime*60);
            $messageRes = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid',$tableMessages,'chat_pid='.$row["uid"].' AND crdate > '.$limit);
            $messageRow = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($messageRes);
            if (!$messageRow["uid"] && $row["crdate"] < $limit) {
                // delete the Chat
                $GLOBALS["TYPO3_DB"]->exec_UPDATEquery(
                    $tableChats,
                    "uid=".$row["uid"],
                    ["active" => "0", "status" => "timeout"]
                );
                if ($this->logging) {
                    $user = $BE_USER->user["uid"]
                        ? ($BE_USER->user["realName"]
                            ? $BE_USER->user["realName"] : $BE_USER->user["username"])
                        : "FE-User";
                    $this->writeLog("Chat ".$row["uid"]." was succesfully destroyed by System, timeout exceeded");
                }
            }
        }
    }

    /**
     * Save info to database if user is currently typing if usage of typing indicator is set.
     * Behaviour of this method is controlled by state of <code>useTypingIndicator</code>.
     *
     * @params boolean $isTyping True if current end is typing, false if it's not
     *
     * @return boolean           True or false if useTypingIndicator is not set to true (1).
     * @access public
     * @see #useTypingIndicator
     * @see #initChat($pid, $ident, $admin=0, $useTypingIndicator)
     */
    public function saveTypingStatus($isTyping)
    {
        global $BE_USER;
        /** tradem 2012-04-11 Added check of control variable. */
        if ($this->useTypingIndicator == 1) {
            if($this->db['uid']) {
                $status_array = unserialize($this->db['status']);
                if(!is_array($status_array)) {
                    $status_array = ['feu_typing' => 0, 'beu_typing' => 0];
                }
                if($BE_USER->user["uid"]) {
                    //current user is a backend-user and typing?
                    if($isTyping == 1) {
                        $status_array['beu_typing'] = 1;
                    } else {
                        $status_array['beu_typing'] = 0;
                    }
                } else {
                    //current user is a frontend-user and typing?
                    if ($isTyping == 1) {
                        $status_array['feu_typing'] = 1;
                    } else {
                        $status_array['feu_typing'] = 0;
                    }
                }
                $updateArray = ['status' => serialize($status_array)];
                if ($this->db['status'] != $updateArray['status']) {
                    $tableChats = "tx_supportchat_chats";
                    $GLOBALS["TYPO3_DB"]->exec_UPDATEquery($tableChats,"uid=".$this->uid,$updateArray);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve typing status of opposite chat-partner.
     * Behavoir of this method is controlled by state of <code>useTypingIndicator</code>.
     *
     * @return boolean True if other end is typing, false if it's not or
     *                 if useTypingIndicator is not set to true (1).
     * @access public
     * @see #useTypingIndicator
     * @see #initChat($pid,$ident,$admin=0,$useTypingIndicator)
     */
    public function getTypingStatus()
    {
        global $BE_USER;

        /** tradem 2012-04-11 Added check of control variable. */
        if ($this->useTypingIndicator == 1) {
            if ($this->db['uid'] && $this->db['active']) {
                $status_array = unserialize($this->db['status']);
                if (!is_array($status_array)) {
                    $status_array = ['feu_typing'=>0, 'beu_typing'=>0];
                }
                if ($BE_USER->user["uid"]) {
                    //current user is a backend-user and frontend-user is typing?
                    if ($status_array['feu_typing'] == 1) {
                        return 1;
                    } else {
                        return 0;
                    }
                } else {
                    //current user is frontend-user and backend-user is typing?
                    if ($status_array['beu_typing'] == 1) {
                        return 1;
                    } else {
                        return 0;
                    }
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
}
