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

namespace Tobento\App\Job\Crud;

use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Crud\Action\AbstractAction;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Action\BulkActionInterface;
use Tobento\App\Crud\Action\HasActionProcessor;
use Tobento\App\Crud\Action\Traits;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\Apps\AppFinder;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\View\ViewInterface;
use Throwable;

final class JobRequeueBulkAction extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    use Traits\HandleBulk;
    
    /**
     * Create a new JobRequeueBulkAction instance.
     *
     * @param null|string $title
     * @param bool $findAppRecursive
     */
    public function __construct(
        null|string $title = null,
        protected bool $findAppRecursive = false,
    ) {
        $this->title = $title ?: 'Requeue';
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        $this->view('job/crud/bulk/requeue-jobs');
    }
    
    /**
     * Returns the name. Must be sluggable and only of [a-z-] characters.
     *
     * @return string
     */
    public function name(): string
    {
        return 'jobs-requeue';
    }
    
    /**
     * Returns the handler processing the action.
     *
     * @return callable(mixed...): \Psr\Http\Message\ResponseInterface
     */
    public function getHandler(): callable
    {
        return [$this, 'handle'];
    }
    
    /**
     * Handle action.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function handle(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        return $this->handleBulk(
            action: $this,
            actionProcessor: $actionProcessor,
            requester: $requester,
            responser: $responser,
        );
    }
    
    /**
     * Returns the process bulk action.
     *
     * @return callable
     */
    public function getBulkProcessAction(): callable
    {
        return [$this, 'processBulk'];
    }
    
    /**
     * Process bulk action.
     *
     * @param ResponserInterface $responser
     * @param TranslatorInterface $translator
     * @param AppInterface $application
     * @return void
     * @throws ActionProcessException
     */
    public function processBulk(ResponserInterface $responser, TranslatorInterface $translator, AppInterface $application): void
    {
        $input = $this->getInput();
        $ids = $input->get('ids', []);
        $jobRepository = $this->controller()->repository();
        $requeued = 0;
        
        foreach(array_values($ids) as $id) {
            
            if (is_null($jobEntity = $jobRepository->findById($id))) {
                continue;
            }
            
            $appFinder = new AppFinder(app: $application);
            
            $app = $this->findAppRecursive
                ? $appFinder->findByIdRecursive(id: $jobEntity->appId())
                : $appFinder->findById(id: $jobEntity->appId());

            if (is_null($app)) {
                continue;
            }

            if (!$app->has(QueuesInterface::class)) {
                continue;
            }

            $queues = $app->get(QueuesInterface::class);

            if (!$queues->has($jobEntity->queueName())) {
                continue;
            }

            $queue = $queues->get($jobEntity->queueName());

            // Only push to queue if job not exists anymore:
            $job = $queue->getJob($jobEntity->jobId());

            if ($job) {
                continue;
            }
            
            $job = $jobEntity->toJob();

            // update retry:
            $retry = $job->parameters()->get(Parameter\Retry::class);

            if (!is_null($retry) && $retry->isMaxReached()) {
                $job->parameter(new Parameter\Retry(max: $retry->max()+1, retried: $retry->retried()));
            }

            $queue->push($job);

            $requeued++;
        }

        $responser->messages()->add(
            level: 'info',
            message: $translator->trans(
                ':number of :total jobs requeued successfully.',
                [':number' => $requeued, ':total' => count($ids)]
            ),
        );
    }
    
    /**
     * Returns the html of action. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        return $view->render(
            view: $this->getView(),
            data: [
                'action' => $this,
            ],
        );
    }
    
    /**
     * Returns whether to display the button to perform the action.
     *
     * @return bool
     */
    public function displayButton(): bool
    {
        return true;
    }
}