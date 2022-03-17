<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TL = 'tx_' . $_EXTKEY . '_domain_model_messages';
$_LL = 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:' . $_TL;

return [
    "ctrl" => [
        "title" => $_LL,
        "label" => "name",
        "tstamp" => "tstamp",
        "crdate" => "crdate",
        "cruser_id" => "cruser_id",
        "default_sortby" => "ORDER BY crdate DESC",
        "delete" => "deleted",
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
            "label" => $_LL . ".chat_pid",
            "config" => [
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_supportchat_domain_model_chats",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1
            ]
        ],
        "name" => [
            "exclude" => 1,
            "label" => $_LL . ".name",
            "config" => [
                "type" => "input",
                "size" => "30"
            ]
        ],
        "message" => [
            "exclude" => 1,
            "label" => $_LL . ".message",
            "config" => [
                "type" => "text",
                "wrap" => "OFF",
                "cols" => "30",
                "rows" => "5"
            ]
        ],
        "code" => [
            "exclude" => 1,
            "label" => $_LL . ".code",
            "config" => [
                "type" => "none"
            ]
        ],
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ]
    ],
    "types" => [
        "0" => ["showitem" => "chat_pid, name, message, code"]
    ],
    "palettes" => [
        "1" => ["showitem" => ""]
    ]
];