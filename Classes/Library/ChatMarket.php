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

class ChatMarket extends Chat
{
    /**
     * Backend user authentication
     *
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUserAuthentication;

    /**
     * Should session be logged
     *
     * @var boolean $logging  Default false
     * @access public
     */
	public $logging = 0;

    /**
     * Limit of log messages to displayed
     *
     * @var boolean $logging  Default false
     * @access public
     */
    public $logLimit = 10;

    /**
     * Last logged row
     *
     * @var int $lastLogRow
     * @access public
     * @deprecated Variable seems to have not apparently any use case; replaced by $logLimit in function.
     */
	public $lastLogRow = null;

    /**
     * Path to default language flag
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
	 * Constructor initializes variables
     *
     * @param boolean $logging
     * @param int $lastLogRow
     *
     * @access public
	 */
	public function __construct()
    {
        parent::__construct();
        $this->logging = func_get_arg(0);
		$this->lastLogRow = (int)(func_get_arg(1));
		if ($this->getBackendUserTypoScript("defLangImg")) {
			$this->defaultLImg = $this->getBackendUserTypoScript("defLangImg");
		}
		if ($this->getBackendUserTypoScript("defLangLabel")) {
			$this->defaultLLabel = $this->getBackendUserTypoScript("defLangLabel");
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
		/* Get the language Info */
		// $language = $this->getLanguageInfo();
		/* get all chats */
		$retArray = [];
        $i = 0;
		// hook for additional info that is shown after the language info in the chatbox
        $hookObjectsArr = [];
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/ChatMarket.php']['additionalInfo'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/ChatMarket.php']['additionalInfo'] as $classRef) {
				$hookObjectsArr[] = GeneralUtility::getUserObj($classRef);
			}
		}
        $res = $this->chatsRepository->findActiveChatsByPid($this->pid);
		foreach ($res as $row) {
			$lastRow = (int)($lastRowArray[($row->getUid())]) ?: 0;
			$mapChatData = [
                'active' => $row->getActive(),
                'be_user' => $row->getBackendUser(),
                'crdate' => $row->getCrdate(),
                'language_uid' => $row->getLanguageUid(),
                'session' => $row->getSession(),
                'status' => $row->getStatus(),
                'type_status' => $row->getTypeStatus(),
                'surfer_ip' => $row->getClientIp(),
                'uid' => $row->getUid()
            ];
            // Note: Use of variable $row seems to be finish here. Loop recurs and uses instead $this->db for data.
            $this->loadChatFromData($mapChatData, $lastRow);
			if ($this->hasUserRights()) {
				// Get all Messages
                $messages = $this->getMessages();
				// Send Messages
				if ($msgToSend[$this->uid]) {
					foreach ($msgToSend[$this->uid] as $msg) {
	                    $this->insertMessage($msg,"beuser", ($this->getBackendUsername()) ?: "Supporter");
					}	
				}

				// Adds localization class
                $localization = new LocalizationHelper();
                // Set default language_uid
                $languageUid = (int)$this->db["language_uid"];

				$retArray[$i]["uid"] = $this->uid;
				$retArray[$i]["lastRow"] = $this->lastRow;
				$retArray[$i]["crdate"] =
                    ChatHelper::renderTstamp($this->db["crdate"]);
				$retArray[$i]["fe_language"] = $languageUid;
				$retArray[$i]["surfer_ip"] = $this->db["surfer_ip"];
				$retArray[$i]["be_user"] = $this->db["be_user"];
                $retArray[$i]["language_flag"] =
                    $localization->getRenderedFlagIconByLanguageUid($languageUid);
				$retArray[$i]["language_label"] =
                    $localization->getLabelByLanguageUid($languageUid);
				$retArray[$i]["messages"] = $messages;
				// Typing status
                // Is client typing?
                $ts = json_decode($this->db['type_status'], true);
                $retArray[$i]["type_status"] = ($ts['feu_typing'] == 1) ? true : false;
                // lock chat ?
                if (isset($lockChats[$this->uid])) {
                    $this->lockChat((int)$lockChats[$this->uid]);
					$retArray[$i]["from_lock_chat"] = (int)$lockChats[$this->uid];
                }
				// destroy chat ?
				if ((int)$destroyChats[$this->uid]) {
					$this->destroyChat();
					$retArray[$i]["from_destroy_chat"] = 1;
				}
				/*added for typingStatus*/
				// set typing status
                if (isset($typingStatus[$this->uid])) {
                    $this->saveTypingStatus((int)$typingStatus[$this->uid]);
                }

                // process hook for additional info
                foreach ($hookObjectsArr as $hookObj) {
                    $array = [];
                    $retArray[$i]["additionalInfo"] =
                        GeneralUtility::callUserFunction($hookObj, $array, $this);
				}
				$i++;
			}
		}
		return ($retArray);
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
		if ($this->logging) {
            $result = $this->logsRepository->findLogMessages($this->pid, (time() - 900), (int)($this->logLimit) ?: 10);
			$i = 0;
            $retArray = [];
            foreach ($result as $row) {
				$this->lastLogRow = $row->getUid();
				$retArray[$i]["crdate"] = ChatHelper::renderTstamp($row->getCrdate());
				$retArray[$i]["message"] = $row->getMessage();
				$i++;
			}
			return $retArray;
		}
	}

    /**
     * Get backend user typo script settings
     *
     * @param string $key
     *
     * @return mixed
     * @access protected
     */
    protected function getBackendUserTypoScript($key)
    {
        if (empty($key) && !is_string($key)) {
            throw new \InvalidArgumentException(
                'Parameter $key of ' . __METHOD__ . ' has to be set'
            );
        }
        return ($GLOBALS['BE_USER']->userTS["supportchat."][$key]) ?: null;
    }
}
