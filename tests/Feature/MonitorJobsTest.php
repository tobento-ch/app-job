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

namespace Tobento\App\Job\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Job\Feature\MonitorJobs;
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\App\Job\Test\CallableJob;
use Tobento\Service\Queue\QueuesInterface;

class MonitorJobsTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Job\Boot\Job::class);
        $app->boot(\Tobento\App\Queue\Boot\Queue::class);
        return $app;
    }
    
    public function testJobGetsSavedToRepositoryIfSendToQueue()
    {
        $this->fakeConfig()->with('job.features', [
            MonitorJobs::class,
        ]);
        
        $fakeQueue = $this->fakeQueue();
        
        $app = $this->bootingApp();
        
        $this->assertSame(0, $app->get(JobRepositoryInterface::class)->count());
        
        $app->get(QueuesInterface::class)->queue(name: 'file')->push(new CallableJob(id: 'foo'));
        
        $this->assertSame(1, $app->get(JobRepositoryInterface::class)->count());
    }
}