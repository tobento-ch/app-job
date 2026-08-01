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
 
namespace Tobento\App\Job;

use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Throwable;

interface JobRepositoryInterface extends RepositoryInterface
{
    /**
     * Push job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param string $appId
     * @return void
     */
    public function pushJob(JobInterface $job, QueueInterface $queue, string $appId): void;
    
    /**
     * Starting job.
     *
     * @param JobInterface $job
     * @return void
     */
    public function startingJob(JobInterface $job): void;
    
    /**
     * Finished job.
     *
     * @param JobInterface $job
     * @return void
     */
    public function finishedJob(JobInterface $job): void;
    
    /**
     * Failed job.
     *
     * @param JobInterface $job
     * @param Throwable $exception
     * @return void
     */
    public function failedJob(JobInterface $job, Throwable $exception): void;
    
    /**
     * Returns distinct values for the given column as a key/value array.
     *
     * @param string $column The column name to extract unique values from.
     * @return array<string, string> The distinct values indexed by themselves.
     */
    public function distinctValues(string $column): array;
}