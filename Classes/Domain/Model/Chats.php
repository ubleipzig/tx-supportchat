<?php
/**
 * Class Model Chats
 *
 * Copyright (C) Leipzig University Library 2022 <info@ub.uni-leipzig.de>
 *
 * @author  Frank Morgner <morgnerf@ub.uni-leipzig.de>
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

namespace Ubl\Supportchat\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;


/**
 * Class Model Chats
 *
 * @package Ubl\SupportChat\Domain\Model
 */
class Chats extends AbstractEntity
{
    /**
     * Backend user
     *
     * @var string
     **/
    protected $beUser = "";

    /**
     * Creation date
     *
     * @var int $crdate
     * @Extbase\Validate("NotEmpty");
     */
    protected $crdate = 0;

    /**
     * Session
     *
     * @var string
     **/
    protected $session = "";

    /**
     * Status if active
     *
     * @var int
     **/
    protected $active = 0;

    /**
     * IP of client
     *
     * @var string
     **/
    protected $surferIp = "";

    /**
     * Language id of user
     *
     * @var int
     **/
    protected $languageUid = 0;

    /**
     * Status information of chat
     *
     * @var string
     **/
    protected $status = "";

    /**
     * Status of typing for front- and backend saved as json string
     *
     * @var string
     **/
    protected $typeStatus = "{}";

    /**
     * Returns backend user
     *
     * @return string $beUser
     */
    public function getBackendUser()
    {
        return $this->beUser;
    }

    /**
     * Sets backend user
     *
     * @param string $beUser
     */
    public function setBackendUser(string $beUser)
    {
        $this->beUser = $beUser;
    }

    /**
     * Returns creation date
     *
     * @return int $crdate
     */
    public function getCrdate()
    {
        return $this->crdate;
    }

    /**
     * Sets creation date
     *
     * @param int $crdate
     */
    public function setCrdate(int $crdate)
    {
        $this->crdate = $crdate;
    }

    /**
     * Returns session
     *
     * @return string $session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Sets session
     *
     * @param string $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * Returns status active
     *
     * @return int $active
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Sets status active
     *
     * @param int $active
     */
    public function setActive(int $active)
    {
        $this->active = $active;
    }

    /**
     * Returns client ip
     *
     * @return string $surferIp
     */
    public function getClientIp()
    {
        return $this->surferIp;
    }

    /**
     * Sets client ip
     *
     * @param string $surferIp
     */
    public function setClientIp(string $surferIp)
    {
        $this->surferIp = $surferIp;
    }

    /**
     * Returns language id of user
     *
     * @return int $languageUid
     */
    public function getLanguageUid()
    {
        return $this->languageUid;
    }

    /**
     * Sets language id of user
     *
     * @param int $languageUid
     */
    public function setLanguageUid(int $languageUid)
    {
        $this->languageUid = $languageUid;
    }

    /**
     * Returns status information of chat
     *
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets status information of chat
     *
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * Returns status of typing
     *
     * @return string $status
     */
    public function getTypeStatus()
    {
        return $this->typeStatus;
    }

    /**
     * Sets status of typing
     *
     * @param array $type_status
     */
    public function setTypeStatus(array $type_status)
    {
        $this->typeStatus = json_encode($type_status);
    }

}