<?php
require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chat_functions.php');

class chat extends tx_chat_functions {
	
	var $identification = 0; // identification for user (FE-User Session OR BE-USER UID)
	var $admin = 0; // if admin then other access check (BE-USER) 
	var $uid=0; // the chat uid
	var $pid=0; // the pid for the chat, and Messages
	var $lastRow=0; // uid of the last row. The getMessage function will only get Messages with an uid greater than this var!
	
	/** tradem 2012-04-11 Added to control typing indiator */
	var $useTypingIndicator=0; // controls if typing indicator should show up or not, defaults to false (0)
	
	var $logging=1; // insert Log Messages in DB

	// Array for the Chat in DB
	var $db = Array();


	/**
	* Just iniatialize some basic data needed for every chat object!
	* 
	* tradem 2012-04-11 Added $useTypingIndicator parameter
	*/
	function initChat($pid,$ident,$admin=0,$useTypingIndicator=0) {
		$this->pid = intval($pid);
		$this->identification = $ident; // FE-User session id, or BE-User uid
		$this->admin = $admin; // only for BE-USERS
		/** tradem 2012-04-11 Assign $useTypingIndicator parameter */
		$this->useTypingIndicator = $useTypingIndicator;
		/** tradem 2012-04-13 Added log for more debug information */
		/** $this->writeLog("initChat: state of useTypingIndicator=[" . $this->useTypingIndicator . "]"); */				 				
	}

	/**
	* Get the chat from DB and load some needed data 
	*/
	function loadChatFromDB($uid,$lastRow) {
		$this->uid = $uid;
		$this->lastRow = $lastRow;
		$this->db = $this->getChat();
	}
	
	/**
	* Get the chat from @param1 and load some needed data 
	*/ 
	function loadChatFromData($dbChat,$lastRow) {
		$this->uid = $dbChat["uid"];
		$this->lastRow = $lastRow;
		$this->db = $dbChat;
	}

	/**
	* Check if the current user has permission to given chat 
	*/
	function hasUserRights() {
		if(!$this->admin) {
			if($this->db["session"]==$this->identification && $this->identification && $this->db["active"] && $this->uid) { 
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
	* @return String: be_user_destroyed,fe_user_destroyed,timeout,no_access
	*/
	function chatStatus() {
		if($this->db["status"]) {
			return ($this->db["status"]);
		}
		else {
			return ("no_access");
		}
	}

	/*
	* get the chat from DB
	* @return Array the Chat
	*/
	function getChat() {
        global $TYPO3_DB;
        $tableChats = "tx_snisupportchat_chats";
        $res = $TYPO3_DB->exec_SELECTquery("*",$tableChats,'uid='.$this->uid);
        return ($TYPO3_DB->sql_fetch_assoc($res));
	}

	/*
	* Creates the Chat / store it to DB
	* @return the newly created chat uid
	*/
	function createChat($fe_lang_uid) {
        global $TYPO3_DB;
        $table = "tx_snisupportchat_chats";
        $insertData = Array(
            "pid" => $this->pid,
            "crdate" => time(),
            "session" => $this->identification,
            "active" => 1,
            "language_uid" => $fe_lang_uid,
			"surfer_ip" => $_SERVER["REMOTE_ADDR"]
        );
        // Hook for example adding a call by Asterisk to youre Supportlers or manipulating the DB entry  
        $hookObjectsArr = array();
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/createChat'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/createChat'] as $classRef) {
                $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
            }
        }

        foreach($hookObjectsArr as $hookObj)    {
            if (method_exists($hookObj, 'createChat')) {
                $insertData = $hookObj->createChat($insertData,$this);
            }
        }
        $TYPO3_DB->exec_INSERTquery($table,$insertData);
        $chatUid = $TYPO3_DB->sql_insert_id();

        if($this->logging) {
            $this->writeLog("Chat ".$chatUid." was succesfully created!");
            $this->logTypingStatus($chatUid);            
        }
		return ($chatUid);		
	}

	/**
	* Get all messages with uid greater then $this->lastRow
	* @param String: comma separatet list of fieldnames to get from DB
	* @return Array: a multidimensional Messages Array 
	*/
	function getMessages($fields="*") {
		global $TYPO3_DB;
		$table = "tx_snisupportchat_messages";
		$res = $TYPO3_DB->exec_SELECTquery($fields.",uid",$table,'chat_pid='.$this->uid.' AND uid > '.$this->lastRow,"","crdate");
		$data = Array();
		$fieldArray = explode(",",$fields);
		$i=0;
		while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$this->lastRow = $row["uid"];
			foreach ($fieldArray as $field) {
				if($field=="crdate") {
					$data[$i][$field] = $this->renderTstamp($row[$field]);
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
	* Insert a Message in DB
	* @return the uid of the newly created message
	*/
	function insertMessage($message,$code,$name,$fromSupportler="",$toSupportler="") {
		global $TYPO3_DB;

        $message = htmlspecialchars($message);
		$message = $this->html_activate_links($message);		

        /* DEPRECATED HOOK since 26.11.11 - use preInsertMessage instead */
        $hookObjectsArr = array();
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/prePostMessage'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/prePostMessage'] as $classRef) {
                $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
            }
        }
        foreach($hookObjectsArr as $hookObj)    {
            if (method_exists($hookObj, 'prePostMessage')) {
                $message = $hookObj->prePostMessage($message,$this); // DEPRECATED HOOK since 26.11.11 - use preInsertMessage instead
            }
        }
		
		$insertData = Array(
			"crdate" => time(),
			"tstamp"=> time(),
			"pid" => $this->pid,
			"code" => $code,
			"from_supportler" => $fromSupportler,
			"to_supportler" => $toSupportler,
			"chat_pid" => $this->uid,
			"name" => $name,
			"message" => $message
		); 

        // Hook for own processing of posted message  
        $hookObjectsArr = array();
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/insertMessage'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/insertMessage'] as $classRef) {
                $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
            }
        }

        foreach($hookObjectsArr as $hookObj)    {
            if (method_exists($hookObj, 'preInsertMessage')) {
                $message = $hookObj->preInsertMessage($insertData,$this);
            }
        }

	    $table = "tx_snisupportchat_messages";
    	$TYPO3_DB->exec_INSERTquery($table,$insertData);
        $messageUid = $TYPO3_DB->sql_insert_id();
		if($messageUid > $this->lastRow) {
			$this->lastRow = $messageUid;
		}	
		return($messageUid);
	}

	/**
	* Destroys a chat (active=0)
	*/
	function destroyChat() {
		global $TYPO3_DB,$BE_USER;
		$table = "tx_snisupportchat_chats";
		$updateArray = Array(
			"active" => 0, 
		);         
		if($BE_USER->user["uid"]) {
			$updateArray["status"] = "be_user_destroyed";
		}
		else {
			$updateArray["status"] = "fe_user_destroyed";
		}
		$TYPO3_DB->exec_UPDATEquery($table,'uid='.$this->uid,$updateArray);
		$this->db["active"]=0;
		if ($this->logging) {
			$user = $this->admin ? ($BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"]) : "FE-User"; 
			$this->writeLog("Chat ".$this->uid." was succesfully destroyed by ".$user);
		}
	}

	/**
	* Write log message to DB
	* @return 1
	*/
	function writeLog($msg) {
        global $TYPO3_DB;
        $insertData = Array(
            "crdate" => time(),
            "tstamp"=> time(),
            "pid" => $this->pid,
            "message" => $msg
        );
        $table = "tx_snisupportchat_log";
        $TYPO3_DB->exec_INSERTquery($table,$insertData);
		return 1;
	}

    /**
    * Locks / unlocks the chat for current BE-User
	* @param Boolean: lock or unlock the chat
    * @return Boolean: 1 for chat locked, 0 for chat not locked 
    */
    function lockChat($lock=1) {
		global $TYPO3_DB,$BE_USER;
        $table = "tx_snisupportchat_chats";
        $updateArray = Array(
            "be_user" => $lock ? $BE_USER->user["uid"] : "",
        );
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
    * @param Int: the be-user to assume the chat to
    * @return: nothing
	* @todo implement it
    */
    function assumeChatToUser($be_user) {

    }
    
    /**
    * Accept or decline the request to assume the chat
    * @param Boolean: accept it or not
    * @return: nothing
	* @todo implement it
    */
    function acceptAssumeChat($accepted=1) {

    }

    /**
     * Destroys all chats that are inactive - this happens if no messages is been send for a given time defined by TS
     * @return nothing
     */
    function destroyInactiveChats($inactivateTime) {
        global $TYPO3_DB;
        $tableChats = "tx_snisupportchat_chats";
        $tableMessages = "tx_snisupportchat_messages";
        $res = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid,crdate',$tableChats,'active=1 AND deleted=0 AND hidden=0 AND pid='.$this->pid);
        while ($row=$GLOBALS["TYPO3_DB"]->sql_fetch_assoc($res)) {
            $limit = time() - ($inactivateTime*60);
            $messageRes = $GLOBALS["TYPO3_DB"]->exec_SELECTquery('uid',$tableMessages,'chat_pid='.$row["uid"].' AND crdate > '.$limit);
            $messageRow = $GLOBALS["TYPO3_DB"]->sql_fetch_assoc($messageRes);
            if(!$messageRow["uid"] && $row["crdate"] < $limit) {
                // delete the Chat
                $GLOBALS["TYPO3_DB"]->exec_UPDATEquery($tableChats,"uid=".$row["uid"],array("active" => "0", "status" => "timeout"));
		        if ($this->logging) {
		            $user = $BE_USER->user["uid"] ? ($BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"]) : "FE-User";
        		    $this->writeLog("Chat ".$row["uid"]." was succesfully destroyed by System, timeout exceeded");
		        }
            }
        }
    }
	
    /**
     * Save info to database if user is currently typing if usage of typing indicator is set.
     * Behavoir of this method is controlled by state of <code>useTypingIndicator</code>.
     * 
     * @param Boolean: true if current end is typing, false if it's not 
     * @return Boolean: true or false if useTypingIndicator is not set to true (1).
     * @see #useTypingIndicator 
     * @see #initChat($pid,$ident,$admin=0,$useTypingIndicator)  
     */
    function saveTypingStatus($isTyping) {
        global $BE_USER;
        
        /** tradem 2012-04-11 Added check of control variable. */
        if ($this->useTypingIndicator == 1) {
//         	$this->writeLog("saveTypingStatus: this->useTypingIndicator=[" . $this->useTypingIndicator . "]");        	
        	if($this->db['uid']) {                
        		$status_array = unserialize($this->db['status']);
        		if(!is_array($status_array)) {
             		$status_array = array ('feu_typing'=>0, 'beu_typing'=>0);
            	}        	
                if($BE_USER->user["uid"]) {
                    //current user is a backend-user and typing?
                    if($isTyping == 1) {
                        $status_array['beu_typing'] = 1;
                    } 
                    else {
                        $status_array['beu_typing'] = 0;
        			}        	
       			}
                else {
                    //current user is a frontend-user and typing?
                    if($isTyping == 1) {
                        $status_array['feu_typing'] = 1;
                    }
                    else {
                        $status_array['feu_typing'] = 0;
                	}        	
                }
                $updateArray = Array('status' => serialize($status_array));
                if($this->db['status'] != $updateArray['status']) {
                    $tableChats = "tx_snisupportchat_chats";        	
            		$GLOBALS["TYPO3_DB"]->exec_UPDATEquery($tableChats,"uid=".$this->uid,$updateArray);        	
                }    
       		}        	
        	return true;        		 
        }
        else {
        	return false;
        }        
   }

   
    /**
     * Retrieve typing status of opposite chat-partner.
     * Behavoir of this method is controlled by state of <code>useTypingIndicator</code>.
     * 
     * @param none
     * @return Boolean: true if other end is typing, false if it's not  or if useTypingIndicator is not set to true (1).
     * @see #useTypingIndicator 
     * @see #initChat($pid,$ident,$admin=0,$useTypingIndicator)  
     */
    function getTypingStatus() {
        global $BE_USER;

        /** tradem 2012-04-11 Added check of control variable. */
        if ($this->useTypingIndicator == 1) {
        	// $this->writeLog("getTypingStatus: this->useTypingIndicator=[" . $this->useTypingIndicator . "]");        	 
        	if($this->db['uid'] && $this->db['active']) {
                $status_array = unserialize($this->db['status']);
                if(!is_array($status_array)) {
                   $status_array = array ('feu_typing'=>0, 'beu_typing'=>0);
                }
                if($BE_USER->user["uid"]) {
                        //current user is a backend-user and frontend-user is typing?
                        if($status_array['feu_typing'] == 1) {
                            return 1;
                        }                        
                        else {
                            return 0;
                        }    
                } 
                else {
                //current user is frontend-user and backend-user is typing?
                    if($status_array['beu_typing'] == 1) {
                        return 1;
                    }    
                    else {
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
     * @author tradem
     * @since 2012-04-11 
     */
    function logTypingStatus($chatId) {
    	if ($this->useTypingIndicator != 1) {
    		$this->writeLog("Info: Chat ".$chatId." has been configured without typing indicator!");
    	}    	 
    }
    
	
}
?>
