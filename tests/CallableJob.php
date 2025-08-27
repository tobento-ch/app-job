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
 
namespace Tobento\App\Job\Test;

use Tobento\Service\Queue\CallableJob as Job;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobSkipException;

final class CallableJob extends Job
{
    private int $processed = 0;
    private null|JobInterface $handledJob = null;
    
    public function __construct(
        private array $payload = [],
        private bool $failingJob = false,
        private bool $skippingJob = false,
        null|string $id = null,
    ) {
        $this->id = $id;
    }
    
    public function processed(): int
    {
        return $this->processed;
    }
    
    public function handledJob(): null|JobInterface
    {
        return $this->handledJob;
    }
    
    public function getPayload(): array
    {
        $this->payload['skipping'] = $this->skippingJob;
        $this->payload['failing'] = $this->failingJob;
        
        return $this->payload;
    }
    
    public function handleJob(JobInterface $job): void
    {
        $skipping = $job->getPayload()['skipping'] ?? false;
        
        if ($skipping) {
            throw new JobSkipException(message: 'skipped job');
        }
        
        $failing = $job->getPayload()['failing'] ?? false;
        
        if ($failing) {
            throw new \Exception('failing job');
        }
        
        $this->handledJob = $job;
        $this->processed++;
    }
}