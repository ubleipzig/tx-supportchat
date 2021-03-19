<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == "BE") {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Ubl.' . $_EXTKEY,
        'user',          // Main area
        'tx_supportchat_M1',  // Name of the module
        '',             // Position of the module
        [          // Allowed controller action combinations
            'SupportChatModule' => 'index'
        ],
        [          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/moduleicon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
        ]
    );

    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories'] =
        ['EXT:' . $_EXTKEY . '/Resources/Public/css/module-chat.css'];
}
