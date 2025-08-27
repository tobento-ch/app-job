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
use Tobento\App\Job\Feature\Jobs;
use Tobento\App\Job\Feature\MonitorJobs;
use Tobento\Apps\AppsInterface;
use Tobento\Service\Seeder\SeedInterface;
use Tobento\Service\Queue\QueueInterface;

class AppsJobsTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Job\Test\App\Frontend::class);
        $app->boot(\Tobento\App\Job\Boot\Job::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Job\Controller\JobCrudController::class;
    }
    
    protected function getSeedDefinition(): null|\Closure
    {
        return function (SeedInterface $seed): array {
            return [
                'status' => 'pending',
                'queue' => 'file',
                'queued' => true,
                'app_id' => 'root',
                'job_id' => 'ID',
                'name' => 'Tobento\App\Job\Test\CallableJob',
                'payload' => json_encode([]),
                'parameters' => json_encode([]),
            ];
        };
    }
    
    public function testRequeueActionFromAnotherApp()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
            MonitorJobs::class,
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory(['app_id' => 'frontend', 'queued' => false])->times(1)->create();
        
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        $frontendApp = $apps->get('frontend')->app();
        $frontendApp->booting();
        $queue = $frontendApp->get(QueueInterface::class);
        $queue->clear();
        
        $this->assertSame(0, $queue->size());
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        $http->followRedirects()->assertStatus(200);
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testBulkRequeueActionFromAnotherApp()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'jobs-requeue'))->body([
            'ids' => [2, 5],
        ]);
        
        $this->getSeedFactory()->times(4)->create();
        $this->getSeedFactory(['app_id' => 'frontend', 'job_id' => 'foo'])->times(1)->create();
        
        $app = $this->bootingApp();
        $apps = $app->get(AppsInterface::class);
        $frontendApp = $apps->get('frontend')->app();
        $frontendApp->booting();
        $queue = $frontendApp->get(QueueInterface::class);
        $queue->clear();
        
        $this->assertSame(0, $queue->size());
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
        
        $this->assertSame(2, $queue->size());
    }
}