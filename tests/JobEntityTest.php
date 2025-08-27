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

use PHPUnit\Framework\TestCase;
use Tobento\App\Job\JobEntity;
use Tobento\App\Job\JobEntityInterface;
use Tobento\Service\Queue\JobInterface;

class JobEntityTest extends TestCase
{
    public function testThatImplementsJobEntityInterface()
    {
        $this->assertInstanceof(JobEntityInterface::class, new JobEntity());
    }
    
    public function testIdMethod()
    {
        $this->assertSame(0, (new JobEntity([]))->id());
        $this->assertSame(3, (new JobEntity(['id' => 3]))->id());
        $this->assertSame(3, (new JobEntity(['id' => '3']))->id());
    }
    
    public function testAppIdMethod()
    {
        $this->assertSame('', (new JobEntity([]))->appId());
        $this->assertSame('', (new JobEntity(['app_id' => '']))->appId());
        $this->assertSame('foo', (new JobEntity(['app_id' => 'foo']))->appId());
    }
    
    public function testJobIdMethod()
    {
        $this->assertSame('', (new JobEntity([]))->jobId());
        $this->assertSame('', (new JobEntity(['job_id' => '']))->jobId());
        $this->assertSame('foo', (new JobEntity(['job_id' => 'foo']))->jobId());
    }
    
    public function testQueueNameMethod()
    {
        $this->assertSame('', (new JobEntity([]))->queueName());
        $this->assertSame('foo', (new JobEntity(['queue' => 'foo']))->queueName());
    }
    
    public function testIsQueuedMethod()
    {
        $this->assertFalse((new JobEntity([]))->isQueued());
        $this->assertFalse((new JobEntity(['queued' => false]))->isQueued());
        $this->assertFalse((new JobEntity(['queued' => '0']))->isQueued());
        $this->assertTrue((new JobEntity(['queued' => true]))->isQueued());
        $this->assertTrue((new JobEntity(['queued' => '1']))->isQueued());
    }

    public function testHasMethod()
    {
        $entity = new JobEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertTrue($entity->has(name: 'foo'));
        $this->assertTrue($entity->has(name: 'bar.baz'));
        $this->assertFalse($entity->has(name: 'baz'));
    }
    
    public function testGetMethod()
    {
        $entity = new JobEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertSame('Foo', $entity->get(name: 'foo'));
        $this->assertSame('Baz', $entity->get(name: 'bar.baz'));
        $this->assertSame(null, $entity->get(name: 'baz'));
        $this->assertSame('default', $entity->get(name: 'baz', default: 'default'));
    }
    
    public function testToJobMethod()
    {
        $entity = new JobEntity([
            'job_id' => 'ID',
            'name' => 'Name',
            'payload' => json_encode(['foo' => 'bar']),
            'parameters' => json_encode(['Tobento\Service\Queue\Parameter\Delay' => ['seconds' => 60]]),
        ]);
        
        $job = $entity->toJob();
        
        $this->assertInstanceof(JobInterface::class, $job);
        $this->assertSame('ID', $job->getId());
        $this->assertSame('Name', $job->getName());
        $this->assertSame(['foo' => 'bar'], $job->getPayload());
        $this->assertSame(['Tobento\Service\Queue\Parameter\Delay' => ['seconds' => 60]], $job->parameters()->jsonSerialize());
    }
    
    public function testToJobMethodWithoutAttributes()
    {
        $entity = new JobEntity([]);
        $job = $entity->toJob();
        
        $this->assertInstanceof(JobInterface::class, $job);
        $this->assertSame('', $job->getId());
        $this->assertSame('', $job->getName());
        $this->assertSame([], $job->getPayload());
        $this->assertSame([], $job->parameters()->jsonSerialize());
    }
    
    public function testToArrayMethod()
    {
        $entity = new JobEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertSame([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ], $entity->toArray());
    }
}