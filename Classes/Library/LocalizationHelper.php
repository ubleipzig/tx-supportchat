<?php
/**
 * Class LanguageHelper
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

namespace Ubl\Supportchat\Library;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LocalizationHelper
{
    /**
     * Constants of default flag
     *
     * @var string
     */
    const DEFAULT_LANGUAGE_FLAG = "de";

    /**
     * Constants of default language name
     *
     * @var string
     */
    const DEFAULT_LANGUAGE_TITLE = "Deutsch";

    /**
     * Language object
     *
     * @var array $languageObject
     * @access private
     */
    private $languageObject = [];

    /**
     * Gets flag identifier for any given language uid
     *
     * @params int $sys_language_uid
     *
     * @return string $output
     * @access public
     */
    public function getFlagIconByLanguageUid($sys_language_uid)
    {
        foreach ($this->getLanguages() as $value) {
            if ($value['uid'] == $sys_language_uid) {
                return $value['flag'];
            }
        }
        return self::DEFAULT_LANGUAGE_FLAG;
    }

    /**
     * Gets label for any given language uid
     *
     * @params int $sys_language_uid
     *
     * @return string $output
     * @access public
     */
    public function getLabelByLanguageUid($sys_language_uid)
    {
        foreach ($this->getLanguages() as $value) {
            if ($value['uid'] == $sys_language_uid) {
                return $value['title'];
            }
        }
        return self::DEFAULT_LANGUAGE_TITLE;
    }

    /**
     * Get language iso code by language uid
     *
     * @params int $sys_language_uid
     *
     * @return string
     * @access public
     */
    public function getIsoCodeByLanguageUid($sys_language_uid)
    {
        foreach ($this->getLanguages() as $value) {
            if ($value['uid'] == $sys_language_uid) {
                return $value['language_isocode'];
            }
        }
        return "";
    }

    /**
     * Gets the correct flag icon for any given language uid
     *
     * @params int $sys_language_uid
     *
     * @return string $output
     * @access public
     */
    public function getRenderedFlagIconByLanguageUid($sys_language_uid)
    {
        if (($flagIdentifier = $this->getFlagIconByLanguageUid($sys_language_uid)) != "") {
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            return $iconFactory->getIcon(
                'flags-' . $flagIdentifier,
                Icon::SIZE_SMALL
            )->render();
        }
    }

    /**
     * Get all languages
     *
     * @return array $languageObject
     * @access private
     */
    private function getLanguages()
    {
        if (!$this->languageObject) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('pages')
                ->createQueryBuilder();
            $result = $queryBuilder
                ->select('*')
                ->from('sys_language')
                ->execute();
            while ($row = $result->fetch()) {
                $this->languageObject[] = $row;
            }
        }
        return $this->languageObject;
    }
}

