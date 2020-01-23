<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

  ## Extending TypoScript from static template uid=43 to set up userdefined tag:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
    $_EXTKEY,
    'editorcfg',
    'tt_content.CSS_editor.ch.tx_supportchat_pi1 = < plugin.tx_supportchat_pi1.CSS_editor',
    43
);

$TYPO3_CONF_VARS['FE']['eID_include']['tx_supportchat_pi1'] = 'EXT:' . $_EXTKEY . '/pi1/ajaxResponse.php';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi1/class.tx_supportchat_pi1.php',
    '_pi1',
    'list_type',
    0
);