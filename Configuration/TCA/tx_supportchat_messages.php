<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TXEXTKEY = 'tx_' . $EXTKEY;

return array(
    "ctrl" => array(
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "iconfile" => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/icon_tx_supportchat_messages.gif',
    ),
    "feInterface" => array(
        "fe_admin_fieldList" => "chat_pid, name, message",
    ),
    "interface" => array(
        "showRecordFieldList" => "chat_pid,name,message"
    ),
    "columns" => array(
        "chat_pid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.chat_pid",
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
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.name",
            "config" => array(
                "type" => "input",
                "size" => "30",
            )
        ),
        "message" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_messages.message",
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