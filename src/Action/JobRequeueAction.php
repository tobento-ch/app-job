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
 
namespace Tobento\App\Job\Action;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Http\Exception\HttpException;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\App\Job\Service\AppFinder;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

class JobRequeueAction
{
    /**
     * Requeues the job.
     *
     * @param int|string $id The task id to run.
     * @param AppInterface $app
     * @param JobRepositoryInterface $jobRepository
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function __invoke(
        int|string $id,
        AppInterface $app,
        JobRepositoryInterface $jobRepository,
        TranslatorInterface $translator,
        RouterInterface $router,
        ResponserInterface $responser,
    ): ResponseInterface {
        if (is_null($jobEntity = $jobRepository->findById($id))) {
            throw new NotFoundException();
        }
        
        $app = (new AppFinder(app: $app))->findById(id: $jobEntity->appId());
        
        if (is_null($app)) {
            throw new HttpException(statusCode: 422, message: 'App with the ID :id not found.');
        }

        if (!$app->has(QueuesInterface::class)) {
            throw new HttpException(statusCode: 422, message: 'Queues not exists');
        }
        
        $queues = $app->get(QueuesInterface::class);
        
        if (!$queues->has($jobEntity->queueName())) {
            throw new HttpException(statusCode: 422, message: 'Queue not exists');
        }
        
        $queue = $queues->get($jobEntity->queueName());
        
        // Only push to queue if job not exists anymore:
        $job = $queue->getJob($jobEntity->jobId());
        
        if ($job) {
            $responser->messages()->add(
                level: 'notice',
                message: $translator->trans(
                    'Job with the ID :id not requeued as still queued.',
                    [':id' => $job->getId()]
                ),
            );
        } else {
            $job = $jobEntity->toJob();
            
            // update retry:
            $retry = $job->parameters()->get(Parameter\Retry::class);
            
            if (!is_null($retry) && $retry->isMaxReached()) {
                $job->parameter(new Parameter\Retry(max: $retry->max()+1, retried: $retry->retried()));
            }
            
            $queue->push($job);
            
            $responser->messages()->add(
                level: 'success',
                message: $translator->trans(
                    'Job with the ID :id requeued onto queue :name successfully.',
                    [':id' => $job->getId(), ':name' => $jobEntity->queueName()]
                ),
            );
        }
        
        return $responser->redirect($router->url('jobs.index'));
    }
}