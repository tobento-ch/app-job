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

use Tobento\Service\Collection\Collection;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\ParametersFactory;

class JobEntity implements JobEntityInterface
{
    /**
     * @var Collection
     */
    protected Collection $attributes;
    
    /**
     * Create a new JobEntity instance.
     *
     * @param array $attributes
     */
    public function __construct(
        array $attributes = [],
    ) {
        $this->attributes = new Collection($attributes);
    }

    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int
    {
        return (int)$this->get('id');
    }

    /**
     * Returns the app id.
     *
     * @return string
     */
    public function appId(): string
    {
        return $this->get('app_id', '');
    }
    
    /**
     * Returns the job id.
     *
     * @return string
     */
    public function jobId(): string
    {
        return $this->get('job_id', '');
    }
    
    /**
     * Returns the queue name the job was queued.
     *
     * @return string
     */
    public function queueName(): string
    {
        return $this->get('queue', '');
    }
    
    /**
     * Returns the whether the job is queued or not.
     *
     * @return bool
     */
    public function isQueued(): bool
    {
        return (bool)$this->get('queued');
    }

    /**
     * Returns whether an attribute exists or not.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->attributes->has($name);
    }
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->attributes->get($name, $default);
    }
    
    /**
     * Returns the job.
     *
     * @return JobInterface
     */
    public function toJob(): JobInterface
    {
        return new Job(
            id: $this->jobId(),
            name: $this->get('name', ''),
            payload: json_decode($this->get('payload', '{}'), true),
            parameters: (new ParametersFactory())->createFromJsonString($this->get('parameters', '{}')),
        );
    }
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes->toArray();
    }
}