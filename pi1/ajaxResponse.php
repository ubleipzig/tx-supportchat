<?php
// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('PATH_typo3conf')) die ('Could not access this script directly!');
        
require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chat.php');

class ajaxResponse {
	var $uid = 0; // the chat uid from getVar
	var $identification = "";	// the session id
	var $cmd = "getAll"; // default command
	var $lang = 0; // the Fe-User language-uid
	var $pid = 0; // the pid for chats and messages
	/** tradem 2012-04-13 Added to control typing indiator */	
	var $useTypingIndicator = 0; // controls if typing indicator should show up or not, defaults to false (0)
	/** */	

	function init() {
		$feUserObj = tslib_eidtools::initFeUser();
		$this->identification = $feUserObj->id;      
		$this->uid = intval(t3lib_div::_GET("chat")) ? intval(t3lib_div::_GET("chat")) : 0;
		$this->lang = intval(t3lib_div::_GET("L")) ? intval(t3lib_div::_GET("L")) : 0;
		$this->pid = intval(t3lib_div::_GET("pid")) ? intval(t3lib_div::_GET("pid")) : 0;
		/** 2012-04-11 tradem Initialize useTypingIndicator */
		$this->useTypingIndicator = intval(t3lib_div::_GET("useTypingIndicator")) ? intval(t3lib_div::_GET("useTypingIndicator")) : 0; 
		if(t3lib_div::_GP("cmd")) {
			$this->cmd = t3lib_div::_GP("cmd");
		}
        // initialize the chat Object 
		$lastRow = intval(t3lib_div::_GP("lastRow")) ? intval(t3lib_div::_GP("lastRow")) : 0;
        $chat = new chat();
		
		/** tradem 2012-04-13 Pass useTypingIndicator and log.*/
		$chat->initChat($this->pid,$this->identification,0,$this->useTypingIndicator);			
		// $chat->writeLog("Frontend: ajaxResponse.php setting useTypingIndicator=[".$this->useTypingIndicator."]");
		if($this->uid) {
			$chat->loadChatFromDB($this->uid,$lastRow);
		}
		switch ($this->cmd) {
			case "checkIfOnline":
				$chatPids = t3lib_div::_GET("chatPids");
				$onlineArray = $chat->checkIfChatIsOnline($chatPids);
                $xml = $chat->convert2xml($onlineArray);
				$chat->printResponse($xml);
			break;
			case "createChat":
				$chatUid = $chat->createChat($this->lang);
				$chat->printResponse($chatUid);
			break;
			case "destroyChat":
				if($chat->hasUserRights()) {
					$chat->destroyChat();					
				}
			break;
			case "getAll":
				/* get and send messages*/
				if($chat->hasUserRights()) {
					// get messages from DB
                    $fields = "crdate,code,name,message";
                    $msgArray = $chat->getMessages($fields);
                    // store new messages in DB
                    $msgToSend = t3lib_div::_POST("msgToSend");
                    $chat->saveTypingStatus(t3lib_div::_GP("isTyping"));
                    if($msgToSend) {
                        $userName = htmlspecialchars(t3lib_div::_POST("chatUsername"));
                        for($i=0; $i<sizeOf($msgToSend); $i++) {
                            $chat->insertMessage($msgToSend[$i],"feuser",$userName);
                        }
                    }
					$xmlArray = Array(
						"time" => $chat->renderTstamp(time()),
						"lastRow" => $chat->lastRow,
						"messages" => $msgArray,
                        "status" => $chat->getTypingStatus()
					);
				}
                else {
                    /* why no access */
                    $xmlArray = Array(
                        "time" => $chat->renderTstamp(time()),
                        "status" => $chat->chatStatus()
                    );
                }
                $xml = $chat->convert2xml($xmlArray);
                $chat->printResponse($xml);
			break;            
		    case "createChatLog":
		        $this->data = is_string(t3lib_div::_POST("data")) ? t3lib_div::_POST("data") : NULL;
		        if(isset($this->data) && is_string($this->data)) {
		            //clean up posted data
		            $this->data = htmlspecialchars_decode(strip_tags(str_replace('</p>', "\r\n", trim($this->data))));
                    //add intro-text
                    $intro = "Chat-Log : " . date("l, j. M Y H:i:s") . "\r\n\r\n";
		            ob_clean();
                    header('Content-Type: text/plain');
                    header('Content-Disposition: attachment; filename="ChatLog'.time().'.txt"');
                    print $intro . $this->data;
                }
		    break;
		}
	}
}

$ajax = new ajaxResponse;
$ajax->init();
?>
