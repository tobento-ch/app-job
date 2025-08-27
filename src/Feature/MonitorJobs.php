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
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\App\Job\Queue\FailedJobHandler;
use Tobento\App\Job\Queue\JobProcessor;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\JobProcessorInterface;

class MonitorJobs extends Boot
{
    public const INFO = [
        'boot' => [
            'Monitors jobs',
        ],
    ];
    
    public const BOOT = [
        //
    ];

    /**
     * Boot application services.
     *
     * @param AppInterface $app
     * @return void
     */
    public function boot(AppInterface $app): void
    {
        $app->on(
            JobProcessorInterface::class,
            static function(JobProcessorInterface $jobProcessor, AppInterface $app): JobProcessorInterface {
                return new JobProcessor(
                    appId: $app->id(),
                    jobProcessor: $jobProcessor,
                    jobRepository: $app->get(JobRepositoryInterface::class),
                );
            }
        )->priority(-1000000);
    }
}