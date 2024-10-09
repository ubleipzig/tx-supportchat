<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

if (TYPO3_MODE == "BE") {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Ubl.supportchat',
        'user',          // Main area
        'tx_supportchat_M1',  // Name of the module
        '',             // Position of the module
        [          // Allowed controller action combinations
            'SupportChatModule' => 'index'
        ],
        [          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:supportchat/Resources/Public/Icons/module-icon.svg',
            'labels' => 'LLL:EXT:supportchat/Resources/Private/Language/locallang_mod.xlf',
        ]
    );

    $GLOBALS['TBE_STYLES']['skins']['supportchat']['stylesheetDirectories'] =
        ['EXT:supportchat/Resources/Public/css/module-chat.css'];
}
