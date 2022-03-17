<?php

/**
 * Class BackendUserHelper
 *
 * Copyright (C) Leipzig University Library 2022 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgner@ub.uni-leipzig.de>
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
 */

namespace Ubl\Supportchat\Library;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendUserHelper
{
    /**
     * Constant for table be_sessions
     *
     * @var string
     */
    const TABLE_BACKEND_SESSIONS = 'be_sessions';

    /**
     * Constant for table be_users
     *
     * @var string
     */
    const TABLE_BACKEND_USERS = 'be_users';

    /**
     * Get backend users
     *
     * @param int $user_id Uid of user
     *
     * @return array $beUserArray   List of backend users
     * @access public
     */
    public static function getBackendUsers($user_id)
    {
        // $table="be_sessions";
        // $res = $TYPO3_DB->exec_SELECTquery("ses_userid", $table, '1');
        // get SelectBox with all be_user
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_BACKEND_SESSIONS)
            ->createQueryBuilder();
        $result = $queryBuilder
            ->select('ses_userid')
            ->from(self::TABLE_BACKEND_SESSIONS)
            ->execute();
        $inList = "";
        while ($row = $result->fetch()) {
            $inList .= ",'" . $row["ses_userid"] . "'";
        }
        $inList = substr($inList,1);
        $beUserArray = [];
        if ($inList) {
            // $table = "be_users"
            //$res = $TYPO3_DB->exec_SELECTquery(
            //    "uid, username, realName", $table, 'deleted=0 AND disable=0 AND uid IN ('.$inList.') AND uid<>' . $this->backendUserAuthentication->user["uid"]
            //);
            $queryBuilderUser = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable(self::TABLE_BACKEND_USERS)
                ->createQueryBuilder();
            $result = $queryBuilderUser
                ->select('uid', 'username', 'realName')
                ->from(self::TABLE_BACKEND_USERS)
                ->where(
                    $queryBuilder->expr()->eq('deleted', 0),
                    $queryBuilder->expr()->eq('disable', 0),
                    $queryBuilder->expr()->in('uid', $inList),
                    $queryBuilder->expr()->neq('uid', $user_id)
                )
                ->execute();
            $i = 0;
            while ($row = $result->fetch()) {
                $beUserArray[$i] = [
                    "uid" => $row["uid"],
                    "name" => ($row["realName"]) ?: $row["username"],
                ];
                $i++;
            }
        }
        return $beUserArray;
    }
}
