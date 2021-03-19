<?php
/**
 * Class SupportChatController
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localization;
use Ubl\Supportchat\Library\Chat;
use Ubl\Supportchat\Library\ChatHelper;

/**
 * Class SupportChatController
 *
 * Support Chat frontend action controller
 *
 * @package Ubl\SupportChat\Controller
 */
class SupportChatController extends BaseAbstractController
{
    /**
     * Prefix id
     *
     * @var string
     * @access public
     */
    public $prefixId = 'tx_supportchat_pi1';	// Same as class name

    /**
     * The extension configuration
     *
     * @var object
     * @access public
     */
    public $extConf;

    /**
     * Content Object
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     * @access protected
     */
    protected $cObj;

    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public $extKey = 'supportchat';


    /**
     * @var string
     * @access public
     */
    public $pi_checkCHash = true;

    /**
     * Object manager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @access public
     *
     * @inject
     */
    protected $objectManager;


    /**
     * Initializes the module
     *
     * @return void
     * @throws Exception    If no chat pid given.
     *
     */
    public function initializeAction()
    {
        $this->cObj = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
        $this->extConf = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        parent::initializeAction();
    }

    /**
     * The main method of the PlugIn
     *
     * @params string		$content: The PlugIn content
     * @params array		$conf: The PlugIn configuration
     *
     * @return	The content that is displayed on the website
     */
    public function indexAction()
    {
        $this->checkPids = $this->checkForOnlineOfflinePages(true);
        /** tradem 2012-04-11 Sets typing indicator */
        $this->useTypingIndicator = $this->loadUsingTypingIndicator();
        $this->request = GeneralUtility::_GP('supportchat');
        $cmd = (isset($this->request['cmd']))
            ? filter_var($this->request['cmd'], FILTER_SANITIZE_STRING) : '';
        switch ($cmd) {
            case 'openChat':
                //get sessionId and check if JS is enabled
                $sessionId = $this->checkJS();
                // write something to the session so the session cookie (and id) is not re-created on every browser request (needed since new session handling see bug http://bugs.typo3.org/view.php?id=10205)
                $GLOBALS['TSFE']->fe_user->setKey("ses","supportchat","1");
                if($sessionId) {
                    $chatIsOnline = ChatHelper::checkIfChatIsOnline($this->checkPids);
                    if($chatIsOnline[$this->settings["chatPluginPid"]]) {
                        // tx_chat_functions::destroyInactiveChats($this->conf["timeToInactivateChatIfNoMessages"],$this->conf["chatsPid"]);
                        $chat = new Chat();
                        /** tradem 2012-04-12 Pass typing inidcator usage configuration */
                        // $chat->writeLog("Frontend : useTypingIndicator=[" .$this->useTypingIndicator. "]");
                        $chat->initChat($this->settings["chatsPid"], "", 0, $this->useTypingIndicator);
                        $chat->destroyInactiveChats($this->settings["timeToInactivateChatIfNoMessages"]);
                        $this->addJsInHeader($sessionId, $chatUid);
                        $this->displayChatBox();
                    } else {
                        $this->displayChatIsOffline();
                    }
                } else {
                    $this->displayNoJsOrCookie();
                }
                break;
            default:
                $this->addJsInHeaderForCheckIfChatIsOnline();
                $this->showSupportButton();
        }
        if(trim($this->settings["_CSS_DEFAULT_STYLE"]))	{
            $this->view->assign(
                'chatCss',
                '<style type="text/css">'.$this->settings["_CSS_DEFAULT_STYLE"].'</style>'
            );
        }
        //return $this->pi_wrapInBaseClass($content);
    }

    /**
     * Return object manager object
     *
     * @return object|\TYPO3\CMS\Extbase\Object\ObjectManager
     */
    public function getObjectManager()
    {
        if (!$this->objectManager) {
            $this->objectManager =
                GeneralUtility::makeInstance('TYPO3\\CMS\\ExtBase\\Object\\ObjectManager');
        }
        return $this->objectManager;
    }

    /**
     * Render template with message that the Chat is offline
     *
     * @return string $content  Html output
     * @access public
     */
    public function displayChatIsOffline()
    {
        return $this->view->assign('isChatOffline', true);
    }

    /**
     * Generates the ChatBox
     *
     * @return string $content  Html output
     */
    public function displayChatBox()
    {
        return $this->view->assignMultiple([
            'isChatbox' => true,
            'title' => 'chatbox-welcome',
            'title-id' => 'chatboxTitle',
            'chatbox-style' => 'display: none;',
            'message-label' => 'chatbox-messagelabel',
            'chatbox-id' => 'supportchatbox',
            'textbox-id' => 'textBox',
            'send-id' => 'sendMessage',
            'send-label' => 'chatbox-sendmessage',
            'message' => '',
            'close-id' => 'chatClose',
            'close-label' => 'chatbox-close',
            'error' => 'noJsOrCookies-text',
            'export-text' => 'chatbox-export',
            'export-action-url' =>
                $this->getAbsUrl('index.php?eID=tx_supportchat_pi1&cmd=createChatLog')
        ]);
    }

    /**
     * Adds the JS Code for the SupportLogo to the Header
     *
     * @return void     Assign javascript 'headerJS' to view
     * @access public
     *
     */
    public function addJsInHeaderForCheckIfChatIsOnline()
    {
        $onload = '';

        $content = '<script type="text/javascript" src="' . ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/Javascript/Prototype.js"></script>';
        $jsCheckPids = $this->checkForOnlineOfflinePages();

        if ($jsCheckPids) {
            $content .= '<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/SupportchatIsOnline.js').'"></script>';
            $onLoad = '
	    		Event.observe(window, "load", function() { initOnlineCheck("'.$this->getAbsUrl('index.php?eID=tx_supportchat').'"); });
			';
        }
        $content .= '
            <script type="text/javascript">
            /*<![CDATA[*/
            <!--
                var globFreq = '.$this->settings["checkIfChatIsOnline"].';
                var checkPids = "'.$jsCheckPids.'";
                '.$onLoad.'
            //-->
            /*]]>*/
            </script>
        ';
        $this->view->assign('headerJs', $content);
    }

    /**
     * Adds the JS - AJAX Code in the <head> section of the template
     * Includes the needed JS files.
     *
     * @params string $sessionId    The identification for the Server
     * @params int $chatUid         The chat-uid for this surfer
     *
     * @return void
     * @access public
     */
    public function addJsInHeader($sessionId, $chatUid) {
        $pid = $this->settings["chatsPid"]
            ? $this->settings["chatsPid"] : $GLOBALS["TSFE"]->id;
        $lang = intval(GeneralUtility::_GET("L"))
            ? "&L=".intval(GeneralUtility::_GET("L")) : "";
        $freq = $this->settings["getMessagesInSeconds"] * 1000;
        /* tradem 2012-04-11 Added JS-Variable for typing indicator */
        $useTypingIndicator =  $this->useTypingIndicator;
        $chatUsername = $GLOBALS["TSFE"]->fe_user->user["uid"]
            ? ($GLOBALS["TSFE"]->fe_user->user["first_name"]
                ? ($GLOBALS["TSFE"]->fe_user->user["first_name"]." ".$GLOBALS["TSFE"]->fe_user->user["last_name"])
                : addslashes($GLOBALS["TSFE"]->fe_user->user["name"]))
            : addslashes(Localization::translate("chat-username", $this->extKey));
        $jsInHeader = '
			<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath('supportchat').'Resources/Public/JavaScript/MootoolsCore.js"></script>
			<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath('supportchat').'Resources/Public/JavaScript/MootoolsMore.js"></script>
			<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat').'Resources/Public/JavaScript/Smileys.js').'"></script>
			<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat').'Resources/Public/JavaScript/Supportchat.js').'"></script>
			<script type="text/javascript">
			/*<![CDATA[*/
			<!--
			    var chatUid = 0;
				var globFreqMessages = '.$freq.';
				var globPid = '.$pid.';
				var globLang = "'.$lang.'";
				var fe_user_name = "'.$GLOBALS["TSFE"]->fe_user->user["name"].'";
				var timeFormated = "'.strftime($this->settings["strftime"],time()).'";
				var useTypingIndicator = '.$useTypingIndicator.'; // tradem 2012-04-11 Added JS-Variable for typing indicator
				var diffLang = {
					\'chatboxTitleBeUserOk\': \''.addslashes(Localization::translate("chatbox-title-be-user-ok", $this->extKey)).'\',
					\'chatboxWelcome\': \''.addslashes(Localization::translate("chatbox-welcome", $this->extKey)).'\',
					\'chatUsername\': \''.$chatUsername.'\',
					\'systemByeBye\': \''.addslashes(Localization::translate("system-chat-byebye", $this->extKey)).'\',
					\'systemSupportlerJoinedChat\': \''.addslashes(Localization::translate("system-supportler-joined-chat", $this->extKey)).'\',
					\'systemSupportlerLeavedChat\': \''.addslashes(Localization::translate("system-supportler-leaved-chat", $this->extKey)).'\',
					\'chatWelcome\': \''.addslashes(Localization::translate("chatbox-entry-welcome", $this->extKey)).'\',
					\'system\': \''.addslashes(Localization::translate("system-name", $this->extKey)).'\',
					\'chatTimeout\': \''.addslashes(Localization::translate("chatTimeout", $this->extKey)).'\',
					\'chatDestroyedByAdmin\': \''.addslashes(Localization::translate("chatDestroyedByAdmin", $this->extKey)).'\',
					\'chatNoAccess\': \''.addslashes(Localization::translate("chatNoAccess", $this->extKey)).'\'
				};
				window.addEvent("domready", function() {
					initChat("'.$this->getAbsUrl('index.php?eID=tx_supportchat').'");
				});
				window.onbeforeunload = function() {
					chat.destroyChat();
					if(typeof(close_button_flag) === \'undefined\') {
					    alert("'.str_replace('\\\\', '\\', addslashes(Localization::translate("system-chat-byebye-alert", $this->extKey))).'");
					}
				}
			// -->
			/*]]>*/
			</script>
		';
        $this->view->assign('headerJs', $jsInHeader);
    }

    /**
     * Shows the Support Button - 2 ways - online or offline Logo and Link from TS
     *
     * @return string $content  Html outputt
     * @access public
     */
    public function showSupportButton()
    {
        // check if Chat is online or offline (if page where chat is stored is hidden or not)
        $chatIsOnline = ChatHelper::checkIfChatIsOnline($this->checkPids);
        if($chatIsOnline[$this->settings["chatPluginPid"]]) {
            $onlineClass = "";
            $offlineClass = 'class="hidden"';
        }
        else {
            $onlineClass = 'class="hidden"';
            $offlineClass = "";
        }

        //$out = $this->cObj->getSubpart($this->templateCode, '###SHOW_SUPPORT_LOGO###');
        // get the offline Variant
        $image = '<img src="'.ChatHelper::getPath($this->settings["offlineLogo"]).'" alt="Support Chat Offline" title="Support Chat Offline" />';

        $offlineOnline[] = [
            'offlineOnlineClass' => $offlineClass,
            'ChatPluginPid' => $this->settings['chatPluginPid'],
            'offlineOnlineTitle' => 'support-logo-header',
            'offlineOnlineImage' => $image,
            'offlineOnlineStatusMsg' => 'status_msg_offline'
        ];

        // get the online Variant
        $image = '<img src="'.ChatHelper::getPath($this->settings["onlineLogo"]).'" alt="Support Chat Online" title="Support Chat Online" />';
        $linkConf = [
            "parameter" => $this->settings["chatPluginPid"],
            "linkAccessRestrictedPages" => 1,
            "additionalParams" => "&tx_supportchat[cmd]=openChat",
            "returnLast" => "url"
        ];
        $openChatLink = $this->getAbsUrl($this->cObj->typoLink("", $linkConf));

        $link = $this->controllerContext->getUriBuilder()->reset()->setTargetPageUid($this->settings["chatNotSupportedPage"])->buildFrontendUri();
        $link = '<a href="'.$link.'" onclick="supportChatOpenWindow(\''.$openChatLink.'\',\'supportchatwindow\',\''.$this->conf["chatWindowJsParams"].'\'); return false;" target="_blank">'.$image.'</a>';

        $offlineOnline[] = [
            'offlineOnlineClass' => $onlineClass,
            'ChatPluginPid' => $this->settings['chatPluginPid'],
            'offlineOnlineTitle' => 'support-logo-header',
            'offlineOnlineImage' => $link,
            'offlineOnlineStatusMsg' => 'status_msg_online'
        ];

        return $this->view->assign('offlineOnline', $offlineOnline);
    }

    /**
     * Shows an error message that Cookies and Javascript must be enabled
     *
     * @return string $content  HTML output
     * @access public
     */
    public function displayNoJsOrCookie()
    {
        return $this->view->assign('isNoJsOrCookie', true);
    }

    /**
     * Return comma separated list of pages which this plugin instance should check
     * if offline or online
     *
     * @param boolean $forceThisUid
     *
     * @return int
     * @access private
     */
    private function checkForOnlineOfflinePages($forceThisUid = false)
    {
        if(trim($this->settings["checkIfChatIsOnlinePids"])) {
            if(trim($this->settings["checkIfChatIsOnlinePids"]) == "this") {
                $checkPids = $this->settings["chatPluginPid"];
            } else {
                $checkPids = trim($this->settings["checkIfChatIsOnlinePids"]);
            }
        } else {
            $checkPids = 0;
        }
        if($forceThisUid) {
            $checkPids = $this->settings["chatPluginPid"];
        }
        return ($checkPids);
    }

    /**
     * Checks if the Surfer has JS enabled and if a sessionID exists
     *
     * @return string   SessionId or zero if no javascript or no sessionId
     * @access public
     */
    private function checkJS()
    {
        if(!$GLOBALS['TSFE']->fe_user->id || GeneralUtility::_GET("noJs")) {
            return(0);
        } else {
            return($GLOBALS['TSFE']->fe_user->id);
        }
    }

    /**
     * Checks if typing indicator is enabled in configuration or not.
     * Defaults to true.
     *
     * @return boolean     Returns true (1) or false (0)
     * @access private
     */
    private function loadUsingTypingIndicator()
    {
        return ($this->settings["useTypingIndicator"] ? 1 : 0);
    }

    /**
     * Try to get absolute URL to link
     *
     * @param string $link
     *
     * @return string $url Absolute URL
     * @access private
     */
    private function getAbsUrl($link)
    {
        $isAbsRelPrefix = !empty($GLOBALS['TSFE']->absRefPrefix);
        $isBaseURL  = !empty($GLOBALS['TSFE']->baseUrl);
        if ($isBaseURL) {
            $url = $GLOBALS['TSFE']->baseUrlWrap($link);
        } else if ($isAbsRelPrefix) {
            $url = GeneralUtility::locationHeaderUrl($link);
        } else {
            $url = $link;
        }
        return $url;
    }
}