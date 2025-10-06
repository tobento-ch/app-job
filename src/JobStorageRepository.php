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
use Tobento\Service\Queue\JobSkipException;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\StorageEntityFactoryInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Throwable;

class JobStorageRepository extends StorageRepository implements JobRepositoryInterface
{
    /**
     * Returns the configured columns.
     *
     * @return iterable<ColumnInterface>|ColumnsInterface
     */
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            new Column\Id(),
            new Column\Text('status')->type(length: 100),
            new Column\Text('name'),
            new Column\Text('job_id'),
            new Column\Text('app_id'),
            new Column\Text('queue'),
            new Column\Boolean('queued'),
            new Column\Integer(name: 'retries', type: 'tinyInt')
                ->type(length: 5, unsigned: true, nullable: false, default: 0),
            
            // must be of type timestamp as purge commands uses timestamps!
            new Column\Datetime(name: 'created_at', type: 'timestamp')->autoCreate(),
            
            new Column\Datetime('run_at'),
            new Column\FloatCol('runtime_seconds')
                ->type(nullable: true, precision: 3)
                ->read(fn (null|float $value): float => is_null($value) ? 0 : round($value, 3)),
            new Column\Text('memory_usage_bytes')->type(nullable: true),
            new Column\Text(name: 'payload', type: 'text'),
            new Column\Text(name: 'exception', type: 'text'),
            new Column\Text(name: 'parameters', type: 'text'),
        ];
    }
    
    /**
     * Push job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param string $appId
     * @return void
     */
    public function pushJob(JobInterface $job, QueueInterface $queue, string $appId): void
    {
        if ($this->findOne(where: ['job_id' => $job->getId()])) {

            $this->update(where: ['job_id' => $job->getId()], attributes: [
                'parameters' => (string)$job->parameters(),
                'queued' => true,
                'status' => 'pending',
            ]);
            
            return;
        }

        $this->create([
            'status' => 'pending',
            'name' => $job->getName(),
            'app_id' => $appId,
            'queue' => $queue->name(),
            'queued' => true,
            'job_id' => $job->getId(),
            'payload' => json_encode($job->getPayload()),
            'parameters' => (string)$job->parameters(),
        ]);
    }
    
    /**
     * Starting job.
     *
     * @param JobInterface $job
     * @return void
     */
    public function startingJob(JobInterface $job): void
    {
        $this->update(where: ['job_id' => $job->getId()], attributes: [
            'status' => 'running',
            'run_at' => date('c'),
            'queued' => false,
        ]);
    }
    
    /**
     * Finished job.
     *
     * @param JobInterface $job
     * @return void
     */
    public function finishedJob(JobInterface $job): void
    {
        $attributes = ['status' => 'completed'];
        
        if ($job->parameters()->has(Parameter\Monitor::class)) {
            $monitor = $job->parameters()->get(Parameter\Monitor::class);
            
            $attributes['runtime_seconds'] = $monitor->runtimeInSeconds();
            $attributes['memory_usage_bytes'] = $monitor->memoryUsage();
            $attributes['exception'] = null;
        }
        
        $this->update(where: ['job_id' => $job->getId()], attributes: $attributes);
    }
    
    /**
     * Failed job.
     *
     * @param JobInterface $job
     * @param Throwable $exception
     * @return void
     */
    public function failedJob(JobInterface $job, Throwable $exception): void
    {
        $status = $exception instanceof JobSkipException ? 'skipped' : 'failed';
        
        $retried = $job->parameters()->get(Parameter\Retry::class)?->retried();
        $maxReached = $job->parameters()->get(Parameter\Retry::class)?->isMaxReached();
        
        if ($maxReached) {
            $status = 'failed';
        }
        
        $this->update(
            where: [
                'job_id' => $job->getId(),
            ],
            attributes: [
                'status' => $status,
                'retries' => $retried ?: 1,
                'exception' => sprintf(
                    "Exception:\n%s\n\nFile:\n%s\n\nLine:\n%s\n\nMessage:\n%s\n\nTrace:\n%s",
                    $exception::class,
                    $exception->getFile(),
                    $exception->getLine(),
                    $exception->getMessage(),
                    $exception->getTraceAsString(),
                ),
            ],
        );
    }
}