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

use Tobento\App\AppInterface;
use Tobento\App\Crud\Action\AbstractAction;
use Tobento\App\Crud\Action\BulkActionInterface;
use Tobento\App\Crud\Action\HasActionProcessor;
use Tobento\App\Crud\Exception\ActionProcessException;
use Tobento\App\Job\Service\AppFinder;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Translation\TranslatorInterface;
use Tobento\Service\View\ViewInterface;
use Throwable;

final class JobRequeueBulkAction extends AbstractAction implements BulkActionInterface
{
    use HasActionProcessor;
    
    /**
     * Create a new JobRequeueBulkAction instance.
     *
     * @param null|string $title
     */
    public function __construct(
        null|string $title = null,
    ) {
        $this->title = $title ?: 'Requeue';
        $this->route('{name}.bulk', function(): array {
            return ['name' => $this->name()];
        });
        
        $this->linkToAction('index');
        $this->view('job/crud/bulk/requeue-jobs');
    }
    
    /**
     * Create a new instance.
     *
     * @param null|string $title
     * @return static
     */
    public static function new(
        null|string $title = null
    ): static {
        return new static($title);
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
            
            $app = (new AppFinder(app: $application))->findById(id: $jobEntity->appId());

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