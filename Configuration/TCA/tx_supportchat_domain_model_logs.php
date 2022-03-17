<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TL = 'tx_' . $_EXTKEY . '_domain_model_logs';
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
        "iconfile" => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/icon_tx_supportchat_chats.gif',
    ],
    "feInterface" => [
        "fe_admin_fieldList" => "message",
    ],
    "interface" => [
        "showRecordFieldList" => "message"
    ],
    "columns" => [
        "message" => [
            "exclude" => 1,
            "label" => $_LL . ".message",
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
        "0" => ["showitem" => "message"]
    ],
    "palettes" => [
        "1" => ["showitem" => ""]
    ]
];