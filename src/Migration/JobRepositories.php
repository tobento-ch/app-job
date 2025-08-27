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

namespace Tobento\App\Job\Migration;

use Tobento\App\Job\JobRepositoryInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

class JobRepositories implements MigrationInterface
{
    /**
     * Create a new JobRepositories instance.
     *
     * @param JobRepositoryInterface $jobRepository
     */
    public function __construct(
        protected JobRepositoryInterface $jobRepository,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Job repositories.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            RepositoryAction::newOrNull(
                repository: $this->jobRepository,
                description: 'Job resource.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            RepositoryDeleteAction::newOrNull(
                repository: $this->jobRepository,
                description: 'Job resource',
            ),
        );
    }
}