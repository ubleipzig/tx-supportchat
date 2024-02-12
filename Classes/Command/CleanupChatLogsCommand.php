<?php
declare(strict_types=1);
/**
 * Class CleanupChatLogsCommand
 *
 * Copyright (C) Leipzig University Library 2024 <info@ub.uni-leipzig.de>
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
namespace Ubl\Supportchat\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CleanupCommandController
 *
 * Provides commandline interface to clean up past bookings
 *
 * @package Ubl\Booking\Command
 */
class CleanupChatLogsCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     *
     * @return void
     */
    public function configure(): void
    {
        $this
            ->setDescription('Removes chat logs after a certain period of days.')
            ->setHelp('Removes chat logs after a certain period of days.'
                . LF . 'To specify the period of days chat will be kept, use the --days or -d option. Default is 7 days.'
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Pass this option to set time interval defined in days for cleaning frontend user table. Default is 7.',
                '7'
            );
    }

    /**
     * Console command for removing expired bookings
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = $input->getOption('dry-run') != false ? true : false;

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $days = $input->getOption('days');

        $dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
        $current = $dt->modify('midnight');
        $ts = $current->sub(new \DateInterval("P{$days}D"));
        $cnt = $this->removeChatLogsBeforeTime($ts);
        $io->writeln(
            sprintf('%d chat logs removed before %s', $cnt, $ts->format('d.m.Y H:i:s'))
        );
        return 0;
    }

    /**
     * Get connection for table
     *
     * @param string $tbl	Table name
     *
     * @return TYPO3\CMS\Core\Database\Connection
     * @access protected
     */
    protected function getConnectionForTable($tbl)
    {
        /** @var ConnectionPool $connectionPool */
        return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tbl);
    }

    /**
     * Removes chat logs before a specified time from database
     *
     * @param \DateTimeInterface $time Timestamp
     *
     * @return int 	Affected row to proceed.
     * @access protected
     */
    protected function removeChatLogsBeforeTime(\DateTimeInterface $time)
    {
        try {
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_domain_model_logs');
            return $queryBuilder
                ->delete('tx_supportchat_domain_model_logs')
                ->where(
                    $queryBuilder->expr()->lt(
                        'tstamp',
                        $time->getTimestamp()
                    )
                )
                ->execute();
        } catch (Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }
}