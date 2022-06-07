<?php
/**
 * Class ChatHelper
 *
 * Copyright (C) Leipzig University Library 2020 <info@ub.uni-leipzig.de>
 *
 * @author  Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
 *
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

namespace Ubl\Supportchat\Library;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ChatHelper {

	/**
	 * Checks if the chat is online
     *
     * @param array $pids
     *
	 * @return array Return ("chatsPluginPid" => boolen(on- or offline), ...)
     * @access public
	 */
	public static function checkIfChatIsOnline($pids)
    {
		$pid = explode(',',$pids);
		$pids = "";
		foreach ($pid as $uid) {
			$pids .= ',' . (intval($uid));
		}
		$pids = substr($pids,1);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->createQueryBuilder();
        $result = $queryBuilder
            ->select('uid', 'hidden')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in('uid', $pids)
            )
            ->execute();
		$retArray = [];
		while ($row = $result->fetch()) {
			$retArray[$row["uid"]] = $row["hidden"] ? 0 : 1;
		}
		// Hook for implements personal check of chatIsOnline function
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/ChatHelper.php']['checkChatIsOnline'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['supportchat']['Library/ChatHelper.php']['checkChatIsOnline'] as $_funcRef) {
                $_params = [
                    'retArray' => $retArray
                ];
                $self = self;
                $retArray = GeneralUtility::callUserFunction($_funcRef, $_params, $self);
            }
        }
        return $retArray;
	}

    /**
     * Get anonymized ip address
     *
     * @param string $ip Ip address
     *
     * @return string Anonymize ip address
     * @access public
     */
    public static function getAnonymizedIpAddress($ip)
    {
        return preg_replace(
            ['/\.\d{1,3}\.\d{1,3}$/', '/[\da-f]*:[\da-f]*$/'],
            ['.0.0', 'XXXX:XXXX'],
            $ip
        );
    }

    /**
     * Prefer HTTP_X_FORWARDED_FOR IP towards originally programmed REMOTE_ADDR
     * due to use of proxy.
     *
     * @return string IP address
     * @access public
     */
    public static function getIpAddressOfUser()
    {
        if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
            return self::getAnonymizedIpAddress($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return self::getAnonymizedIpAddress($_SERVER["REMOTE_ADDR"]);
        }
    }

	/**
	 * Gets the path to a file, needed to translate the 'EXT:extkey' into the real path
	 *
	 * @params string $path  Path to the file
	 *
     * @return string        Returns path
     * @access public
	 */
	public static function getPath($path)
    {
		if (substr($path,0,4)=='EXT:') {
			$keyEndPos = strpos($path, '/', 6);
			$key = substr($path,4,$keyEndPos-4);
			$keyPath = ExtensionManagementUtility::siteRelpath($key);
			$newPath = $keyPath.substr($path,$keyEndPos+1);
			return $newPath;
		} else {
			return $path;
		}
	}

    /**
     * Convert to xml
     *
     * @param string $data The String to check
     *
     * @return string $xml With activated links
     * @access public
     */
	public static function convert2Xml($data)
    {
		$xml = GeneralUtility::array2xml($data, "", 0, "phparray", -1);
		return ($xml);
	}

    /**
     * Render timestamp
     *
     * @param string $tstamp
     *
     * @return string
     * @access public
     */
	public static function renderTstamp($tstamp)
    {
		return (date("H:i:s", $tstamp));
	}

	/**
	 * Convert http://, www., mailto: to real links
     *
	 * @param string $str String to check
     *
	 * @return string $str With activated links
     * @access public
	 */
	public static function activateHtmlLinks($str)
    {
		// uncommented due ticket issue #4560
		// $str = str_replace("http://www.","www.",$str);
		// $str = str_replace("www.","http://www.",$str);
		$str = preg_replace(
		    "/([\w]+:\/\/[\w\-?&;#~=\.\/\@]+[\w\/])/i",
            "<a href=\"$1\" target=\"_blank\">$1</a>",
            $str
        );
		$str = preg_replace(
		    "/([\w\-?&;#~=\.\/]+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,3}|[0-9]{1,3})(\]?))/i",
            "<a href=\"mailto:$1\">$1</a>",
            $str
        );
		return $str;
	}

	/**
	 * Prints the content for AJAX Request. Default for xml content.
     *
     * @param string $content   Content to print
     * @param boolean $json     Content in JSON
     *
     * @return string $content
     * @access public
	 */
	public static function printResponse($content, $json = false)
    {
		ob_clean();
		header('Expires: Mon, 26 Jul 1990 05:00:00 GMT');
		header('Last-Modified: ' . gmdate( "D, d M Y H:i:s" ) . 'GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Length: '.strlen($content));
        if ($json) {
            header('Content-Type: ' . 'application/json');
        } else {
            header('Content-Type: ' . 'text/xml');
        }
		print $content;
	}
}
