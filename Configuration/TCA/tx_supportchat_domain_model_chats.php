<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TL = 'tx_' . $_EXTKEY . '_domain_model_chats';
$_LL = 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:' . $_TL;


return [
    "ctrl" => [
        'title' => $_LL,
        'label' => 'be_user',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "delete" => "deleted",
        "enablecolumns" => [
            "disabled" => "hidden",
        ],
        "iconfile" => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/icon_tx_supportchat_chats.gif',
    ],
    "feInterface" => [
        "fe_admin_fieldList" => "hidden, fe_user, session, active, last_row_uid",
    ],
    "interface" => [
        "showRecordFieldList" => "hidden,be_user,session,active,last_row_uid,language_uid,surfer_ip,status,type_status"
    ],
    "columns" => [
        "hidden" => [
            "exclude" => 1,
            "label" => "LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden",
            "config" => [
                "type" => "check",
                "default" => "0"
            ]
        ],
        "be_user" => [
            "exclude" => 1,
            "label" => $_LL . ".be_user",
            "config" => [
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "be_users",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1
            ]
        ],
        "session" => [
            "exclude" => 1,
            "label" => $_LL . ".session",
            "config" => [
                "type" => "input",
                "size" => "30"
            ]
        ],
        "surfer_ip" => [
            "exclude" => 1,
            "label" => $_LL . ".surfer_ip",
            "config" => [
                "type" => "input",
                "size" => "30"
            ]
        ],
        "active" => [
            "exclude" => 1,
            "label" => $_LL . ".active",
            "config" => [
                "type" => "check"
            ]
        ],
        "status" => [
            "exclude" => 1,
            "label" => $_LL . ".status",
            "config" => [
                "type" => "passthrough"
            ]
        ],
        "type_status" => [
            "exclude" => 1,
            "label" => $_LL . ".type_status",
            "config" => [
                "type" => "passthrough"
            ]
        ],
        "last_row_uid" => [
            "exclude" => 1,
            "label" => $_LL . ".last_row_uid",
            "config" => [
                "type" => "none"
            ]
        ],
        "language_uid" => [
            "exclude" => 1,
            "label" => $_LL . ".language_uid",
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
        "0" => ["showitem" => "hidden, --palette--;;1;, be_user, session, active, last_row_uid, language_uid, surfer_ip, type_status, status"]
    ],
    "palettes" => [
        "1" => ["showitem" => ""]
    ]
];
