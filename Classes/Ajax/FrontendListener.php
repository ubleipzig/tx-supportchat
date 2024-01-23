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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\HmtlResponse;
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
     * @return ResponseInterface
     * @access public
     */
    public function getAjaxResponse() : ResponseInterface
    {
        /**
         * @to-do class EidUtility is deprecated and has to be replaced by a PSR-15 middleware solution of class
         * TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication with Typo3 v10
         */
        $feUserObj = \TYPO3\CMS\Frontend\Utility\EidUtility::initFeUser();
        $this->identification = $feUserObj->id;
        $this->uid = (int)(GeneralUtility::_GET("chat")) ?: 0;
        $this->lang = (int)(GeneralUtility::_GET("L")) ?: 0;
        $this->pid = (int)(GeneralUtility::_GET("pid")) ?: 0;
        /** 2012-04-11 tradem Initialize useTypingIndicator */
        $this->useTypingIndicator = (int)(GeneralUtility::_GET("useTypingIndicator")) ?: 0;
        $this->cmd = (GeneralUtility::_GP("cmd")) ?: null;
        // initialize the chat Object
        $lastRow = (int)(GeneralUtility::_GP("lastRow")) ?: 0;
        $chat = new Chat();
        $chat->initChat($this->pid, $this->identification,false, $this->useTypingIndicator);
        if ($this->uid) {
            $chat->loadChatFromDB($this->uid, $lastRow);
        }
        switch ($this->cmd) {
            case "checkIfOnline":
                // no case detected for use
                // functionality could be replaced by extension supportchat-switch
                // further inspections needed before marking as deprecated
                // @to-do return have to be part of ResponseInterface
                $chatPids = GeneralUtility::_GET("chatPids");
                $onlineArray = ChatHelper::checkIfChatIsOnline($chatPids);
                $xml = ChatHelper::convert2xml($onlineArray);
                return ChatHelper::printResponse($xml);
                break;
            case "createChat":
                $chatUid = $chat->createChat($this->lang);
                return GeneralUtility::makeInstance(
                    JsonResponse::class,
                    [$chatUid],
                    200
                );
                break;
            case "destroyChat":
                if ($chat->hasUserRights()) {
                    $chat->destroyChat();
                }
                return GeneralUtility::makeInstance(
                    JsonResponse::class,
                    [],
                    200
                );
                break;
            case "getAll":
                /* get and send messages*/
                if ($chat->hasUserRights()) {
                    // get messages from DB
                    $msgArray = $chat->getMessages();
                    // store new messages in DB
                    $msgToSend = GeneralUtility::_POST("msgToSend");
                    $chat->saveTypingStatus(GeneralUtility::_GP("isTyping"));
                    if ($msgToSend) {
                        $userName = htmlspecialchars(GeneralUtility::_POST("chatUsername"));
                        for ($i=0; $i < sizeOf($msgToSend); $i++) {
                            $chat->insertMessage($msgToSend[$i], "feuser", $userName);
                        }
                    }
                    $xmlArray = [
                        "time" => ChatHelper::renderTstamp(time()),
                        "lastRow" => $chat->lastRow,
                        "messages" => $msgArray,
                        "typingStatus" => $chat->getTypingStatus()
                    ];
                } else {
                    /* why no access */
                    $xmlArray = [
                        "time" => ChatHelper::renderTstamp(time()),
                        "status" => $chat->chatStatus()
                    ];
                }
                return GeneralUtility::makeInstance(
                    JsonResponse::class,
                    $xmlArray,
                    200
                );
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