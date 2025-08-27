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

namespace Tobento\App\Job\Test\Console;

use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Tobento\App\Job\Console\PurgeJobsCommand;
use Tobento\App\Job\JobEntityFactory;
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\App\Job\JobStorageRepository;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Storage\InMemoryStorage;

class PurgeJobsCommandTest extends TestCase
{
    protected function createContainer(): ContainerInterface
    {
        $container = new Container();
        $container->set(ClockInterface::class, new FrozenClock());
        
        $container->set(JobRepositoryInterface::class, function () {
            return new JobStorageRepository(
                storage: new InMemoryStorage([]),
                table: 'jobs',
                entityFactory: new JobEntityFactory(),
            );
        });
        
        return $container;
    }
    
    public function testPurgesJobsOlderThanTheDefault24Hours()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'failed', 'created_at' => (new FrozenClock())->modify('+23 hours')->now()->getTimestamp()]);
        $jobRepository->create(['status' => 'pending']);
        $jobRepository->create(['status' => 'failed']);
        
        (new TestCommand(command: PurgeJobsCommand::class))
            ->expectsOutput('3 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
            ->expectsExitCode(0)
            ->execute($container);
    }
    
    public function testPurgesJobsWithSpecificHours()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+11 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'failed', 'created_at' => (new FrozenClock())->modify('+10 hours')->now()->getTimestamp()]);
        $jobRepository->create(['status' => 'failed']);
        
        (new TestCommand(
            command: PurgeJobsCommand::class,
            input: ['--hours' => '10']
        ))
        ->expectsOutput('2 jobs that were monitored longer than 10 hours ago have been deleted successfully.')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testPurgesJobsWithQueuedOptionTrue()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'failed', 'queued' => true]);
        $jobRepository->create(['status' => 'failed']);
        
        (new TestCommand(
            command: PurgeJobsCommand::class,
            input: ['--queued' => true]
        ))
        ->expectsOutput('1 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testPurgesJobsWithQueuedOptionFalse()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'failed', 'queued' => false]);
        $jobRepository->create(['status' => 'failed']);
        
        (new TestCommand(
            command: PurgeJobsCommand::class,
            input: ['--queued' => false]
        ))
        ->expectsOutput('1 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testPurgesJobsWithSpecificStatus()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'skipped']);
        $jobRepository->create(['status' => 'failed']);
        
        (new TestCommand(
            command: PurgeJobsCommand::class,
            input: ['--status' => 'skipped']
        ))
        ->expectsOutput('1 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testPurgesJobsWithSpecificAppIds()
    {
        $container = $this->createContainer();
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        $jobRepository = $container->get(JobRepositoryInterface::class);
        
        $jobRepository->create(['status' => 'failed']);
        $jobRepository->create(['status' => 'failed', 'app_id' => 'root']);
        $jobRepository->create(['status' => 'failed', 'app_id' => 'frontend']);
        
        (new TestCommand(
            command: PurgeJobsCommand::class,
            input: ['--appId' => ['root', 'frontend']]
        ))
        ->expectsOutput('2 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testNoJobsArePurged()
    {
        $container = $this->createContainer();
        
        (new TestCommand(command: PurgeJobsCommand::class))
            ->expectsOutput('0 jobs that were monitored longer than 24 hours ago have been deleted successfully.')
            ->expectsExitCode(0)
            ->execute($container);
    }
}