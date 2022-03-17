<?php
/**
 * Class Model Messages
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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Model Messages
 *
 * @package Ubl\SupportChat\Domain\Model
 */
class Messages extends AbstractEntity
{
    /**
     * Code
     *
     * @var string
     **/
    protected $code = '';

    /**
     * Chat pid
     *
     * @var int
     **/
    protected $chat_pid;

    /**
     * Creation date
     *
     * @var int
     * @validate notEmpty
     */
    protected $crdate = 0;

    /**
     * Name
     *
     * @var string
     **/
    protected $name = '';

    /**
     * Chat message
     *
     * @var string
     **/
    protected $message = '';

    /**
     * Returns type of user. Code can be 'feuser' or 'beuser'
     *
     * @return string $code Returns feuser|beuser
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Sets type of user. Code can be 'feuser' or 'beuser'
     *
     * @param string $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }

    /**
     * Returns chat pid
     *
     * @return int $chat_pid
     */
    public function getChatPid()
    {
        return $this->chat_pid;
    }

    /**
     * Sets chat pid
     *
     * @param int $chat_pid
     */
    public function setChatPid(int $chat_pid)
    {
        $this->chat_pid = $chat_pid;
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
     * Returns name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name
     *
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns message
     *
     * @return string $message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets message
     *
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }
}