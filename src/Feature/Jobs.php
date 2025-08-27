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
 
namespace Tobento\App\Job\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\Job\Controller\JobCrudController;
use Tobento\Service\Acl\AclInterface;
use Tobento\Service\Console\ConsoleInterface;
use Tobento\Service\Menu\MenusInterface;
use Tobento\Service\Routing\RouterInterface;
use function Tobento\App\Translation\trans;

class Jobs extends Boot
{
    public const INFO = [
        'boot' => [
            'routes jobs',
        ],
    ];

    public const BOOT = [
        \Tobento\App\User\Boot\Acl::class,
        \Tobento\App\User\Boot\User::class,
        \Tobento\App\User\Boot\HttpUserErrorHandler::class,
        Crud::class,
    ];
    
    /**
     * Create a new Jobs instance.
     *
     * @param null|string $menu The menu name or null if none.
     * @param string $menuLabel The menu label.
     * @param null|string $menuParent The menu parent or null if none.
     * @param bool $withAcl
     */
    public function __construct(
        protected null|string $menu = 'main',
        protected string $menuLabel = 'Jobs',
        protected null|string $menuParent = null,
        protected bool $withAcl = true,
    ) {}

    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @param Migration $migration
     * @param Crud $crud
     * @return void
     */
    public function boot(AppInterface $app, Migration $migration, Crud $crud): void
    {
        $migration->install(\Tobento\App\Job\Migration\Jobs::class);
        
        $acl = $app->get(AclInterface::class);
        $acl->rule('jobs')->description('User can access jobs.');
        
        if ($this->withAcl === false) {
            $acl->addPermissions(['jobs']);
        }

        // Routes:
        $router = $app->get(RouterInterface::class);
        
        // Routes:
        $crud->routeController(
            JobCrudController::class,
            middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'jobs.index' => 'jobs',
                        'jobs.show' => 'jobs',
                        'jobs.delete' => 'jobs',
                        'jobs.bulk' => 'jobs',
                    ],
                ]
            ],
            except: ['store', 'create', 'update', 'edit', 'copy'],
            localized: true,
        );
        
        $router->post(
            uri: '{?locale}/jobs/{id}/requeue',
            handler: \Tobento\App\Job\Action\JobRequeueAction::class,
        )->name('jobs.requeue')
         ->middleware(['can', 'permission' => 'jobs']);
        
        // Console commands:
        $app->on(ConsoleInterface::class, static function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\App\Job\Console\PurgeJobsCommand::class);
        });
        
        // Menu:
        if ($this->menu) {
            $app->on(
                MenusInterface::class,
                function(MenusInterface $menus, AclInterface $acl, RouterInterface $router) {
                    if ($acl->can('jobs')) {
                        $menus->menu($this->menu)
                            ->link($router->url('jobs.index'), trans($this->menuLabel))
                            ->parent($this->menuParent)
                            ->id('jobs.index');
                    }
                }
            );
        }
    }
}