<?php
if(!defined( 'TYPO3_MODE')) {
    die ('Access denied.');
}

/*\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'supportchat',
    'Configuration/TypoScript/',
    'Support Chat'
);*/

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['supportchat'] = 'layout,select_key';