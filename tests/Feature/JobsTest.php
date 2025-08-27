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
use Tobento\Service\Seeder\SeedInterface;

class JobsTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
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
    
    public function testIndexAction()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $this->getSeedFactory()->times(2)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Jobs')
            ->assertCrudIndexHeaderColumnsExists(columns: [
                'status', 'name', 'app_id', 'retries', 'queue', 'actions',
            ])
            ->assertCrudIndexEntityCount(2);
    }
    
    public function testIndexActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "jobs" permission.');
    }
    
    public function testCreateActionIsDisabled()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()->assertStatus(404);
    }
    
    public function testStoreActionIsDisabled()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([]);

        $http->response()->assertStatus(404);
    }

    public function testEditActionIsDisabled()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testUpdateActionIsDisabled()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([]);
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testShowAction()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Job Details')
            ->assertCrudFormFieldExists(field: 'status')
            ->assertCrudFormFieldExists(field: 'name')
            ->assertCrudFormFieldExists(field: 'app_id')
            ->assertCrudFormFieldExists(field: 'retries')
            ->assertCrudFormFieldExists(field: 'queue')
            ->assertCrudFormFieldExists(field: 'created_at')
            ->assertCrudFormFieldExists(field: 'run_at')
            ->assertCrudFormFieldExists(field: 'runtime_seconds')
            ->assertCrudFormFieldExists(field: 'memory_usage_bytes')
            ->assertCrudFormFieldExists(field: 'job_id')
            ->assertCrudFormFieldExists(field: 'payload')
            ->assertCrudFormFieldExists(field: 'parameters');
    }
    
    public function testCopyActionIsDisabled()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testDeleteAction()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(1);

        $this->assertSame(1, $this->getCrudRepository()->count());
    }
    
    public function testDeleteActionFailsWithoutPermission()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));

        $this->getSeedFactory()->times(2)->create();

        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "jobs" permission.');
    }
    
    public function testRequeueAction()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory(['queued' => false])->times(1)->create();
        
        $http->response()->assertStatus(302)->assertLocation($this->generateIndexUri());
        $http->followRedirects()->assertStatus(200);
        
        $fakeQueue->queue(name: 'file')->assertPushedTimes('Tobento\App\Job\Test\CallableJob', 1);
    }
    
    public function testRequeueActionFailsWithoutPermission()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: true),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "jobs" permission.');
    }
    
    public function testRequeueActionRequeuesOnce()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory()->times(1)->create();
        $http->followRedirects()->assertStatus(200);
        
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        $http->followRedirects()->assertStatus(200);
        
        $fakeQueue->queue(name: 'file')->assertPushedTimes('Tobento\App\Job\Test\CallableJob', 1);
    }
    
    public function testRequeueActionFailsIfQueueNotFound()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory(['queue' => 'unknown'])->times(1)->create();
        
        $http->response()->assertStatus(422);
    }
    
    public function testRequeueActionFailsIfAppNotFound()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: 'jobs/1/requeue');
        
        $this->getSeedFactory(['app_id' => 'unknown'])->times(1)->create();
        
        $http->response()->assertStatus(422);
    }
    
    public function testBulkRequeueAction()
    {
        $this->fakeConfig()->with('job.features', [
            new Jobs(withAcl: false),
        ]);
        
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'jobs-requeue'))->body([
            'ids' => [2, 5],
        ]);
        
        $this->getSeedFactory()->times(4)->create();
        $this->getSeedFactory(['job_id' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(302)
            ->assertLocation($this->generateIndexUri());

        $http->followRedirects()
            ->assertStatus(200)
            ->assertCrudIndexEntityCount(5);
        
        $fakeQueue->queue(name: 'file')->assertPushedTimes('Tobento\App\Job\Test\CallableJob', 2);
    }
    
    public function testBulkRequeueActionWithoutPermission()
    {
        $fakeQueue = $this->fakeQueue();
        $http = $this->fakeHttp();
        $http->previousUri($this->generateIndexUri());
        $http->request(method: 'POST', uri: $this->generateBulkUri(action: 'jobs-requeue'))->body([
            'ids' => [2, 5],
        ]);
        
        $this->getSeedFactory()->times(4)->create();
        $this->getSeedFactory(['job_id' => 'foo'])->times(1)->create();
        
        $http->response()
            ->assertStatus(403)
            ->assertBodyContains('You don\'t have a required "jobs" permission.');
    }
}