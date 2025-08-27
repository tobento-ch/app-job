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

interface JobEntityInterface
{
    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int;

    /**
     * Returns the app id.
     *
     * @return string
     */
    public function appId(): string;
    
    /**
     * Returns the job id.
     *
     * @return string
     */
    public function jobId(): string;
    
    /**
     * Returns the queue name the job was queued.
     *
     * @return string
     */
    public function queueName(): string;
    
    /**
     * Returns the whether the job is queued or not.
     *
     * @return bool
     */
    public function isQueued(): bool;

    /**
     * Returns whether an attribute exists or not.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed;
    
    /**
     * Returns the job.
     *
     * @return JobInterface
     */
    public function toJob(): JobInterface;
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array;
}