<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
    'supportchat',
    'user',
    'tx_supportchat_M1',
    '',   // Position of the module
    [   // Allowed controller action combinations
        \Ubl\Supportchat\Controller\SupportChatModuleController::class => 'index'
    ],
    [   // Additional configuration
        'access' => 'user,group',
        'icon' => 'EXT:supportchat/Resources/Public/Icons/module-icon.svg',
        'labels' => 'LLL:EXT:supportchat/Resources/Private/Language/locallang_mod.xlf',
    ]
);

$GLOBALS['TBE_STYLES']['skins']['supportchat']['stylesheetDirectories'] =
    ['EXT:supportchat/Resources/Public/css/module-chat.css'];

