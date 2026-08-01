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
 
namespace Tobento\App\Job\Controller;

use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Http\Exception\HttpException;
use Tobento\App\Job\Crud\JobRequeueBulkAction;
use Tobento\App\Job\JobRepositoryInterface;
use function Tobento\App\Translation\trans;

class JobCrudController extends AbstractCrudController
{
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'jobs';
    
    /**
     * Create a new JobCrudController instance.
     *
     * @param JobRepositoryInterface $repository
     * @param bool $findAppRecursive
     */
    public function __construct(
        JobRepositoryInterface $repository,
        protected bool $findAppRecursive = false,
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if (in_array($action->name(), ['store', 'update'])) {
            return [];
        }
        
        if ($action->name() !== 'index') {
            yield new Field\PrimaryId(name: 'id');
        }
        
        yield new Field\Select(name: 'status', label: trans('Status'))
            ->group(trans('Job'))
            ->options([
                'pending' => trans('pending'),
                'completed' => trans('completed'),
                'failed' => trans('failed'),
                'skipped' => trans('skipped'),
            ])
            ->formatValue(new Field\Formatter\Badge(classes: [
                'pending' => 'text-info',
                'completed' => 'text-success',
                'failed' => 'text-error',
                'skipped' => 'text-warning',
            ]));
        
        yield new Field\Text(name: 'app_id', label: trans('App ID'))
            ->group(trans('Job'));
        
        yield new Field\Text(name: 'name', label: trans('Name'))
            ->group(trans('Job'));
        
        yield new Field\Text(name: 'job_id', label: trans('Job ID'))
            ->type('number')
            ->group(trans('Job'));
        
        yield new Field\Text(name: 'queue', label: trans('Queue'))
            ->group(trans('Job'));
        
        yield new Field\Radios(name: 'queued', label: trans('Queued'))
            ->group(trans('Job'))
            ->options(['0' => trans('No'), '1' => trans('Yes')])
            ->formatValue(new Field\Formatter\Badge(classes: [
                '0' => 'text-error',
                '1' => 'text-success',
            ]));
        
        yield new Field\Text(name: 'created_at', label: trans('Created At'))
            ->type('datetime-local')
            ->group(trans('Job'))
            ->formatValue(new Field\Formatter\Date(format: 'EEEE, dd. MMMM yyyy, HH:mm'));
        
        yield new Field\Textarea(name: 'payload', label: trans('Payload'))
            ->group(trans('Job'));
        
        yield new Field\Textarea(name: 'parameters', label: trans('Parameters'))
            ->group(trans('Job'));
        
        yield new Field\Text(name: 'retries', label: trans('Attempts'))
            ->group(trans('Run Details'))
            ->type('number');
        
        yield new Field\Text(name: 'run_at', label: trans('Run At'))
            ->type('datetime-local')
            ->group(trans('Run Details'))
            ->formatValue(new Field\Formatter\Date(format: 'EEEE, dd. MMMM yyyy, HH:mm'));
        
        yield new Field\Text(name: 'runtime_seconds', label: trans('Runtime In Seconds'))
            ->group(trans('Run Details'));
        
        yield new Field\Text(name: 'memory_usage_bytes', label: trans('Memory Usage In Bytes'))
            ->group(trans('Run Details'));

        yield new Field\Textarea(name: 'exception', label: trans('Exception'))
            ->group(trans('Run Details'));
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        $requeueJob = new Button\Form(label: trans('Requeue Job'), group: 'entity')
            ->name('requeueJob')
            ->linkToRoute('jobs.requeue', function(EntityInterface $entity): array {
                return ['id' => $entity->id()];
            });
        
        yield new Action\Index(title: trans('Jobs'))
            ->addButton($requeueJob)
            ->displayButtonIf('requeueJob', fn (EntityInterface $entity): bool => !$entity->get('queued'))
            ->ajaxButtonAction('requeueJob')
            ->groupButtons(
                except: ['show'],
                button: new Button\Dropdown(label: '', icon: 'dots', group: 'entity')
                    ->name('more')
                    ->raw(),
            );

        yield new Action\Delete();

        yield new Action\BulkDelete();

        yield new JobRequeueBulkAction(
            findAppRecursive: $this->findAppRecursive,
        );

        yield new Action\Show(trans('Job Details'));
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [
            ...new Filter\Fields()
                ->fields($action->fields())
                ->except('app_id', 'queue')
                ->toFilters(),
            
            new Filter\Select(name: 'app_id', field: 'app_id')
                ->options($this->repository()->distinctValues('app_id'))
                ->group('field'),
            
            new Filter\Select(name: 'queue', field: 'queue')
                ->options($this->repository()->distinctValues('queue'))
                ->group('field'),
            
            new Filter\FieldsSortOrder(),
            
            new Filter\ModalButton()->group('header'),
            
            new Filter\Group(name: 'group-columns')->group('modal')->label(trans('Columns'))->open(false),
            
            new Filter\Columns()
                ->group('group-columns')
                ->default('status', 'name', 'app_id', 'retries', 'queue', 'actions'),
            
            new Filter\Group(name: 'group-pagination')->group('modal')->label(trans('Pagination'))->open(false),
            
            new Filter\PaginationItemsPerPage()
                ->group('group-pagination')
                ->open(false),
            
            new Filter\Pagination()->group('footer'),
        ];
    }
    
    public function repository(): JobRepositoryInterface
    {
        return $this->repository;
    }
}