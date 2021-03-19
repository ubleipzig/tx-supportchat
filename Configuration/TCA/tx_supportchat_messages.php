<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TXEXTKEY = 'tx_' . $EXTKEY;

return [
    "ctrl" => [
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "iconfile" => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/icon_tx_supportchat_messages.gif',
    ],
    "feInterface" => [
        "fe_admin_fieldList" => "chat_pid, name, message",
    ],
    "interface" => [
        "showRecordFieldList" => "chat_pid,name,message"
    ],
    "columns" => [
        "chat_pid" => [
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.chat_pid",
            "config" => [
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_supportchat_chats",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1
            ]
        ],
        "name" => [
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.name",
            "config" => [
                "type" => "input",
                "size" => "30"
            ]
        ],
        "message" => [
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.message",
            "config" => [
                "type" => "text",
                "wrap" => "OFF",
                "cols" => "30",
                "rows" => "5"
            ]
        ],
    ],
    "types" => [
        "0" => ["showitem" => "chat_pid;;;;1-1-1, name, message"]
    ],
    "palettes" => [
        "1" => ["showitem" => ""]
    ]
];