<?php
/**
 * Class BaseAbstractController
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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use Ubl\Supportchat\Domain\Repository\ChatsRepository;
use Ubl\Supportchat\Domain\Repository\LogsRepository;
use Ubl\Supportchat\Domain\Repository\MessagesRepository;


/**
 * Class AbstractController
 *
 * Provides common methods to use in all controllers
 *
 * @package Ubl\SupportChat\Controller
 */
abstract class BaseAbstractController extends ActionController
{
    /**
     * chatsRepository
     *
     * @var ChatsRepository
     */
    protected ?ChatsRepository $chatsRepository = null;

    /**
     * logsRepository
     *
     * @var LogsRepository
     */
    protected ?LogsRepository $logsRepository = null;

    /**
     * messagesRepository
     *
     * @var MessagesRepository
     */
    protected ?MessagesRepository $messagesRepository = null;

    /**
     * URI Builder
     *
     * @var uriBuilder
     */
    protected $uriBuilder = null;

    /**
     * Inject ChatsRepository
     *
     * @param ChatsRepository $chatsRepository
     */
    public function injectChatsRepository(ChatsRepository $chatsRepository): void
    {
        $this->chatsRepository = $chatsRepository;
    }

    /**
     * Inject LogsRepository
     *
     * @param LogsRepository $logsRepository
     */
    public function injectLogsRepository(LogsRepository $logsRepository): void
    {
        $this->logsRepository = $logsRepository;
    }

    /**
     * Inject MessagesRepository
     *
     * @param MessagesRepository $messagesRepository
     */
    public function injectMessagesRepository(MessagesRepository $messagesRepository): void
    {
        $this->messagesRepository = $messagesRepository;
    }

    /**
     * Inject UriBuilder
     *
     * @param UriBuilder $uriBuilder
     */
    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Get backend user
     *
     * @return BackendUserAuthentication
     * @access protected
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Get session data
     *
     * Only references to backend module!
     *
     * @param string $key
     *
     * @return array $sessionData
     * @access public
     */
    public function getSessionData($key): array
    {
        $userGlobals = $this->getUserGlobals();
        $sessionData = ($userGlobals->getSessionData($key)) ?
            $userGlobals->getSessionData($key) : [];

        // deprecated because class will only be called ar SupportChatModulController -> getAudioAlertViewSnippet
        //if (TYPO3_MODE === 'BE') {
            $ucData = (isset($userGlobals->uc['moduleData']['supportchat']))
                ? $userGlobals->uc['moduleData']['supportchat'] : [];
            $configurationData = (isset($ucData[$key]))
                ? $ucData[$key] : [];
            if (!empty($configurationData) && !(empty($sessionData))) {
                // merge session and configuration data
                ArrayUtility::mergeRecursiveWithOverrule($sessionData, $configurationData);
            } else if (!empty($configurationData)) {
                // there seems to be only configuration data (after fresh login)
                $sessionData = $configurationData;
            }
        //}
        return $sessionData;
    }

    /**
     * Set session data
     *
     * @param string $key
     * @param string $data
     * @param mixed $persist
     *
     * @return void
     * @access public
     * @deprecated Method is not be used in v11. Please remove at v12.
     */
    public function setSessionData($key, $data, $persist = null): void
    {
        $userGlobals = $this->getUserGlobals();

        // write data to user configuration to persist over sessions
        if ($persist === true /*&& TYPO3_MODE === 'BE'*/) {
            $ucData = $userGlobals->uc['moduleData']['supportchat'];
            $ucData[$key] = $data;
            $userGlobals->uc['moduleData']['supportchat'] = $ucData;
            $userGlobals->writeUC();
            // uncommented Typo3 v11 due to ref. 95320 deprecation of VariousMethodArgumentsInAuthenticationObjects
            //$userGlobals->writeUC($userGlobals->uc);
        }
        $userGlobals->setAndSaveSessionData($key, $data);
    }

    /**
     * Return the corresponding user GLOBALS for FE/BE
     *
     * @return mixed $userGlobals
     */
    protected function getUserGlobals(): mixed
    {
        if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()) {
            $userGlobals = $this->getBackendUser();
        } else if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()) {
            $userGlobals = $GLOBALS['TSFE']->fe_user;
        }
        return $userGlobals;
    }

    /**
     * Helper function to use localized strings in controllers
     *
     * @param string $key            locallang key
     * @param string $defaultMessage the default message to show if key was not found
     *
     * @return string
     * @access protected
     */
    protected function translate($key, $defaultMessage = ''): string
    {
        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
            $key,
            strtolower($this->request->getControllerExtensionName())
        );
        return ($message === null) ? $defaultMessage : $message;
    }
}
