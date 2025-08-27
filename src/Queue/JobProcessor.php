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
 
namespace Tobento\App\Job\Queue;

use Tobento\App\Job\JobRepositoryInterface;
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\QueueInterface;
use Throwable;

class JobProcessor implements JobProcessorInterface
{
    /**
     * Create a new JobProcessor.
     *
     * @param string $appId
     * @param JobProcessorInterface $jobProcessor
     * @param JobRepositoryInterface $jobRepository
     */
    public function __construct(
        protected string $appId,
        protected JobProcessorInterface $jobProcessor,
        protected JobRepositoryInterface $jobRepository,
    ) {}

    /**
     * Add a job handler for the specified job.
     *
     * @param string $job
     * @param string|JobHandlerInterface $handler
     * @return static $this
     */
    public function addJobHandler(string $job, string|JobHandlerInterface $handler): static
    {
        $this->jobProcessor->addJobHandler(job: $job, handler: $handler);
        return $this;
    }
    
    /**
     * Process job.
     *
     * @param JobInterface $job
     * @return void
     * @throws Throwable
     */
    public function processJob(JobInterface $job): void
    {
        $this->jobProcessor->processJob(job: $job);
    }
    
    /**
     * Before process job.
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws Throwable
     */
    public function beforeProcessJob(JobInterface $job): JobInterface
    {
        $this->jobRepository->startingJob(job: $job);
        
        return $this->jobProcessor->beforeProcessJob(job: $job);
    }
    
    /**
     * After process job.
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws Throwable
     */
    public function afterProcessJob(JobInterface $job): JobInterface
    {
        $job = $this->jobProcessor->afterProcessJob(job: $job);
        
        $this->jobRepository->finishedJob(job: $job);
        
        return $job;
    }
    
    /**
     * Process pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPushingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        $job = $this->jobProcessor->processPushingJob(job: $job, queue: $queue);
        
        $this->jobRepository->pushJob(job: $job, queue: $queue, appId: $this->appId);
        
        return $job;
    }
    
    /**
     * Process popping job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPoppingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        return $this->jobProcessor->processPoppingJob(job: $job, queue: $queue);
    }
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     * @throws Throwable
     */
    public function processFailedJob(JobInterface $job, Throwable $e): void
    {
        $this->jobRepository->failedJob(job: $job, exception: $e);
        
        $this->jobProcessor->processFailedJob(job: $job, e: $e);
    }
}