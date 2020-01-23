<?php

if( !defined( 'TYPO3_MODE' ) ) {
    die ( 'Access denied.' );
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'supportchat',
    'Configuration/TypoScript/',
    'SupportChat configuration'
);


/*\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'supportchat',
    'Configuration/TypoScript/setup.txt',
    'Backend TS Config'
);*/