<?php
if( !defined( 'TYPO3_MODE' ) ) {
    die ( 'Access denied.' );
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Ubl.supportchat',
    'tx_supportchat',
    'Support Chat',
    'EXT:supportchat/Resources/Public/Icons/moduleicon.gif'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'supportchat',
    'Configuration/TypoScript/',
    'SupportChat configuration'
);

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['supportchat'] = 'layout,select_key';