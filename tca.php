<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_snisupportchat_chats"] = Array (
	"ctrl" => $TCA["tx_snisupportchat_chats"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,be_user,session,active,last_row_uid,language_uid,surfer_ip"
	),
	"feInterface" => $TCA["tx_snisupportchat_chats"]["feInterface"],
	"columns" => Array (
		"hidden" => Array (		
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.xml:LGL.hidden",
			"config" => Array (
				"type" => "check",
				"default" => "0"
			)
		),
		"be_user" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.be_user",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "be_users",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"session" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.session",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"surfer_ip" => Array(
			"exclude" => 1,
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.surfer_ip",
			"config" => Array(
				"type" => "input",
				"size" => "30"
			)
		),
		"active" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.active",		
			"config" => Array (
				"type" => "check",
			)
		),
		"last_row_uid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.last_row_uid",		
			"config" => Array (
				"type" => "none",
			)
		),
		"language_uid" => Array (
                        "exclude" => 1,
                        "label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats.language_uid",
                        "config" => Array (
                                "type" => "none",
                        )
                ),

	),
	"types" => Array (
		"0" => Array("showitem" => "hidden;;1;;1-1-1, be_user, session, active, last_row_uidi,language_uid,surfer_ip")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);



$TCA["tx_snisupportchat_messages"] = Array (
	"ctrl" => $TCA["tx_snisupportchat_messages"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "chat_pid,name,message"
	),
	"feInterface" => $TCA["tx_snisupportchat_messages"]["feInterface"],
	"columns" => Array (
		"chat_pid" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_messages.chat_pid",		
			"config" => Array (
				"type" => "group",	
				"internal_type" => "db",	
				"allowed" => "tx_snisupportchat_chats",	
				"size" => 1,	
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"name" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_messages.name",		
			"config" => Array (
				"type" => "input",	
				"size" => "30",
			)
		),
		"message" => Array (		
			"exclude" => 1,		
			"label" => "LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_messages.message",		
			"config" => Array (
				"type" => "text",
				"wrap" => "OFF",
				"cols" => "30",	
				"rows" => "5",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "chat_pid;;;;1-1-1, name, message")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>
