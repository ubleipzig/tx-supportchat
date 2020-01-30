<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_supportchat_chats");

$TCA["tx_supportchat_chats"] = array(
    "ctrl" => array(
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/language/locallang_db.xlf:tx_supportchat_chats',
        'label' => 'be_user',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "delete" => "deleted",
        "enablecolumns" => array(
            "disabled" => "hidden",
        ),
        "dynamicConfigFile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . "tca.php",
        "iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . "icon_tx_supportchat_chats.gif",
    ),
    "feInterface" => array(
        "fe_admin_fieldList" => "hidden, be_user, session, active, last_row_uid",
    )
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_supportchat_messages");

$TCA["tx_supportchat_messages"] = array(
    "ctrl" => array(
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:tx_supportchat_messages',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "dynamicConfigFile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . "tca.php",
        "iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . "icon_tx_supportchat_messages.gif",
    ),
    "feInterface" => array(
        "fe_admin_fieldList" => "chat_pid, name, message",
    )
);


// \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key';


/*\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array('LLL:EXT:' . $_EXTKEY . '/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'),
    'list_type'
);*/

//\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'pi1/static/SupportChat_TS/', 'Support Chat TS');

if (TYPO3_MODE == "BE") {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Ubl.' . $_EXTKEY,
        'user',          // Main area
        'tx_supportchat_M1',  // Name of the module
        '',             // Position of the module
        array(          // Allowed controller action combinations
            'SupportChatModule' => 'index'
        ),
        array(          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/moduleicon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
        )
    );

    $GLOBALS['TBE_STYLES']['skins'][$_EXTKEY]['stylesheetDirectories'] =
        ['EXT:' . $_EXTKEY . '/Resources/Public/css/module-chat.css'];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        $_EXTKEY,
        'Configuration/TypoScript/',
        'Support Chat TS'
    );
}
