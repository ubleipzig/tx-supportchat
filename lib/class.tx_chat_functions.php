<?php
class tx_chat_functions {

    /**
    * Checks if the chat is online
    * @return Array ("chatsPluginPid" => boolen(on- or offline), ...)
    */
    function checkIfChatIsOnline($pids) {
        global $TYPO3_DB;
        $table="pages";
		// security
		$pid = explode(',',$pids);
		$pids = "";
		foreach ($pid as $uid) {
			$pids .= ','.(intval($uid));
		}
		$pids = substr($pids,1);
        $res = $TYPO3_DB->exec_SELECTquery("uid,hidden",$table,'uid IN ('.$pids.')');
		$retArray = Array();
		while ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
			$retArray[$row["uid"]] = $row["hidden"] ? 0 : 1;
		}
        // Hook for youre own chatIsOnline function 
        $hookObjectsArr = array();
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/checkIfOnline'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['sni_supportchat/checkIfOnline'] as $classRef) {
                $hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
            }
        }

        foreach($hookObjectsArr as $hookObj)    {
            if (method_exists($hookObj, 'checkIfChatIsOnline')) {
                $retArray =  $hookObj->checkIfChatIsOnline($pids,$retArray,$this);
            }
        }

        return ($retArray);
    }

    /**
    * Gets the path to a file, needed to translate the 'EXT:extkey' into the real path
    *
    * @param    string  $path: Path to the file
    * @return the real path
    */
     function getPath($path) {
       if (substr($path,0,4)=='EXT:') {
         $keyEndPos = strpos($path, '/', 6);
         $key = substr($path,4,$keyEndPos-4);
         $keyPath = t3lib_extMgm::siteRelpath($key);
         $newPath = $keyPath.substr($path,$keyEndPos+1);
         return $newPath;
       }    else {
         return $path;
       }
    }

    function convert2Xml($data) {
        $xml = t3lib_div::array2xml($data,"",0,"phparray",-1);
        return ($xml);
    }

    function renderTstamp($tstamp) {
        return (date("H:i:s",$tstamp));
    }

	/**
	* convert http://, www., mailto: to real links
	* @param String: the String to check
	* @return String: with activated links
	*/
	function html_activate_links($str) {
		$str = str_replace("http://www.","www.",$str);
		$str = str_replace("www.","http://www.",$str);
		$str = preg_replace("/([\w]+:\/\/[\w-?&;#~=\.\/\@]+[\w\/])/i","<a href=\"$1\" target=\"_blank\">$1</a>", $str);
		$str = preg_replace("/([\w-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i","<a href=\"mailto:$1\">$1</a>",$str);		
		return $str;
	}
 
    /**
    * Prints the content for AJAX Request
    */
    function printResponse($content) {
		ob_clean();
        header('Expires: Mon, 26 Jul 1990 05:00:00 GMT');
        header('Last-Modified: ' . gmdate( "D, d M Y H:i:s" ) . 'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-Length: '.strlen($content));
        header('Content-Type: text/xml');
        print $content;
    }
}	
?>
