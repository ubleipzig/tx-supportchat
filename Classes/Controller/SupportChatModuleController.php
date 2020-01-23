<?php
/**
 * Class SupportChatModuleController
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

namespace Ubl\Supportchat\Controller;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Lang\LanguageService;

use Ubl\Supportchat\Library\Chat;
use Ubl\Supportchat\Library\ChatHelper;
use Ubl\Supportchat\Library\ChatMarket;

/**
 * Class AbstractController
 *
 * Provides common methods to use in all controllers
 *
 * @package Ubl\SupportChat\Controller
 */
class SupportChatModuleController extends BaseAbstractController
{

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName
        = \TYPO3\CMS\Backend\View\BackendTemplateView::class;

    /**
     * The period of the AJAX Request
     *
     * @var int $ajaxGetAllFreq
     * @access private
     */
    private $ajaxGetAllFreq = 3;

    /**
     * The backend username shown in the chat
     *
     * @var string $beUserName
     * @access private
     */
    private $beUserName;

    /**
     * The page id where chats and messages are stored
     *
     * @var int $chatsPid
     * @access private
     */
    private $chatsPid;

    /**
     * The default language for the backend user
     *
     * @var string $defLang
     * @access private
     */
    private $defLang = "gb";

    /**
     * Pageinfo to check access
     *
     * @var mixed
     * @access private
     */
    private $pageInformation;

    /**
     * Play an alert sound yes or no
     *
     * @var boolean $playAlert
     * @access private
     */
    private $playAlert = 1;

    /**
     *  Display the log box
     *
     * @var boolean
     * @access private
     */
    private $showLogBox = 1;

    /**
     * Time to inactivate a chat in minutes
     *
     * @var int $timeToInactivateChat
     * @access private
     */
    private $timeToInactivateChat = 15;

    /**
     * Controls if typing indicator should show up or not, defaults to true (1)
     *
     * @var boolean $useTypingIndicator
     * @access private
     */
    private $useTypingIndicator = 1;

    /**
     * Initializes the module
     *
     * @return void
     * @throws Exception    If no chat pid given.
     *
     */
    public function initializeAction()
    {
        global $BE_USER;

        $this->chatsPid = $BE_USER->userTS["supportchat."]["chatsPid"];
        if (!$this->chatsPid) {
            throw new Exception(
                'You must insert the BE-User TS-Config var "supportchat.chatsPid"!'
            );
        }

        $this->defLang = ($BE_USER->userTS["supportchat."]["defLang"])
            ? $BE_USER->userTS["supportchat."]["defLang"] : $this->defLang;

        /** 2012-04-11 Added to control typing indiator if it works. */
        $this->useTypingIndicator = (isset($BE_USER->userTS["supportchat."]["useTypingIndicator"]))
            ? intval($BE_USER->userTS["supportchat."]["useTypingIndicator"]) : $this->useTypingIndicator;
        $this->ajaxGetAllFreq = ($BE_USER->userTS["supportchat."]["ajaxGetAllFreq"])
            ? $BE_USER->userTS["supportchat."]["ajaxGetAllFreq"] * 1000
            : $this->ajaxGetAllFreq * 1000;
        $this->timeToInactivateChat = (isset($BE_USER->userTS["supportchat."]["timeToInactivateChatIfNoMessages"]))
            ? $BE_USER->userTS["supportchat."]["timeToInactivateChatIfNoMessages"]
            : $this->timeToInactivateChat;
        $this->playAlert = (isset($BE_USER->userTS["supportchat."]["playAlert"]))
            ? $BE_USER->userTS["supportchat."]["playAlert"] : $this->playAlert;
        $this->showLogBox = (isset($BE_USER->userTS["supportchat."]["showLogBox"]))
            ? $BE_USER->userTS["supportchat."]["showLogBox"] : $this->showLogBox;
        $this->beUserName = (isset($BE_USER->user["realName"]))
            ? $BE_USER->user["realName"] : $BE_USER->user["username"];
        // general
        $this->id = (int)GeneralUtility::_GET('id');
        //The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageInformation =
            BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(1));
    }

    /**
     * Index action
     *
     */
    public function indexAction()
    {
        global $BACK_PATH;

        // Draw the header.
        $this->doc = GeneralUtility::makeInstance("TYPO3\\CMS\\Backend\\Template\\DocumentTemplate");
        $this->doc->backPath = $BACK_PATH;

        if (isset($this->playAlert) && $this->playAlert == 1) {
            // Add the Alert SWF File
            $contentPlayAlert = '
				<object id="beep_alert" class="flash" type="application/x-shockwave-flash" data="'. ExtensionManagementUtility::extRelPath('supportchat') .'Resources/Public/img/flash/flashbeep_alert.swf" width="1" height="1">
					<param name="movie" value="' . ExtensionManagementUtility::extRelPath('supportchat') . 'Resources/Public/img/flash/flashbeep_alert.swf" />
				</object>
				<p class="alert"><input type="checkbox" checked="checked" id="alert_check" /> ' . $this->translate("module.playAlert") . '</p>
			';
        }

        $chat = new Chat();
        $chat->initChat($this->chatsPid, "");
        $chat->destroyInactiveChats($this->timeToInactivateChat);

        // JavaScript
        $this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}
				let assetsPath = "' . ExtensionManagementUtility::extRelPath('supportchat') . 'Resources/Public/' . '"
			</script>
		';
        $this->doc->JScode .= $this->addJsInHeader();
        $this->doc->postCode = '
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
			</script>
		';

        $this->content = $this->doc->startPage($this->translate("title"));
        $this->content .= $this->doc->header($this->translate("title"));
        $this->content .= $contentPlayAlert;
        $this->content .= '<div style="padding-top: 20px;"></div>';
        $this->content .= $this->moduleContent();
        $this->menuConfig();

        // ShortCut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $this->content .= '<div style="padding-top: 20px;"></div>';
            $shortCutIcon = $this->doc->makeShortcutIcon(
                "id",
                implode(",", array_keys($this->MOD_MENU)),
                $this->getBackendUser()->groupData['modules']
            );
            $this->content .= $this->doc->section("", $shortCutIcon);
        }
        $this->content .= $this->doc->endPage();
        $this->view->assign('content', $this->content);
    }

    /**
     * Get chat ajax action
     *
     * @return string xml
     *
     */
    public function getChatAction()
    {
        global $BE_USER;

        if (!$BE_USER->user["uid"]) {
            $xmlArray = array(
                "fromNoAccess" => array(
                    "time" => ChatHelper::renderTstamp(time()),
                )
            );
            return ($xmlArray);
        }
        $this->chatsPid = $BE_USER->userTS["supportchat."]["chatsPid"];
        if($BE_USER->userTS["supportchat."]["showLogBox"]!="") {
            $this->logging = $BE_USER->userTS["supportchat."]["showLogBox"];
        }
        /** tradem 2012-04-12 Added to control typing indiator if it works. */
        if ($BE_USER->userTS["supportchat."]["useTypingIndicator"]) {
            $this->useTypingIndicator = $BE_USER->userTS["supportchat."]["useTypingIndicator"];
        }
        $this->lastRowArray = (GeneralUtility::_GP("lastRowArray"))
            ? GeneralUtility::_GP("lastRowArray") : array();
        if(GeneralUtility::_GP("cmd")) {
            $this->cmd = GeneralUtility::_GP("cmd");
        }
        $this->lastLogRow = (intval(GeneralUtility::_GP("lastLogRow")))
            ? intval(GeneralUtility::_GP("lastLogRow")) : 0;
        $this->uid = intval(GeneralUtility::_GP("chatUid"))
            ? intval(GeneralUtility::_GP("chatUid")) : 0;
        $chatMarket = new ChatMarket($this->logging, $this->lastLogRow);
        $chatMarket->initChat(
            $this->chatsPid,
            $BE_USER->user["uid"],
            1,
            $this->useTypingIndicator
        );
        switch($this->cmd) {
            case 'doAll':
                // get all chats,messages,time,be_user
                $msgToSend = GeneralUtility::_GP("msgToSend");
                $lockChats = GeneralUtility::_GP("lockChat");
                $destroyChats = GeneralUtility::_GP("destroyChat");
                /*added for typingStatus*/
                $typingStatus = GeneralUtility::_GP("typingStatus");
                $xmlArray = array(
                    "fromDoAll" => array(
                        "time" => ChatHelper::renderTstamp(time()),
                        /*added for typingStatus*/
                        "chats" => $chatMarket->doAll($this->lastRowArray, $msgToSend, $lockChats, $destroyChats, $typingStatus),
                        "log" => $chatMarket->getLogMessages(),
                        "lastLogRow" => $chatMarket->lastLogRow,
                        "beUsers" => $chatMarket->getBeUsers(),
                    )
                );
                $xml = ChatHelper::convert2xml($xmlArray);
                ChatHelper::printResponse($xml);
                break;
        }
        return;
    }

    /**
     * Generates the module content
     *
     * @return    void
     */
    private function moduleContent()
    {
        // page/be_user TSconfig settings:
        // @deprecated seems to have no proper function at codes FM
        $modTSconfig = BackendUtility::getModTSconfig(
            $this->id,
            "mod." . $GLOBALS["MCONF"]["name"]
        );

        // render Chat Boxes Wrap
        $content = '<div id="chatboxes_wrap">';
        $content .= '</div>';
        $content .= '<hr class="clearer" />';
        if (isset($this->showLogBox) && $this->showLogBox == 1) {
            $content .= '<div style="padding-top: 5px;"></div>';
            $content .= '<p class="log_title">Log:</p>';
            $content .= '<div id="logBox">&nbsp;</div>';
        }
        return $content;
    }

    /**
     * Adds the complete JS Code
     *
     * @return Complete Java Code
     */
    private function addJsInHeader()
    {
        //@deprecated lines below are uncommented due to $res seems to be not used at method
        //global $TYPO3_DB;
        //$table = "sys_language";
        //$res = $TYPO3_DB->exec_SELECTquery("uid, flag, title", $table, '1');
        $jsCode = '
			<link rel="stylesheet" type="text/css" href="' . GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::extRelPath('supportchat') . 'Resources/Public/css/module-chat.css') . '" />
			<script type="text/javascript" src="' . ExtensionManagementUtility::extRelPath('supportchat') . 'Resources/Public/js/mootools-1.2.6-core-yc.js"></script>
			<script type="text/javascript" src="' . ExtensionManagementUtility::extRelPath('supportchat') . 'Resources/Public/js/mootools-1.2.5.1-more.js"></script>
			<script type="text/javascript" src="' . GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::extRelPath('supportchat').'Resources/Public/js/Element.Forms.js') . '"></script>
			<script type="text/javascript" src="' . GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::extRelPath('supportchat').'Resources/Public/js/UvumiDropdown-compressed.js') . '"></script>
			<script type="text/javascript" src="' . GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::extRelPath('supportchat').'Resources/Public/js/smilies.js') . '"></script>
			<script type="text/javascript" src="' . GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::extRelPath('supportchat').'Resources/Public/js/supportchat_be.js') . '"></script>
			<script type="text/javascript">
			/*<![CDATA[*/
			<!--
				var LL = {
					"options": "' . addslashes($this->translate("module.options")) . '",
					"text_pieces": "' . addslashes($this->translate("module.text_pieces")) . '",
					"options_lock": "' . addslashes($this->translate("module.options_lock")) . '",
					"options_unlock": "' . addslashes($this->translate("module.options_unlock")) . '",
					"options_assume": "' . addslashes($this->translate("module.options_assume")) . '",
					"created_at": "' . addslashes($this->translate("module.created_at")) . '",
					"language": "' . addslashes($this->translate("module.language")) . '",
					"type_youre_message": "' . addslashes($this->translate("module.type_youre_message")) . '",
					"status_unlocked": "' . addslashes($this->translate("module.status_unlocked")) . '",
					"status_locked": "' . addslashes($this->translate("module.status_locked")) . '",
					"username": "' . addslashes($this->beUserName) . '",
					"system": "' . addslashes($this->translate("module.system")) . '",
					"chatDestroyedMsg": "' . addslashes($this->translate("module.chatDestroyedMsg")) . '",
					"welcomeMsg": "' . addslashes(sprintf($this->translate("module.welcomeMsg"), $this->beUserName)) . '",
					"noFixTextInThisLanguage": "' . addslashes($this->translate("module.noFixTextInThisLanguage")) . '",
					"noBeUserOnline": "' . addslashes($this->translate("module.noBeUserOnline")) . '",
					"ok": "' . addslashes($this->translate("module.ok")) . '",
					"abort": "' . addslashes($this->translate("module.abort")) . '",
					"assumeToTitle": "' . addslashes($this->translate("module.assumeToTitle")) . '"
				}
				var fixText = {
					' . $this->createFixTextJsObj() . '
				}

				var theRequest = null;
				var timer = null;
				var strftime = "";

				window.addEvent("domready", function() {
					initChat(' . $this->ajaxGetAllFreq . ',' . $this->useTypingIndicator . ');
				});
			// -->
			/*]]>*/
			</script>
		';
        return ($jsCode);
    }

    /**
     * Create FixText javascript object
     *
     * @return bool|string
     */
    private function createFixTextJsObj()
    {
        global $BE_USER;
        $fixText = $BE_USER->userTS["supportchat."]["fixText."];
        $jsCode = '';
        if (is_array($fixText)) {
            foreach ($fixText as $key => $val) {
                $jsCode .= '
					"' . substr($key, 0, -1) . '": {
				';
                foreach ($val as $keyInner => $valInner) {
                    $jsCode .= '
						"' . $keyInner . '": "' . $valInner . '",';
                }
                $jsCode = substr($jsCode, 0, -1);
                $jsCode .= '
					},';
            }
            $jsCode = substr($jsCode, 0, -1);
        }
        return ($jsCode);
    }

    /**
     * Returns the LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     */
    protected function initializeView(ViewInterface $view)
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation([]);
    }

    /**
     * Menu config
     *
     * @deprecated
     */
    protected function menuConfig()	{
        $this->MOD_MENU = array (
            'function' => array (
                '1' => $GLOBALS['LANG']->getLL('function1'),
            )
        );
        $MCONF = array();
        $MCONF['name'] = $this->getBackendUser()->groupData['modules'];
        $MCONF['script'] = '_DISPATCH';
        $MCONF['_'] = 'mod.php?M=' .  $this->getBackendUser()->groupData['modules'];
        $MCONF['access'] = 'user,group';
        if (!$this->MCONF['name']) {
            $this->MCONF = $MCONF;
        }
    }
}
