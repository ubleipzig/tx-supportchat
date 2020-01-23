<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_supportchat_chats"] = array(
    "ctrl" => $TCA["tx_supportchat_chats"]["ctrl"],
    "interface" => array(
        "showRecordFieldList" => "hidden,be_user,session,active,last_row_uid,language_uid,surfer_ip"
    ),
    "feInterface" => $TCA["tx_supportchat_chats"]["feInterface"],
    "columns" => array(
        "hidden" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
            "config" => array(
                "type" => "check",
                "default" => "0"
            )
        ),
        "be_user" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.be_user",
            "config" => array(
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "be_users",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
        "session" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.session",
            "config" => array(
                "type" => "input",
                "size" => "30",
            )
        ),
        "surfer_ip" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.surfer_ip",
            "config" => array(
                "type" => "input",
                "size" => "30"
            )
        ),
        "active" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.active",
            "config" => array(
                "type" => "check",
            )
        ),
        "last_row_uid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.last_row_uid",
            "config" => array(
                "type" => "none",
            )
        ),
        "language_uid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_chats.language_uid",
            "config" => array(
                "type" => "none",
            )
        ),

    ),
    "types" => array(
        "0" => Array("showitem" => "hidden;;1;;1-1-1, be_user, session, active, last_row_uidi,language_uid,surfer_ip")
    ),
    "palettes" => array(
        "1" => array("showitem" => "")
    )
);



$TCA["tx_supportchat_messages"] = array(
    "ctrl" => $TCA["tx_supportchat_messages"]["ctrl"],
    "interface" => array(
        "showRecordFieldList" => "chat_pid,name,message"
    ),
    "feInterface" => $TCA["tx_supportchat_messages"]["feInterface"],
    "columns" => array(
        "chat_pid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_messages.chat_pid",
            "config" => array(
                "type" => "group",
                "internal_type" => "db",
                "allowed" => "tx_supportchat_chats",
                "size" => 1,
                "minitems" => 0,
                "maxitems" => 1,
            )
        ),
        "name" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_messages.name",
            "config" => array(
                "type" => "input",
                "size" => "30",
            )
        ),
        "message" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:supportchat/locallang_db.xml:tx_supportchat_messages.message",
            "config" => array(
                "type" => "text",
                "wrap" => "OFF",
                "cols" => "30",
                "rows" => "5",
            )
        ),
    ),
    "types" => array(
        "0" => array("showitem" => "chat_pid;;;;1-1-1, name, message")
    ),
    "palettes" => array(
        "1" => array("showitem" => "")
    )
);
