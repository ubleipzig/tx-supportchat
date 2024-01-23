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

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Exception;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Lang\LanguageService;

use Ubl\Supportchat\Library\BackendUserHelper;
use Ubl\Supportchat\Library\Chat;
use Ubl\Supportchat\Library\ChatHelper;
use Ubl\Supportchat\Library\ChatMarket;

/**
 * Class SupportChatModuleController
 *
 * Support Chat backend action controller
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
    private $showLogBox = true;

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
     * @throws \Exception    If no chat pid given.
     *
     */
    public function initializeAction()
    {
        $tsConfig = $this->getBackendUser()->getTSConfig();
        $this->chatsPid = $tsConfig["supportchat."]["chatsPid"];
        if (!$this->chatsPid) {
            throw new \Exception(
                'For your backend-user TS-Config variable: "supportchat.chatsPid" is missing.
                Check if the variable is set and you have the right to access the chat.'
            );
        }

        $this->defLang = ($tsConfig["supportchat."]["defLang"]) ?: $this->defLang;
        $this->useTypingIndicator =
            ($tsConfig["supportchat."]["useTypingIndicator"]) ?: $this->useTypingIndicator;
        $this->ajaxGetAllFreq = ($tsConfig["supportchat."]["ajaxGetAllFreq"])
            ? $tsConfig["supportchat."]["ajaxGetAllFreq"] * 1000
            : $this->ajaxGetAllFreq * 1000;
        $this->timeToInactivateChat = ($tsConfig["supportchat."]["timeToInactivateChatIfNoMessages"])
                ?: $this->timeToInactivateChat;
        $this->playAlert = ($tsConfig["supportchat."]["playAlert"]) ?: $this->playAlert;
        $this->showLogBox = ($tsConfig["supportchat."]["showLogBox"]) ?: $this->showLogBox;
        $this->beUserName = ($this->getBackendUser()->user["realName"]) ?: $this->getBackendUser()->user["username"];
        // Get id
        $this->id = (int)GeneralUtility::_GET('id');
        // The page will show only if there is a valid page and if this page may be viewed by the user
        $this->pageInformation =
            BackendUtility::readPageAccess($this->id, $this->getBackendUser()->getPagePermsClause(1));
    }

    /**
     * Index action
     *
     */
    public function indexAction()
    {
        $chat = new Chat();
        $chat->initChat($this->chatsPid, "");
        $chat->destroyInactiveChats($this->timeToInactivateChat);
        // @to-do check functionality of migrated method
        BackendUtility::getPagesTSconfig($this->id)["mod."][($GLOBALS["MCONF"]["name"])];

        $content = $this->getAudioAlertViewSnippet(); // $contentPlayAlert;
        $content .= $this->addJsInlineCode();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $moduleUrl = $uriBuilder->buildUriFromRoute($this->request->getPluginName());

        $this->view->assignMultiple([
            'content' => $content,
            'moduleUrl' => $moduleUrl,
            'frequencyOfChatRequest' => $this->ajaxGetAllFreq,
            'isTypeIndicator' => $this->useTypingIndicator,
            'showLogBox' => $this->showLogBox
        ]);
    }

    /**
     * Get chat ajax action
     *
     * @return ResponseInterface
     * @access public
     */
    public function getChatAction() : ResponseInterface
    {
        $xmlArray = [];
        if (!$this->getBackendUser()->user["uid"]) {
            $xmlArray = [
                "fromNoAccess" => [
                    "time" => ChatHelper::renderTstamp(time()),
                ]
            ];
            return GeneralUtility::makeInstance(
                JsonResponse::class,
                $xmlArray,
                200
            );
        }
        $tsConfig = $this->getBackendUser()->getTSConfig();
        // user ts chatsPid
        $this->chatsPid = $tsConfig["supportchat."]["chatsPid"];
        // user ts enableLogging
        if ($tsConfig["supportchat."]["enableLogging"] != "") {
            $this->logging = $tsConfig["supportchat."]["enableLogging"];
        }
        // user ts useTypingIndicator
        if ($tsConfig["supportchat."]["useTypingIndicator"]) {
            $this->useTypingIndicator = $tsConfig["supportchat."]["useTypingIndicator"];
        }
        $this->lastRowArray = (GeneralUtility::_GP("lastRowArray")) ?: [];
        if (GeneralUtility::_GP("cmd")) {
            $this->cmd = GeneralUtility::_GP("cmd");
        }
        $this->lastLogRow = ((int)(GeneralUtility::_GP("lastLogRow"))) ?: 0;
        $this->uid = ((int)(GeneralUtility::_GP("chatUid"))) ?: 0;
        $chatMarket = new ChatMarket($this->logging, $this->lastLogRow);
        $chatMarket->initChat(
            $this->chatsPid,
            $this->getBackendUser()->user["uid"],
            true,
            $this->useTypingIndicator
        );
        switch($this->cmd) {
            case 'doAll':
                // get all chats,messages,time,be_user
                $msgToSend = GeneralUtility::_GP("msgToSend");
                $lockChats = GeneralUtility::_GP("lockChat");
                $destroyChats = GeneralUtility::_GP("destroyChat");
                $typingStatus = GeneralUtility::_GP("typingStatus");
                $xmlArray = [
                    "fromDoAll" => [
                        "time" => ChatHelper::renderTstamp(time()),
                        "chats" => $chatMarket->doAll(
                            $this->lastRowArray,
                            $msgToSend,
                            $lockChats,
                            $destroyChats,
                            $typingStatus
                        ),
                        "log" => $chatMarket->getLogMessages(),
                        "lastLogRow" => $chatMarket->lastLogRow,
                        "beUsers" => BackendUserHelper::getBackendUsers(
                            $this->getBackendUser()->user["uid"]
                        ),
                    ]
                ];
        }
        return GeneralUtility::makeInstance(
            JsonResponse::class,
            $xmlArray,
            200
        );
    }

    /**
     * Set alert sound to cache
     *
     * @return ResponseInterface
     * @access public
     */
    public function setAlertSoundAction() : ResponseInterface
    {
            if ($alertSound = (GeneralUtility::_GP("alertSound"))) {
                $this->getBackendUser()->getSessionData('tx_supportchat');
                $sessionData['alertsound'] = $alertSound;
                $this->getBackendUser()->setAndSaveSessionData('tx_supportchat', $sessionData);
                $data = [
                    "sound" => $alertSound,
                    "success" => "true"
                ];
                return GeneralUtility::makeInstance(
                    JsonResponse::class,
                    $data,
                    200
                );
            } else {
                return false;
            }
    }

    /**
     * Return inline javacode
     *
     * @return string $jsCode
     * @access private
     */
    private function addJsInlineCode()
    {
        $jsCode = '
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
				var timer = null;
				var strftime = "";
                
                function domReady(fn) {
                    // see if DOM is already available
                    if (document.readyState === "complete" || document.readyState === "interactive") {
                        // call on next available tick
                        setTimeout(fn, 1);
                    } else {
                        document.addEventListener("DOMContentLoaded", fn);
                    }
                }    
				domReady(function() {
					initChat(' . $this->ajaxGetAllFreq . ',' . $this->useTypingIndicator . ');
				});
			//-->
			/*]]>*/
			</script>
		';
        return ($jsCode);
    }

    /**
     * Create buttons
     *
     * @return void
     * @access private
     */
    private function createButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        // Shortcut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName('tx_supportchat_M1')
                ->setGetVariables(['route', 'module', 'id'])
                ->setDisplayName('Shortcut');
            $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }
    }

    /**
     * Create FixText javascript object
     *
     * @return bool|string
     */
    private function createFixTextJsObj()
    {
        $tsConfig = $this->getBackendUser()->getTSConfig();
        $fixText = $tsConfig["supportchat."]["fixText."];
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
     * Returns the audio alert html snippet
     *
     * @return
     * @access private
     */
    private function getAudioAlertViewSnippet()
    {
        if (isset($this->playAlert) && $this->playAlert == 1) {
            $sounds = array_diff(
                scandir(ExtensionManagementUtility::extPath('supportchat') . 'Resources/Public/media'),
                ['..','.']

            );
            $options = '';
            $sessionData = $this->getSessionData('tx_supportchat');
            $alertSound = ($sessionData['alertsound'] && ($sessionData['alertsound'] != ""))
                ? $sessionData['alertsound'] : reset($sounds);
            //echo "Das ist " . $alertSound;
            foreach ($sounds as $sound) {
                $options .= '<option value="' . $sound . '" ' . (($sound == $alertSound) ? 'selected="selected"' : '') . '>'. pathinfo($sound, PATHINFO_FILENAME) .'</option>';
            }
            $snippetPlayAlert = '
				<audio id="beep_alert" class="flash" width="1" height="1">
					<source src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL')
                        . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('supportchat'))
                        . 'Resources/Public/media/'. $alertSound . '" type="audio/ogg"
					/>
				</audio>
				<p class="support-chat-alert-options">
				    <label for="alert-select">' . $this->translate("module.selectAlertSound") . ':</label>
				    <select id="alert-select" name="alert-select">
				        ' . $options . '
                    </select>
                    <label for="alert_check" class="alert-check">' . $this->translate("module.playAlert") . ':</label>
                    <input type="checkbox" checked="checked" id="alert_check" />
                </p>
			';
        }
        return ($snippetPlayAlert) ?: '';
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
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->setTitle($this->translate("title"));
        $pathToCssLibrary = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('supportchat')) . 'Resources/Public/Css/Backend/';
        $pageRenderer->addCssFile($pathToCssLibrary . 'module-chat.css');
        $pageRenderer->addJsInlineCode(
            'assets',
            'script_ended = 0;
            function jumpToUrl(URL)	{
                document.location = URL;
            }
            let assetsPath = "' . GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('supportchat')) . 'Resources/Public/' . '"'
        );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Supportchat/SupportchatBackendAlert');
        $pathToJsLibrary = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . PathUtility::stripPathSitePrefix(ExtensionManagementUtility::extPath('supportchat')) . 'Resources/Public/JavaScript/';
        $pageRenderer->addJsFile($pathToJsLibrary . 'Smileys.js');
        $pageRenderer->addJsFile($pathToJsLibrary . 'SupportchatBackend.js');

        $this->createButtons();
        $this->menuConfig();
    }

    /**
     * Menu config
     *
     * @deprecated
     * @access private
     */
    private function menuConfig()
    {
        $this->MOD_MENU = [
            'function' => [
                '1' => $GLOBALS['LANG']->getLL('function1'),
            ]
        ];
        $MCONF = [];
        $MCONF['name'] = $this->getBackendUser()->groupData['modules'];
        $MCONF['script'] = '_DISPATCH';
        $MCONF['_'] = 'mod.php?M=' .  $this->getBackendUser()->groupData['modules'];
        $MCONF['access'] = 'user,group';
        if (!$this->MCONF['name']) {
            $this->MCONF = $MCONF;
        }
    }
}
