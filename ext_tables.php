<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_snisupportchat_chats");

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
		"dynamicConfigFile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)."tca.php",
		"iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY)."icon_tx_snisupportchat_chats.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "hidden, be_user, session, active, last_row_uid",
	)
);


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages("tx_snisupportchat_messages");

$TCA["tx_snisupportchat_messages"] = Array (
	"ctrl" => Array (
		'title' => 'LLL:EXT:sni_supportchat/locallang_db.xml:tx_snisupportchat_messages',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		"default_sortby" => "ORDER BY crdate DESC",
		"dynamicConfigFile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)."tca.php",
		"iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY)."icon_tx_snisupportchat_messages.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "chat_pid, name, message",
	)
);


// \TYPO3\CMS\Core\Utility\GeneralUtility::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key';


\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(Array('LLL:EXT:sni_supportchat/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY,'pi1/static/Sni_SupportChat_TS/', 'Sni Support Chat TS');

if (TYPO3_MODE=="BE")	{
	 \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		$_EXTKEY,
		'user',          // Main area
		'mod1',         // Name of the module
		'',             // Position of the module
		array(          // Allowed controller action combinations
				'Blog' => 'index,show,new,create,delete,deleteAll,edit,update,populate',
				'Post' => 'index,show,new,create,delete,edit,update',
				'Comment' => 'create,delete,deleteAll',
		),
		array(          // Additional configuration
				'access'    => 'user,group',
				'icon'      => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
				'labels'    => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml',
		)
	);

//	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule("user","txsnisupportchatM1","",\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY)."mod1/");
}
?>
