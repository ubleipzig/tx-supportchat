<?php
/**
 * Class SupportChat Frontend Listener
 *
 * Copyright (C) Leipzig University Library 2020 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 *
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

namespace Ubl\Supportchat\Ajax;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Ubl\Supportchat\Library\Chat;
use Ubl\Supportchat\Library\ChatHelper;

/**
 * Class FrontentListener
 *
 * Support Chat ajax frontend listener
 *
 * @package Ubl\SupportChat\Ajax
 */
class FrontendListener
{

    /**
     * Frontend language user id
     *
     * @var int
     * @access public
     */
    public $lang = 0;

    /**
     * Pid fpr chats and messages
     *
     * @var int
     * @access public
     */
    public $pid = 0;

    /**
     * The chat uid
     *
     * @var int
     * @access public
     */
    public $uid = 0;

    /**
     * Use typing indicator
     *
     * @var boolean $useTypingIndicator
     * @access public
     */
    public $useTypingIndicator = 0;

    /**
     * The session id
     *
     * @var string
     * @access protected
     */
    protected $identification = "";

    /**
     * Command
     *
     * @var string
     * @access protected
     */
    protected $cmd = "getAll";


    /**
     * Call chat frontend action listener
     *
     * @return string xml
     * @access public
     */
    public function getAjaxResponse()
    {
        $feUserObj = \TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();
        $this->identification = $feUserObj->id;
        $this->uid = intval(GeneralUtility::_GET("chat"))
            ? intval(GeneralUtility::_GET("chat")) : 0;
        $this->lang = intval(GeneralUtility::_GET("L"))
            ? intval(GeneralUtility::_GET("L")) : 0;
        $this->pid = intval(GeneralUtility::_GET("pid"))
            ? intval(GeneralUtility::_GET("pid")) : 0;
        /** 2012-04-11 tradem Initialize useTypingIndicator */
        $this->useTypingIndicator = intval(GeneralUtility::_GET("useTypingIndicator"))
            ? intval(GeneralUtility::_GET("useTypingIndicator")) : 0;
        if(GeneralUtility::_GP("cmd")) {
            $this->cmd = GeneralUtility::_GP("cmd");
        }
        // initialize the chat Object
        $lastRow = intval(GeneralUtility::_GP("lastRow"))
            ? intval(GeneralUtility::_GP("lastRow")) : 0;
        $chat = new Chat();
        $chat->initChat($this->pid, $this->identification,0, $this->useTypingIndicator);
        if ($this->uid) {
            $chat->loadChatFromDB($this->uid,$lastRow);
        }
        switch ($this->cmd) {
            case "checkIfOnline":
                $chatPids = GeneralUtility::_GET("chatPids");
                $onlineArray = ChatHelper::checkIfChatIsOnline($chatPids);
                $xml = ChatHelper::convert2xml($onlineArray);
                return ChatHelper::printResponse($xml);
                break;
            case "createChat":
                $chatUid = $chat->createChat($this->lang);
                return ChatHelper::printResponse($chatUid);
                break;
            case "destroyChat":
                if ($chat->hasUserRights()) {
                    $chat->destroyChat();
                }
                break;
            case "getAll":
                /* get and send messages*/
                if ($chat->hasUserRights()) {
                    // get messages from DB
                    $fields = "crdate,code,name,message";
                    $msgArray = $chat->getMessages($fields);
                    // store new messages in DB
                    $msgToSend = GeneralUtility::_POST("msgToSend");
                    $chat->saveTypingStatus(GeneralUtility::_GP("isTyping"));
                    if ($msgToSend) {
                        $userName = htmlspecialchars(GeneralUtility::_POST("chatUsername"));
                        for($i=0; $i < sizeOf($msgToSend); $i++) {
                            $chat->insertMessage($msgToSend[$i], "feuser",$userName);
                        }
                    }
                    $xmlArray = [
                        "time" => ChatHelper::renderTstamp(time()),
                        "lastRow" => $chat->lastRow,
                        "messages" => $msgArray,
                        "status" => $chat->getTypingStatus()
                    ];
                }
                else {
                    /* why no access */
                    $xmlArray = [
                        "time" => ChatHelper::renderTstamp(time()),
                        "status" => $chat->chatStatus()
                    ];
                }
                $xml = ChatHelper::convert2xml($xmlArray);
                return ChatHelper::printResponse($xml);
                break;
            case "createChatLog":
                $this->data = is_string(GeneralUtility::_POST("data"))
                    ? GeneralUtility::_POST("data") : null;
                if (isset($this->data) && is_string($this->data)) {
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