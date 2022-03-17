<?php
/**
 * Class Log Repository
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

namespace Ubl\Supportchat\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class Chats Repository
 *
 * @package Ubl\SupportChat\Domain\Repository
 */
class LogsRepository extends Repository
{
    /**
     * Initializes the repository.
     *
     * @return void
     * @see \TYPO3\CMS\Extbase\Persistence\Repository::initializeObject()
     */
    public function initializeObject()
    {
        /** @var QuerySettingsInterface $querySettings */
        $querySettings = $this->objectManager->get(QuerySettingsInterface::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Find active chat by pid
     *
     * @param int $pid
     * @param int $notOlderThan
     * @param int $logLimit
     *
     * @return array
     * @access public
     */
    public function findLogMessages(int $pid, int $notOlderThan, int $logLimit)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $constraints = [
            $query->equals('pid', $pid),
            $query->greaterThan('crdate', $notOlderThan)
        ];
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);
        $query->setLimit($logLimit);
        return $query->matching($query->logicalAnd($constraints))->execute();
    }
}
