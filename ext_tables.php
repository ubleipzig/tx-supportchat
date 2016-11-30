<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::allowTableOnStandardPages("tx_snisupportchat_chats");

$TCA["tx_snisupportchat_chats"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_chats',		
		'label' => 'be_user',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"default_sortby" => "ORDER BY crdate DESC",	
		"delete" => "deleted",	
		"enablecolumns" => Array (		
			"disabled" => "hidden",
		),
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_snisupportchat_chats.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, be_user, session, active, last_row_uid",
	)
);


t3lib_extMgm::allowTableOnStandardPages("tx_snisupportchat_messages");

$TCA["tx_snisupportchat_messages"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_messages',		
		'label' => 'name',	
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"default_sortby" => "ORDER BY crdate DESC",	
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."icon_tx_snisupportchat_messages.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "chat_pid, name, message",
	)
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


t3lib_extMgm::addPlugin(Array('LLL:EXT:sni_supportchat/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/Sni_SupportChat_TS/', 'Sni Support Chat TS');

if (TYPO3_MODE=="BE")	{
		
	t3lib_extMgm::addModule("user","txsnisupportchatM1","",t3lib_extMgm::extPath($_EXTKEY)."mod1/");
}
?>
