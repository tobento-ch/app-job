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
 
namespace Tobento\App\Job\Boot;

use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Migration\Boot\Migration;

class Job extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads job config',
            'implements jobs interface',
            'boots features',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        \Tobento\App\Database\Boot\Database::class,
    ];

    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(
        Config $config,
        Migration $migration,
    ): void {
        // Migration:
        $migration->install(\Tobento\App\Job\Migration\Job::class);
        
        // Load the config:
        $config = $config->load('job.php');
        
        // Interfaces:
        foreach($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
        
        // Migrate job repositories:
        $migration->install(\Tobento\App\Job\Migration\JobRepositories::class);
        
        // Features:
        foreach($config['features'] ?? [] as $feature) {
            if (is_string($feature)) {
                $feature = $this->app->make($feature);
            }
            
            $this->app->boot($feature);
        }
    }
}