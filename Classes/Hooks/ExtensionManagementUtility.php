<?php
/**
 * Class ExtensionManagementUtility
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
namespace Ubl\SupportChat\Hooks;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility as BaseExtensionManagementUtility;

/**
 * Hook for \TYPO3\CMS\Core\Utility\ExtensionManagementUtility
 *
 * @package Ubl\SupportChat\Hooks
 */
class ExtensionManagementUtility extends BaseExtensionManagementUtility
{
    /**
     * Add plugin to static template for css_styled_content
     * @see \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43()
     *
     * @param string $key: The extension key
     * @param string $class: The qualified class name
     * @param string $suffix: The uid of the record
     * @param string $type: Determines the type of the frontend plugin
     * @param bool $cached: Should we created a USER object instead of USER_INT?
     *
     * @return void
     * @access public
    */
    public static function addPItoST43($key, $class, $suffix = '', $type = 'list_type', $cached = false)
    {
        $internalName = 'tx_' . $key . '_' . strtolower(self::getUnqualifiedClassName($class));
        // General plugin
        $typoscript = 'plugin.' . $internalName . ' = USER' . ($cached ? '' : '_INT') . "\n";
        $typoscript .= 'plugin.' . $internalName . '.userFunc = ' . $class . '->main' . "\n";
        parent::addTypoScript($key, 'setup', $typoscript);
        // Add after defaultContentRendering
        switch ($type) {
            case 'list_type':
                $addLine = 'tt_content.list.20.' . $key . $suffix . ' = < plugin.' . $internalName;
                break;
            default:
                $addLine = '';
        }
        if ($addLine) {
            parent::addTypoScript($key, 'setup', $addLine, 'defaultContentRendering');
        }
    }

    /**
     * Get the unqualified name of a class
     *
     * @param string $qualifiedClassname: The qualified class name from get_class()
     *
     * @return string The unqualified class name
     * @access public
     */
    public static function getUnqualifiedClassName($qualifiedClassname)
    {
        $nameParts = explode('\\', $qualifiedClassname);
        return end($nameParts);
    }
}
