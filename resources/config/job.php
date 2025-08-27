<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Job\Feature;
use Tobento\App\Job\JobEntityFactory;
use Tobento\App\Job\JobRepositoryInterface;
use Tobento\App\Job\JobStorageRepository;
use Tobento\Service\Database\DatabasesInterface;

return [

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Configure the features you wish to use or remove uneeded.
    |
    | See: https://github.com/tobento-ch/app-job#features
    |
    */
    
    'features' => [
        Feature\Jobs::class,
        Feature\MonitorJobs::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names as it may be used in other app bundles!
    |
    */
    
    'interfaces' => [
        JobRepositoryInterface::class =>
        static function(DatabasesInterface $databases, JobEntityFactory $entityFactory): JobRepositoryInterface {
            return new JobStorageRepository(
                storage: $databases->default('storage')->storage()->new(),
                table: 'jobs',
                entityFactory: $entityFactory,
            );
        },
    ],
    
];