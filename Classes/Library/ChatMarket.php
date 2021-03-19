<?php

/**
 * Class ChatMarket - Backend frontend
 *
 * Copyright (C) Leipzig University Library 2020 <info@ub.uni-leipzig.de>
 *
 * @author  Ulf Seltmann <seltmann@ub.uni-leipzig.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChatMarket extends Chat {

    /**
     * Should session be logged
     *
     * @var boolean $logging    Default false
     * @access public
     */
	public $logging = 0;

    /**
     * Last logged row
     *
     * @var int $lastLogRow
     * @access public
     */
	public $lastLogRow = 0;

    /**
     * Path to default language floag
     *
     * @var string $defaultLImg
     * @access public
     * @deprecated Implementation not works w/ typo3 v7 onwards
     */
	public $defaultLImg = "typo3/gfx/flags/gb.gif";

    /**
     * Default language label
     *
     * @var string $defaultLLabel Default. "english"
     * @access public
     */
	public $defaultLLabel = "english";

    /**
     * Backend user name
     *
     * @var string $beUserName
     * @access public
     */
	public $beUserName = "Supportler";
	
	/**
	 * Constructor initializes variables
     *
	 */
	public function __construct()
    {
		global $BE_USER;
		$this->logging = func_get_arg(0);
		$this->lastLogRow = intval(func_get_arg(1));
		$this->beUserName = $BE_USER->user["realName"]
            ? $BE_USER->user["realName"] : $BE_USER->user["username"];
		if ($BE_USER->userTS["supportchat."]["defLangImg"]) {
			$this->defaultLImg = $BE_USER->userTS["supportchat."]["defLangImg"];
		}
		if ($BE_USER->userTS["supportchat."]["defLangLabel"]) {
			$this->defaultLLabel = $BE_USER->userTS["supportchat."]["defLangLabel"];
		}
	}
	
	/**
	 * Get all chats and messages and return it as an array, store msg's to DB if any,
     * lock/unlock a chat or destroy one
	 *
     * @param array $lastRowArray   The last rows
	 * @param array $msgToSend      Messages to save in DB
	 * @param array $lockChats      Chats which should be locked/unlocked
	 * @param array $destroyChats   Chats which should be destroyed
     * @param boolean $typingStatus
     *
	 * @return array $retArray  Chats with data
     * @access public
	 */
	public function doAll(
	    $lastRowArray,
        $msgToSend,
        $lockChats,
        $destroyChats,
        $typingStatus
    ) {
        global $TYPO3_DB;
		/* get the language Info */
		//$language = $this->getLanguageInfo();
		/* get all chats */
		$retArray = [];
        $tableChats = "tx_supportchat_chats";
        $res = $TYPO3_DB->exec_SELECTquery("*",$tableChats,'active=1 AND pid='.$this->pid,"","crdate desc");
		$i=0;
		// additional info that is shown after the language info in the chatbox  
		$hookObjectsArr = [];
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['supportchat/additionalInfo'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['supportchat/additionalInfo'] as $classRef) {
				$hookObjectsArr[] = &GeneralUtility::getUserObj($classRef);
			}
		}
		while ($row=$TYPO3_DB->sql_fetch_assoc($res)) {
			$lastRow = intval($lastRowArray[$row["uid"]]) ? intval($lastRowArray[$row["uid"]]) : 0;
			$this->loadChatFromData($row,$lastRow);
			if ($this->hasUserRights()) {
				// get All Messages
                $messages = $this->getMessages("crdate,code,from_supportler,to_supportler,name,message");
				// send Messages
				if ($msgToSend[$this->uid]) {
					foreach ($msgToSend[$this->uid] as $msg) {
	                    $this->insertMessage($msg,"beuser",$this->beUserName);
					}	
				}

				// adds localization and default handling
                $localization = new LocalizationHelper();
                $languageUid = ($this->db["language_uid"] != 0) ? $languageUid : 1;

				// added for typingStatus
				$tmp_status = unserialize($this->db['status']);
				$tmp_status = $tmp_status['feu_typing'];

				$retArray[$i]["chatIndex"]["uid"] = $this->uid;
				$retArray[$i]["chatIndex"]["lastRow"] = $this->lastRow;
				$retArray[$i]["chatIndex"]["crdate"] =
                    ChatHelper::renderTstamp($this->db["crdate"]);
				$retArray[$i]["chatIndex"]["fe_language"] = $this->db["language_uid"];
				$retArray[$i]["chatIndex"]["surfer_ip"] = $this->db["surfer_ip"];
				$retArray[$i]["chatIndex"]["be_user"] = $this->db["be_user"];
				$retArray[$i]["chatIndex"]["language_flag"] =
                    $localization->getRenderedFlagIconByLanguageUid($languageUid);
				$retArray[$i]["chatIndex"]["language_label"] =
                    $localization->getLabelByLanguageUid($languageUid);
				$retArray[$i]["chatIndex"]["messages"] = $messages;
				/*added for typingStatus*/
				$retArray[$i]["chatIndex"]["status"] = ($tmp_status == 1 ? 1 : 0);
                // lock chat ?
                if (isset($lockChats[$this->uid])) {
                    $this->lockChat(intval($lockChats[$this->uid]));
					$retArray[$i]["chatIndex"]["from_lock_chat"] = intval($lockChats[$this->uid]);
                }
				// destroy chat ?
				if (intval($destroyChats[$this->uid])) {
					$this->destroyChat();
					$retArray[$i]["chatIndex"]["from_destroy_chat"] = 1;
				}
				/*added for typingStatus*/
				// set typing status
                if (isset($typingStatus[$this->uid])) {
                    $this->saveTypingStatus(intval($typingStatus[$this->uid]));
                }
				
				foreach ($hookObjectsArr as $hookObj)    {
					if (method_exists($hookObj, 'additionalInfo')) {
						/* only one extension can use this hook for now! */
						$retArray[$i]["chatIndex"]["additionalInfo"] = $hookObj->additionalInfo($this);
					}
				}
				$i++;
			}
		}
		return ($retArray);
	}

	/**
     * Get backend users
     *
     * @return array $beUserArray   List of backend users
     * @access public
     */
	public function getBeUsers()
    {
		global $TYPO3_DB, $BE_USER;
		// get SelectBox with all be_user
		$table="be_sessions";
		$res = $TYPO3_DB->exec_SELECTquery("ses_userid", $table, '1');
		$inList="";
		$beUserArray = [];
		while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$inList .= ",'".$row["ses_userid"]."'";
		}
		$inList = substr($inList,1);
		if ($inList) {
			$table = "be_users";
			$options="";
			$res = $TYPO3_DB->exec_SELECTquery("uid,username,realName",$table,'deleted=0 AND disable=0 AND uid IN ('.$inList.') AND uid<>'.$BE_USER->user["uid"]);
			$i=0;
			while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$name = $row["realName"] ? $row["realName"] : $row["username"];
				$beUserArray[$i] = [
					"uid" => $row["uid"],
					"name" => $name,
				];
				$i++;
			}
		}
		return ($beUserArray);
	}

	
	/**
	 * Get all log messages which are younger than now - 10min, takes care of
     * the lastLogRow var
     *
	 * @return array $retArray The log rows
     * @access public
	 */
	public function getLogMessages()
    {
		global $TYPO3_DB;
		if ($this->logging) {
			$retArray = [];
			$tableLog = "tx_supportchat_log";
			if (!$this->lastLogRow) {
				$limit = '5';
			}
			else {
				$limit = "";
			}
			$notOlderThan = time()-600;
			$res = $TYPO3_DB->exec_SELECTquery(
			    "uid,
			    crdate,
			    message",
                $tableLog,
                'pid='.$this->pid.' AND crdate > '.$notOlderThan.' AND uid > '.$this->lastLogRow,
                "",
                "uid"
            );
			$i=0;
			while ($row=$TYPO3_DB->sql_fetch_assoc($res)) {
				$this->lastLogRow = $row["uid"];
				$retArray[$i]["crdate"] = ChatHelper::renderTstamp($row["crdate"]);
				$retArray[$i]["message"] = $row["message"];
				$i++;
			}
			return ($retArray);
		}
	}
}
