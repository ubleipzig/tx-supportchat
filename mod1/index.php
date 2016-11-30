<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Georg Sch�nweger <Georg.Schoenweger@gmail.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'Support Chat' for the 'sni_supportchat' extension.
 *
 * @author	Georg Schönweger <Georg.Schoenweger@gmail.com>
 */



	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ("conf.php");
require ($BACK_PATH."init.php");
require ($BACK_PATH."template.php");
$LANG->includeLLFile("EXT:sni_supportchat/mod1/locallang.xml");
require_once (PATH_t3lib."class.t3lib_scbase.php");

require_once(t3lib_extMgm::extPath('sni_supportchat').'lib/class.tx_chat.php');


$BE_USER->modAccess($MCONF,1);	// This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]

class tx_snisupportchat_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $jsCodeForLoadedChats;
	var $chatsPid; // the page id where chats and messages are stored 
	var $ajaxGetAllFreq = 3; // the period of the AJAX Request
	var $timeToInactivateChat = 15; // the time to inactivate a chat in minutes
	var $defLang = "gb"; // the default language for the Be-User
	var $playAlert = 1; // play an alert sound yes or no
	var $beUserName = ""; // the BE Username shown in the chat
	var $showLogBox = 1; // Display the log box
	/** tradem 2012-04-11 Added to control typing indiator */
	var $useTypingIndicator = 1;  // controls if typing indicator should show up or not, defaults to true (1)

	/**
	 * Initializes the Module
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		if($BE_USER->userTS["sni_supportchat."]["defLang"]) {
			$this->defLang = $BE_USER->userTS["sni_supportchat."]["defLang"];	
		}
		$this->chatsPid = $BE_USER->userTS["sni_supportchat."]["chatsPid"];
		if(!$this->chatsPid) {
			die('You must insert the BE-User TS-Config Var "sni_supportchat.chatsPid" !');				
		}
		
		/** 2012-04-11 Added to control typing indiator if it works. */
		if (isset($BE_USER->userTS["sni_supportchat."]["useTypingIndicator"])) {
			$this->useTypingIndicator = intval($BE_USER->userTS["sni_supportchat."]["useTypingIndicator"]);
		}
						
		if($BE_USER->userTS["sni_supportchat."]["ajaxGetAllFreq"]) {
			$this->ajaxGetAllFreq = $BE_USER->userTS["sni_supportchat."]["ajaxGetAllFreq"];
		}
		if($BE_USER->userTS["sni_supportchat."]["timeToInactivateChatIfNoMessages"]) {
			$this->timeToInactivateChat = $BE_USER->userTS["sni_supportchat."]["timeToInactivateChatIfNoMessages"];
		}
		if($BE_USER->userTS["sni_supportchat."]["playAlert"]!="") {
			$this->playAlert = $BE_USER->userTS["sni_supportchat."]["playAlert"];
		}
		if($BE_USER->userTS["sni_supportchat."]["showLogBox"]!="") {
			$this->showLogBox = $BE_USER->userTS["sni_supportchat."]["showLogBox"];
		}
        $this->beUserName = $BE_USER->user["realName"] ? $BE_USER->user["realName"] : $BE_USER->user["username"];

		parent::init();
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return	void
	 */
	function menuConfig()	{
		global $LANG;
		$this->MOD_MENU = Array (
			"function" => Array (
				"1" => $LANG->getLL("function1"),
				"2" => $LANG->getLL("function2"),
				"3" => $LANG->getLL("function3"),
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS,$TYPO3_DB;
		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		// Draw the header.
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $BACK_PATH;

		// Render content:
		$headerSection = $this->doc->getHeader("pages",$this->pageinfo,$this->pageinfo["_thePath"],50);			
		$this->content.=$headerSection;
		$this->content.=$this->doc->header($LANG->getLL("title"));
		if($this->playAlert) {
			// Add the Alert SWF File
			$this->content .= '
				<object id="beep_alert" class="flash" type="application/x-shockwave-flash" data="../js/flashbeep_alert.swf" width="1" height="1">
					<param name="movie" value="../js/flashbeep_alert.swf" />
				</object>		
				<p class="alert"><input type="checkbox" checked="checked" id="alert_check" /> '.$LANG->getLL("playAlert").'</p>			
			';
		}		
		$this->content.=$this->doc->spacer(20);		
		$chat = new chat();
		$chat->initChat($this->chatsPid,"");
		$chat->destroyInactiveChats($this->timeToInactivateChat);	
//        tx_chat_functions::destroyInactiveChats($this->timeToInactivateChat,$this->chatsPid);
		$this->moduleContent();									
				
			// JavaScript
		$this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				script_ended = 0;
				function jumpToUrl(URL)	{
					document.location = URL;
				}
			</script>
		';
		$this->doc->JScode .= $this->addJsInHeader();			
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["web"] = 0;
			</script>
		';
		$this->content.=$this->doc->startPage($LANG->getLL("title"));
		// ShortCut
		if ($BE_USER->mayMakeShortcut())	{
			$this->content.=$this->doc->spacer(20).$this->doc->section("",$this->doc->makeShortcutIcon("id",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"]));
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return	void
	 */
	function printContent()	{	
		global $TYPO3_DB,$LANG,$BACK_PATH,$BE_USER;
		
		$this->content.=$this->doc->endPage();		
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{
		global $LANG;
	    // page/be_user TSconfig settings:
		$modTSconfig = t3lib_BEfunc::getModTSconfig($id,"mod.".$GLOBALS["MCONF"]["name"]);			
		//render Chat Boxes Wrap
		$this->content.='<div id="chatboxes_wrap">';		
		$this->content.='</div>';		
		$this->content.='<hr class="clearer" />';
        if($this->showLogBox) {
            $this->content.=$this->doc->spacer(5);
			$this->content.='<p class="log_title">Log:</p>';
            $this->content.='<div id="logBox">&nbsp;</div>';
        }
	}
	
	/**
	 * Adds the complete JS Code
	 * @return Complete Java Code
	 */
	function addJsInHeader() {
		global $LANG,$BACK_PATH,$BE_USER,$TYPO3_DB;
		$frequency = $this->ajaxGetAllFreq * 1000;
		$useTypingIndicator = $this->useTypingIndicator;
		$table="sys_language";
		$res = $TYPO3_DB->exec_SELECTquery("uid,flag,title",$table,'1');		
		$jsCode = '
			<link rel="stylesheet" type="text/css" href="'.t3lib_div::createVersionNumberedFilename('chat.css').'" />
			<script type="text/javascript" src="../js/mootools-1.2.6-core-yc.js"></script>
            <script type="text/javascript" src="../js/mootools-1.2.5.1-more.js"></script>
			<script type="text/javascript" src="'.t3lib_div::createVersionNumberedFilename('../js/Element.Forms.js').'"></script>
			<script type="text/javascript" src="'.t3lib_div::createVersionNumberedFilename('../js/UvumiDropdown-compressed.js').'"></script>
            <script type="text/javascript" src="'.t3lib_div::createVersionNumberedFilename('../js/smilies.js').'"></script>
			<script type="text/javascript" src="'.t3lib_div::createVersionNumberedFilename('../js/sni_supportchat_be.js').'"></script> 
			<script type="text/javascript">
            /*<![CDATA[*/
            <!--
				var LL = {
					"options": "'.addslashes($LANG->getLL("options")).'",
					"text_pieces": "'.addslashes($LANG->getLL("text_pieces")).'",
					"options_lock": "'.addslashes($LANG->getLL("options_lock")).'",
					"options_unlock": "'.addslashes($LANG->getLL("options_unlock")).'",
					"options_assume": "'.addslashes($LANG->getLL("options_assume")).'",
					"created_at": "'.addslashes($LANG->getLL("created_at")).'",
					"language": "'.addslashes($LANG->getLL("language")).'",
					"type_youre_message": "'.addslashes($LANG->getLL("type_youre_message")).'",
					"status_unlocked": "'.addslashes($LANG->getLL("status_unlocked")).'",
					"status_locked": "'.addslashes($LANG->getLL("status_locked")).'",
					"username": "'.addslashes($this->beUserName).'",
					"system": "'.addslashes($LANG->getLL("system")).'",
					"chatDestroyedMsg": "'.addslashes($LANG->getLL("chatDestroyedMsg")).'",
					"welcomeMsg": "'.addslashes(sprintf($LANG->getLL("welcomeMsg"),$this->beUserName)).'",
					"noFixTextInThisLanguage": "'.addslashes($LANG->getLL("noFixTextInThisLanguage")).'",
					"noBeUserOnline": "'.addslashes($LANG->getLL("noBeUserOnline")).'",
					"ok": "'.addslashes($LANG->getLL("ok")).'",
					"abort": "'.addslashes($LANG->getLL("abort")).'",
					"assumeToTitle": "'.addslashes($LANG->getLL("assumeToTitle")).'"
				}

				var fixText = {
					'.$this->createFixTextJsObj().'
				}
				
				var theRequest = null;
				var timer = null;
				var strftime = "";

				window.addEvent("domready", function() {
					initChat('.$frequency.','.$useTypingIndicator.');
				});
            // -->
            /*]]>*/
			</script>
		';
		return($jsCode);
	}
	
	function createFixTextJsObj() {
		global $BE_USER;
		$fixText = $BE_USER->userTS["sni_supportchat."]["fixText."];
		$jsCode = '';
		if(is_array($fixText)) {
			foreach ($fixText as $key => $val) {
				$jsCode .= '
					"'.substr($key,0,-1).'": {
				';
				foreach ($val as $keyInner => $valInner) {
					$jsCode .= '
						"'.$keyInner.'": "'.$valInner.'",';
				}
				$jsCode = substr($jsCode,0,-1);
				$jsCode .= '
					},';
			}
			$jsCode = substr($jsCode,0,-1);
		}
		return ($jsCode);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sni_supportchat/mod1/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/sni_supportchat/mod1/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_snisupportchat_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
