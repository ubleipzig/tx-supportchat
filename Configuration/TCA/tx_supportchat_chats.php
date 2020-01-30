<?php
if (!defined ('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'supportchat';
$_TXEXTKEY = 'tx_' . $EXTKEY;

return array(
    "ctrl" => array(
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats',
        'label' => 'be_user',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        "default_sortby" => "ORDER BY crdate DESC",
        "delete" => "deleted",
    "enablecolumns" => array(
    "disabled" => "hidden",
    ),
    "iconfile" => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/icon_tx_supportchat_chats.gif',
    ),
    "feInterface" => array(
        "fe_admin_fieldList" => "hidden, be_user, session, active, last_row_uid",
    ),
    "interface" => array(
        "showRecordFieldList" => "hidden,be_user,session,active,last_row_uid,language_uid,surfer_ip"
    ),
    "columns" => array(
        "hidden" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden",
            "config" => array(
                "type" => "check",
                "default" => "0"
            )
        ),
        "be_user" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.be_user",
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
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.session",
            "config" => array(
                "type" => "input",
                "size" => "30",
            )
        ),
        "surfer_ip" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.surfer_ip",
            "config" => array(
                "type" => "input",
                "size" => "30"
            )
        ),
        "active" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.active",
            "config" => array(
                "type" => "check",
            )
        ),
        "last_row_uid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.last_row_uid",
            "config" => array(
                "type" => "none",
            )
        ),
        "language_uid" => array(
            "exclude" => 1,
            "label" => "LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_db.xlf:'.$_TXEXTKEY.'_chats.language_uid",
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
