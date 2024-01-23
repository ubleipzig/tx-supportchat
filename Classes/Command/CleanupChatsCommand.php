<?php
declare(strict_types=1);
/**
 * Class CleanupChatsCommand
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
class CleanupChatsCommand extends Command
{
    /**
     * Size of chunk for large scales sql queries
     *
     * @var int sqlOperatingChunksize
     * @access protected
     */
    protected $sqlOperatingChunksize = 250;

    /**
     * Configure the command by defining the name, options and arguments
     *
     * @return void
     */
    public function configure() :void
    {
        $this
            ->setDescription('Removes passed chats after a certain period of days.')
            ->setHelp('Removes passed chats after a certain period of days.'
                . LF . 'To specify the period of days chat will be kept, use the --days or -d option. Default is 7 days.'
                . LF . 'To see only data what will be removed, use the --dry-run option.'
            )
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Pass this option to set time interval defined in days for cleaning frontend user table. Default is 7.',
                '7'
            )->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not be processed.'
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
        if ($isDryRun === true) {
            $io->writeln('<info>This is a dry-run. Data will not be removed.</info>');
        }
        $days = $input->getOption('days');

        $dt = new \DateTimeImmutable('now', new \DateTimeZone(date_default_timezone_get()));
        $current = $dt->modify('midnight');
        $ts = $current->sub(new \DateInterval("P{$days}D"));
        $chatList = $this->findChatsBeforeTime($ts);
        $uids = array_chunk(
            array_column($chatList, 'uid'),
            $this->sqlOperatingChunksize
        );
        $cnt = 0;
        if ($isDryRun === false) {
            foreach ($uids as $package) {
                $cnt += $this->removeChatMessagesByIds($package);
                sleep(3);
            }
        } else {
            $cnt = count($chatList);
        }
        $io->writeln(
            sprintf('%d chats removed before %s', $cnt, $ts->format('d.m.Y H:i:s'))
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
     * Finds chats before a specified time
     *
     * @param \DateTimeInterface $time Time
     *
     * @return array  	Return associative array with result.
     * @access public
     */
    public function findChatsBeforeTime(\DateTimeInterface $time)
    {
        try {
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_domain_model_chats');
            return $queryBuilder
                ->select('tx_supportchat_domain_model_chats.uid')
                ->from('tx_supportchat_domain_model_chats')
                ->rightJoin(
                    'tx_supportchat_domain_model_chats',
                    'tx_supportchat_domain_model_messages',
                    'm',
                    $queryBuilder->expr()->eq('m.chat_pid', $queryBuilder->quoteIdentifier('tx_supportchat_domain_model_chats.uid'))
                )
                ->where(
                    $queryBuilder->expr()->eq(
                        'tx_supportchat_domain_model_chats.active',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
                ->andWhere(
                    $queryBuilder->expr()->lt(
                        'tx_supportchat_domain_model_chats.crdate',
                        $time->getTimestamp()
                    )
                )
                ->groupBy('tx_supportchat_domain_model_chats.uid')
                ->execute()
                ->fetchAll();
        } catch (\Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }

    /**
     * Remove chat messages by given uids
     *
     * @param array $uids	Uids to remove
     *
     * @return int 	Affected row to proceed.
     * @access public
     */
    public function removeChatMessagesByIds(array $uids)
    {
        try {
            $deleteList = implode(
                ', ',
                array_map(function ($item) {
                    return "'" . $item . "'";
                },
                    $uids)
            );
            $queryBuilder = $this->getConnectionForTable('tx_supportchat_domain_model_messages');
            return $queryBuilder
                ->delete('tx_supportchat_domain_model_messages')
                ->where(
                    $queryBuilder->expr()->in('chat_pid', $deleteList)
                )
                ->execute();
        } catch (\Exception $e) {
            'Error while operating on database:' . $e->getMessage() . ' with code:' . $e->getCode();
        }
    }
}