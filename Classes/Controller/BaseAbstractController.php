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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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
     * Get database handle
     *
     * @return \TYPO3\Cms\Core\Database\DatabaseConnection
     * @access protected
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Helper function to use localized strings in controllers
     *
     * @param string $key            locallang key
     * @param string $defaultMessage the default message to show if key was not found
     * @return string
     * @access protected
     */
    protected function translate($key, $defaultMessage = '')
    {
        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
            $key,
            strtolower($this->extensionName)
        );
        return ($message == null) ? $defaultMessage : $message;
    }

}