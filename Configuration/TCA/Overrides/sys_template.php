<?php
if( !defined( 'TYPO3_MODE' ) ) {
    die ( 'Access denied.' );
}

// Register static typoscript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'supportchat',
    'Configuration/TypoScript/',
    'Support Chat',
    'EXT:supportchat/Resources/Public/Icons/moduleicon.gif'
);
