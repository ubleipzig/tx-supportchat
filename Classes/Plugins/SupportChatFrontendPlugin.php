<?php
/**
 * Class SupportChatFrontendPlugin
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

namespace Ubl\Supportchat\Plugin;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility as Localization;
use Ubl\Supportchat\Library\Chat;
use Ubl\Supportchat\Library\ChatHelper;

/**
 * Class FrontendPlugin
 *
 * Support Chat frontend plugin
 *
 * @package Ubl\SupportChat\Plugin
 */
class SupportChatFrontendPlugin
{
    /**
     * Prefix id
     *
     * @var string
     * @access public
     */
    public $prefixId = 'tx_supportchat_pi1';	// Same as class name

    /**
     * Path to this script relative to the extension dir
     *
     * @var string
     * @access public
     */
    public $scriptRelPath = 'pi1/class.tx_supportchat_pi1.php';	//

    /**
     * The extension key
     *
     * @var string
     * @access public
     */
    public $extKey = 'supportchat';

    /**
     * The extension configuration
     *
     * @var object
     * @access public
     */
    public $extConf;

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
     * The main method of the plugIn
     *
     * @params string $content  The PlugIn content
     * @params array $conf      The PlugIn configuration
     *
     * @return string   The content that is displayed on the website
     */
    public function main($content, $conf)	{
        $this->conf = $conf;
        $this->pi_setPiVarDefaults(); // @deprecated - not set not used properly
        $this->pi_loadLL();
        $this->extConf = (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9.0', '<='))
            ? $this->getObjectManager()
                ->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility')
                ->getCurrentConfiguration($this->extKey)
            : GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
                ->get($this->extKey);

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
                if ($sessionId) {
                    $chatIsOnline = ChatHelper::checkIfChatIsOnline($this->checkPids);
                    if ($chatIsOnline[$this->conf["chatPluginPid"]]) {
                        // tx_chat_functions::destroyInactiveChats($this->conf["timeToInactivateChatIfNoMessages"],$this->conf["chatsPid"]);
                        $chat = new Chat();
                        /** tradem 2012-04-12 Pass typing inidcator usage configuration */
                        // $chat->writeLog("Frontend : useTypingIndicator=[" .$this->useTypingIndicator. "]");
                        $chat->initChat($this->conf["chatsPid"], "", false, $this->useTypingIndicator);
                        $chat->destroyInactiveChats($this->conf["timeToInactivateChatIfNoMessages"]);
                        $this->addJsInHeader($sessionId, $chatUid);
                        $content = $this->generateChatBox();
                    } else {
                        $content = $this->showChatIsOfflineMessage();
                    }
                } else {
                    $content = $this->noJsOrCookie();
                }
                break;
            default:
                $this->addJsInHeaderForCheckIfChatIsOnline();
                $content = $this->showSupportButton();
        }
        if (trim($this->conf["_CSS_DEFAULT_STYLE"]))	{
            $GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1_css'] =
                '<style type="text/css">'.$this->conf["_CSS_DEFAULT_STYLE"].'</style>';
        }
        return $this->pi_wrapInBaseClass($content);
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
    public function showChatIsOfflineMessage()
    {
        $out = $this->cObj->getSubpart($this->templateCode, '###CHAT_BOX_OFFLINE###');
        $markerArray = [
            "###TITLE###" => Localization::translate("chat-offline-title", $this->extKey),
            "###MESSAGE###" => Localization::translate("chat-offline-message", $this->extKey),
        ];
        $content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);
        return ($content);
    }

    /**
     * Generates the ChatBox
     *
     * @return string $content  Html output
     */
    public function generateChatBox()
    {
        $out = $this->cObj->getSubpart($this->templateCode, '###CHAT_BOX###');
        $markerArray = [
            "###TITLE###" =>
                Localization::translate("chatbox-welcome", $this->extKey),
            "###TITLE_ID###" => 'chatboxTitle',
            "###CHATBOX_STYLE###" => "display: none;",
            "###SEND_LABEL###" =>
                Localization::translate("chatbox-sendmessage", $this->extKey),
            "###MESSAGE_LABEL###" =>
                Localization::translate("chatbox-messagelabel", $this->extKey),
            "###CHAT_BOX_ID###" => "supportchatbox",
            "###TEXTBOX_ID###" => "textBox",
            "###SEND_ID###" => "sendMessage",
            "###MESSAGE###" => "",
            "###CLOSE_ID###" => "chatClose",
            "###CLOSE_LABEL###" =>
                Localization::translate("chatbox-close", $this->extKey),
            "###ERROR###" =>
                Localization::translate("noJsOrCookies-text", $this->extKey),
            "###EXPORT_TEXT###" =>
                Localization::translate("chatbox-export", $this->extKey),
            "###EXPORT_ACTION_URL###" =>
                $this->getAbsUrl('index.php?eID=tx_supportchat_pi1&cmd=createChatLog')
        ];
        $content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);
        return $content;
    }

    /**
     * Adds the JS Code for the SupportLogo to the Header
     *
     * @return void     Adds JS code to '$GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1']'
     * @access public
     */
    public function addJsInHeaderForCheckIfChatIsOnline() {

        $onload = '';

        $GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1'] = '<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath('supportchat').'Resources/Public/JavaScript/Prototype.js"></script>';
        $jsCheckPids = $this->checkForOnlineOfflinePages();

        if ($jsCheckPids) {

            $GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1'] .= '<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/SupportchatIsOnline.js').'"></script>';
            $onLoad = '
                Event.observe(window, "load", function() { initOnlineCheck("'.$this->getAbsUrl('index.php?eID=tx_supportchat_pi1').'"); });
            ';
        }
        $GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1'] .= '
            <script type="text/javascript">
            /*<![CDATA[*/
            <!--
                var globFreq = '.$this->conf["checkIfChatIsOnline"].';
                var checkPids = "'.$jsCheckPids.'";
                '.$onLoad.'
            // -->
            /*]]>*/
            </script>
        ';
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
    public function addJsInHeader($sessionId, $chatUid)
    {
        $pid = $this->conf["chatsPid"] ? $this->conf["chatsPid"] : $GLOBALS["TSFE"]->id;
        $lang = intval(GeneralUtility::_GET("L")) ? "&L=".intval(GeneralUtility::_GET("L")) : "";
        $freq = $this->conf["getMessagesInSeconds"] * 1000;
        /* tradem 2012-04-11 Added JS-Variable for typing indicator */
        $useTypingIndicator =  $this->useTypingIndicator;
        $chatUsername = $GLOBALS["TSFE"]->fe_user->user["uid"]
            ? ($GLOBALS["TSFE"]->fe_user->user["first_name"]
                ? ($GLOBALS["TSFE"]->fe_user->user["first_name"]." ".$GLOBALS["TSFE"]->fe_user->user["last_name"])
                : addslashes($GLOBALS["TSFE"]->fe_user->user["name"]))
            : addslashes(Localization::translate("chat-username", $this->extKey));
        $GLOBALS['TSFE']->additionalHeaderData['tx_supportchat_pi1'] = '
			<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/MootoolsCore.js"></script>
			<script type="text/javascript" src="'.ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/MootoolsMore.js"></script>
			<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/Smileys.js').'"></script>
			<script type="text/javascript" src="'.GeneralUtility::createVersionNumberedFilename(ExtensionManagementUtility::siteRelPath('supportchat') . 'Resources/Public/JavaScript/Supportchat.js').'"></script>
			<script type="text/javascript">
			/*<![CDATA[*/
			<!--
				var chatUid = 0;
				var globFreqMessages = '.$freq.';
				var globPid = '.$pid.';
				var globLang = "'.$lang.'";
				var fe_user_name = "'.$GLOBALS["TSFE"]->fe_user->user["name"].'";
				var timeFormated = "'.strftime($this->conf["strftime"],time()).'";
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
					initChat("'.$this->getAbsUrl('index.php?eID=tx_supportchat_pi1').'");
				});
				window.onbeforeunload = function() {
					chat.destroyChat();
					if(typeof(close_button_flag) == \'undefined\') {
					    alert("'.str_replace('\\\\', '\\', addslashes(Localization::translate("system-chat-byebye-alert", $this->extKey))).'");
					}
				}
			// -->
			/*]]>*/
			</script>
		';
        return;
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
        $out = $this->cObj->getSubpart($this->templateCode, '###SHOW_SUPPORT_LOGO###');
        // get the offline Variant
        $image = '<img src="'.ChatHelper::getPath($this->conf["offlineLogo"]).'" alt="Support Chat Offline" title="Support Chat Offline" />';
        $markerArray = [
            "###TITLE###" =>
                Localization::translate("support-logo-header", $this->extKey),
            "###IMAGE###" => $this->cObj->stdWrap($image,$this->conf["offlineLogo."]["stdWrap."]),
            "###STATUS_MSG###" =>
                Localization::translate("status_msg_offline", $this->extKey)
        ];
        if ($chatIsOnline[$this->conf["chatPluginPid"]]) {
            $onlineClass = "";
            $offlineClass = 'class="hidden"';
        }
        else {
            $onlineClass = 'class="hidden"';
            $offlineClass = "";
        }
        $offline = '<div '.$offlineClass.' id="tx_supportchat_pi1_offlineLogo_'.$this->conf["chatPluginPid"].'">'.$this->cObj->substituteMarkerArrayCached($out,$markerArray).'</div>';
        // get the online Variant
        $image = '<img src="'.ChatHelper::getPath($this->conf["onlineLogo"]).'" alt="Support Chat Online" title="Support Chat Online" />';
        $linkConf = [
            "parameter" => $this->conf["chatPluginPid"],
            "linkAccessRestrictedPages" => 1,
            "additionalParams" => "&tx_supportchat_pi1[cmd]=openChat",
            "returnLast" => "url"
        ];
        $openChatLink = $this->getAbsUrl($this->cObj->typolink("",$linkConf));
        $markerArray = [
            "###TITLE###" => $this->pi_getLL("support-logo-header"),
            "###IMAGE###" => '<a href="'.$this->pi_getPageLink($this->conf["chatNotSupportedPage"]).'" onclick="supportChatOpenWindow(\''.$openChatLink.'\',\'supportchatwindow\',\''.$this->conf["chatWindowJsParams"].'\'); return false;" target="_blank">'.$image.'</a>',
            "###STATUS_MSG###" => $this->pi_getLL("status_msg_online")
        ];
        $online = '<div '.$onlineClass.' id="tx_supportchat_onlineLogo_'.$this->conf["chatPluginPid"].'">'.$this->cObj->substituteMarkerArrayCached($out,$markerArray).'</div>';
        $content = $online.$offline;
        return $content;
    }

    /**
     * Return comma separated list of pages which this plugin instance should check if offline or online
     *
     * @params boolean $forceThisUid
     *
     * @return int $checkPids
     * @access public
     */
    public function checkForOnlineOfflinePages($forceThisUid = false)
    {
        if (trim($this->conf["checkIfChatIsOnlinePids"])) {
            if (trim($this->conf["checkIfChatIsOnlinePids"]) == "this") {
                $checkPids = $this->conf["chatPluginPid"];
            } else {
                $checkPids = trim($this->conf["checkIfChatIsOnlinePids"]);
            }
        } else {
            $checkPids = 0;
        }
        if ($forceThisUid) {
            $checkPids = $this->conf["chatPluginPid"];
        }
        return $checkPids;
    }

    /**
     * Checks if the Surfer has JS enabled and if a sessionID exists
     *
     * @return mixed  SessionId or zero if no javascript or no sessionId
     * @access public
     */
    public function checkJS()
    {
        if (!$GLOBALS['TSFE']->fe_user->id || GeneralUtility::_GET("noJs")) {
            return 0;
        } else {
            return $GLOBALS['TSFE']->fe_user->id;
        }
    }

    /**
     * Shows an error message that Cookies and Javascript must be enabled
     *
     * @return string $content  HTML output
     * @access public
     */
    public function noJsOrCookie()
    {
        $out = $this->cObj->getSubpart($this->templateCode, '###NO_JS_OR_COOKIES_ENABLED###');
        $markerArray = [
            "###TITLE###" => Localization::translate("noJsOrCookies-title", $this->extKey),
            "###TEXT###" => Localization::translate("noJsOrCookies-text", $this->extKey),
        ];
        $content = $this->cObj->substituteMarkerArrayCached($out,$markerArray);
        return $content;
    }

    /**
     * Checks if typing indicator is enabled in configuration or not.
     * Defaults to true.
     *
     * @return boolean   Returns true or false
     * @access public
     */
    public function loadUsingTypingIndicator()
    {
        return ($this->conf["useTypingIndicator"]) ? true : false;
    }

    /**
     * Try to get absolute URL to link
     *
     * @params string $link
     *
     * @return string $url Absolute URL
     * @access public
     */
    public function getAbsUrl($link)
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