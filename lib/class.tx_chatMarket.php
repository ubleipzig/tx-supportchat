<?php
/**
* Class for Handling all chats, only available in BE
*/

require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chat.php');

class chatMarket extends chat {
	var $logging = 0;
	var $lastLogRow = 0;
	var $defaultLImg = "typo3/gfx/flags/gb.gif";
	var $defaultLLabel = "english";
	var $beUserName = "Supportler";
	
	/**
	* Constructor --> just initialize variables
	*/
	function chatMarket() {
		global $BE_USER;
		$this->logging = func_get_arg(0);
		$this->lastLogRow = intval(func_get_arg(1));
		$this->beUserName = $BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"];
		if($BE_USER->userTS["sni_supportchat."]["defLangImg"]) {
			$this->defaultLImg = $BE_USER->userTS["sni_supportchat."]["defLangImg"];
		}
		if($BE_USER->userTS["sni_supportchat."]["defLangLabel"]) {
			$this->defaultLLabel = $BE_USER->userTS["sni_supportchat."]["defLangLabel"];
		}
	}
	
	/**
	* get all chats and messages and return it as an array, store msg's to DB if any, lock/unlock a chat or destroy one
	* @param Array the last rows
	* @param Array Messages to save in DB
	* @param Array Chats which should be locked/unlocked
	* @param Array Chats which should be destroyed
	* @return Array the chats with data
	*/
	function doAll($lastRowArray,$msgToSend,$lockChats,$destroyChats,$typingStatus) { /*added for typingStatus*/
        global $TYPO3_DB;
		/* get the language Info */
		$language = $this->getLanguageInfo();
		/* get all chats */
		$retArray = Array();
        $tableChats = "tx_snisupportchat_chats";
        $res = $TYPO3_DB->exec_SELECTquery("*",$tableChats,'active=1 AND pid='.$this->pid,"","crdate desc");
		$i=0;
		// additional info that is shown after the language info in the chatbox  
		$hookObjectsArr = array();
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/additionalInfo'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/additionalInfo'] as $classRef) {
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}
		while ($row=$TYPO3_DB->sql_fetch_assoc($res)) {
			$lastRow = intval($lastRowArray[$row["uid"]]) ? intval($lastRowArray[$row["uid"]]) : 0;
			$this->loadChatFromData($row,$lastRow);
			if($this->hasUserRights()) {
				// get All Messages
                $messages = $this->getMessages("crdate,code,from_supportler,to_supportler,name,message");
				// send Messages
				if($msgToSend[$this->uid]) {
					foreach ($msgToSend[$this->uid] as $msg) {
	                    $this->insertMessage($msg,"beuser",$this->beUserName);
					}	
				}
				/*added for typingStatus*/
				$tmp_status = unserialize($this->db['status']);
				$tmp_status = $tmp_status['feu_typing'];
				
				$retArray[$i]["chatIndex"]["uid"] = $this->uid;
				$retArray[$i]["chatIndex"]["lastRow"] = $this->lastRow;
				$retArray[$i]["chatIndex"]["crdate"] = $this->renderTstamp($this->db["crdate"]);
				$retArray[$i]["chatIndex"]["fe_language"] = $this->db["language_uid"];
				$retArray[$i]["chatIndex"]["surfer_ip"] = $this->db["surfer_ip"];
				$retArray[$i]["chatIndex"]["be_user"] = $this->db["be_user"];
				$retArray[$i]["chatIndex"]["language_flag"] = $language[$this->db["language_uid"]]["flag"];
				$retArray[$i]["chatIndex"]["language_label"] = $language[$this->db["language_uid"]]["label"];
				$retArray[$i]["chatIndex"]["messages"] = $messages;
				/*added for typingStatus*/
				$retArray[$i]["chatIndex"]["status"] = ($tmp_status == 1 ? 1 : 0);
                // lock chat ?
                if(isset($lockChats[$this->uid])) {
                    $this->lockChat(intval($lockChats[$this->uid]));
					$retArray[$i]["chatIndex"]["from_lock_chat"] = intval($lockChats[$this->uid]);
                }
				// destroy chat ?
				if(intval($destroyChats[$this->uid])) {
					$this->destroyChat();
					$retArray[$i]["chatIndex"]["from_destroy_chat"] = 1;
				}
				/*added for typingStatus*/
				// set typing status
                if(isset($typingStatus[$this->uid])) {
                    $this->saveTypingStatus(intval($typingStatus[$this->uid]));
                }
				
				foreach($hookObjectsArr as $hookObj)    {
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
	* Returns an Array with all language Infos of the current Typo3 Installation
	*/
	function getLanguageInfo () {
		global $TYPO3_DB,$BACK_PATH;
		$lArray = Array(
			"0"  => Array(
				"flag" => $BACK_PATH."../".$this->defaultLImg,
				"label" => $this->defaultLLabel
			)
		);
		// get all languages from DB
        $res = $TYPO3_DB->exec_SELECTquery("uid,title,flag","sys_language","1");
        while ($row=$TYPO3_DB->sql_fetch_assoc($res)) {
			$lArray[$row["uid"]]["flag"] = $BACK_PATH."gfx/flags/".$row["flag"];
			$lArray[$row["uid"]]["label"] = $row["title"];
		}
		
		return ($lArray);				
	}

	function getBeUsers() {
		global $TYPO3_DB,$BE_USER;
		// get SelectBox with all be_user
		$table="be_sessions";
		$res = $TYPO3_DB->exec_SELECTquery("ses_userid",$table,'1');
		$inList="";
		$beUserArray = Array();
		while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$inList .= ",'".$row["ses_userid"]."'";
		}
		$inList = substr($inList,1);
		if($inList) {
			$table = "be_users";
			$options="";
			$res = $TYPO3_DB->exec_SELECTquery("uid,username,realName",$table,'deleted=0 AND disable=0 AND uid IN ('.$inList.') AND uid<>'.$BE_USER->user["uid"]);
			$i=0;
			while($row = $TYPO3_DB->sql_fetch_assoc($res)) {
				$name = $row["realName"] ? $row["realName"] : $row["username"];
				$beUserArray[$i] = Array(
					"uid" => $row["uid"],
					"name" => $name,
				);
				$i++;
			}
		}
		return ($beUserArray);
	}

	
	/**
	* get all log messages which are younger than now - 10min, takes care of the lastLogRow var
	* @return Array: the log rows
	*/
	function getLogMessages () {
		global $TYPO3_DB;
		if($this->logging) {
			$retArray = Array();
			$tableLog = "tx_snisupportchat_log";
			if(!$this->lastLogRow) {
				$limit = '5';
			}
			else {
				$limit = "";
			}
			$notOlderThan = time()-600;
			$res = $TYPO3_DB->exec_SELECTquery("uid,crdate,message",$tableLog,'pid='.$this->pid.' AND crdate > '.$notOlderThan.' AND uid > '.$this->lastLogRow,"","uid");
			$i=0;
			while ($row=$TYPO3_DB->sql_fetch_assoc($res)) {
				$this->lastLogRow = $row["uid"];
				$retArray[$i]["crdate"] = $this->renderTstamp($row["crdate"]);
				$retArray[$i]["message"] = $row["message"];
				$i++;
			}
			return ($retArray);
		}
	}
}
?>
