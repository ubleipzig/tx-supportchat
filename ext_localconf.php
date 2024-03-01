<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ubl.' . $_EXTKEY,
    'tx_supportchat',
    [
        'SupportChat' => 'index'
    ],
    // non cache actions
    [
        'SupportChat' => 'index'
    ]
);

$TYPO3_CONF_VARS['FE']['eID_include']['tx_supportchat']
    = \Ubl\Supportchat\Ajax\FrontendListener::class . '::getAjaxResponse';


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TypoScript/setup.txt">'
);

/*
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/',
    'Support Chat TS'
);*/