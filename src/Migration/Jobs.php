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

use Tobento\Service\Dir\DirsInterface;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;

class Jobs implements MigrationInterface
{
    protected array $transFiles;
    
    /**
     * Create a new Job instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-job.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-job.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Job view and translation files.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new DirCopy(
                dir: $resources.'views/job/',
                destDir: $this->dirs->get('views').'job/',
                name: 'Job views',
                type: 'views',
                description: 'Job views.',
            ),
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Translation files.',
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
            new DirDelete(
                dir: $this->dirs->get('views').'job/',
                name: 'Job views',
                type: 'views',
                description: 'Job views.',
            ),
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Translation files.',
            ),
        );
    }
}