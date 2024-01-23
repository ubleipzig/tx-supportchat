<?php
/**
 * Class Model Log
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
 * Class Model Log
 *
 * @package Ubl\SupportChat\Domain\Model
 */
class Logs extends AbstractEntity
{
    /**
     * Creation date
     *
     * @var int $crdate
     * @Extbase\Validate("NotEmpty");
     */
    protected $crdate = 0;

    /**
     * Log message
     *
     * @var string
     **/
    protected $message = '';

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