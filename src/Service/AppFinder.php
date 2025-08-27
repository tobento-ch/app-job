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
 
namespace Tobento\App\Job\Service;

use Tobento\App\AppInterface;
use Tobento\Apps\AppsInterface;

final class AppFinder
{
    /**
     * Create a new AppFinder instance.
     *
     * @param AppInterface $app
     */
    public function __construct(
        private AppInterface $app,
    ) {}

    /**
     * Returns the app found if found, otherwise null.
     *
     * @return null|AppInterface
     */
    public function findById(string $id): null|AppInterface
    {
        if ($id === $this->app->id()) {
            return $this->app;
        }
        
        if (! $this->app->has(AppsInterface::class)) {
            return null;
        }
        
        $apps = $this->app->get(AppsInterface::class);
        
        if ($id === 'root') {
            if (! $apps->has($this->app->id())) {
                return null;
            }
            
            return $apps->get($this->app->id())->rootApp();
        }
        
        if (! $apps->has($id)) {
            return null;
        }
        
        $app = $apps->get($id)->app();
        $app->booting();
        
        return $app;
    }
}