<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Ubl.supportchat',
    'tx_supportchat',
    [
        \Ubl\Supportchat\Controller\SupportChatController::class => 'index'
    ],
    // non cache actions
    [
        \Ubl\Supportchat\Controller\SupportChatController::class => 'index'
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_supportchat']
    = \Ubl\Supportchat\Controller\AjaxFrontendController::class . '::getAjaxResponse';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:supportchat/Configuration/TypoScript/setup.typoscript">'
);

/*
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    $_EXTKEY,
    'Configuration/TypoScript/',
    'Support Chat TS'
);*/