<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Job\Console;

use Psr\Clock\ClockInterface;
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\Service\Console\AbstractCommand;
use Tobento\Service\Console\InteractorInterface;

class PurgeJobsCommand extends AbstractCommand
{
    /**
     * The signature of the console command.
     */
    public const SIGNATURE = '
        jobs:purge | Purges jobs.
        {--hours=24 : The number of hours to retain jobs data.}
        {--queued= : If defined it purges only jobs which are queued (true) or not (false).}
        {--status= : Purges only the jobs with the defined status.}
        {--appId[] : Purges only the jobs which belongs to the defined app IDs.}
    ';

    /**
     * Handle the command.
     *
     * @param InteractorInterface $io
     * @param JobRepositoryInterface $jobRepository
     * @param ClockInterface $clock
     * @return int The exit status code: 
     *     0 SUCCESS
     *     1 FAILURE If some error happened during the execution
     *     2 INVALID To indicate incorrect command usage e.g. invalid options
     */
    public function handle(
        InteractorInterface $io,
        JobRepositoryInterface $jobRepository,
        ClockInterface $clock,
    ): int {
        $where = [];
        
        if (!is_null($queued = $io->option(name: 'queued'))) {
            $where['queued'] = (bool)$queued;
        }
        
        if ($status = $io->option(name: 'status')) {
            $where['status'] = $status;
        }
        
        $appIds = $io->option(name: 'appId');
        
        if (!empty($appIds)) {
            $where['app_id'] = ['in' => $appIds];
        }
        
        $where['created_at'] = ['<' => $clock->now()->modify(sprintf('-%s hours', $io->option(name: 'hours')))->getTimestamp()];
        
        $deleted = $jobRepository->delete(where: $where);

        $io->success(sprintf(
            '%s jobs that were monitored longer than %s hours ago have been deleted successfully.',
            is_array($deleted) ? count($deleted) : iterator_count($deleted),
            $io->option(name: 'hours'),
        ));
        
        return static::SUCCESS;
    }
}