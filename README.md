# App Job

The app job lets you monitor queued or ran [jobs](https://github.com/tobento-ch/app-queue) from a web interface.

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Job Boot](#job-boot)
        - [Job Config](#job-config)
    - [Features](#features)
        - [Jobs Feature](#jobs-feature)
        - [Monitor Jobs Feature](#monitor-jobs-feature)
    - [Console](#console)
        - [Purge Jobs Command](#purge-jobs-command)
    - [Learn More](#learn-more)
        - [Monitor Jobs From Another App](#monitor-jobs-from-another-app)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app job project running this command.

```
composer require tobento/app-job
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Job Boot

The job boot does the following:

* installs and loads job config
* implements jobs interface
* boots [features](#features) configured in [job config](#job-config)

```php
use Tobento\App\AppFactory;
use Tobento\App\Job\JobRepositoryInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Job\Boot\Job::class);
$app->booting();

// Implemented interfaces:
$jobRepository = $app->get(JobRepositoryInterface::class);

// Run the app
$app->run();
```

You may install the [App Backend](https://github.com/tobento-ch/app-backend) and [boot the job](https://github.com/tobento-ch/app-backend#adding-boots) in the backend app.

### Job Config

The configuration for the job is located in the ```app/config/job.php``` file at the default App Skeleton config location where you can configure [features](#features) and more.

## Features

### Jobs Feature

The jobs feature provides a jobs page where users can see any jobs queued or ran. In addition, you may requeue failed or completed jobs again.

**Config**

In the [config file](#job-config) you can configure this feature:

```php
'features' => [
    new Feature\Jobs(
        // A menu name to show the jobs link or null if none.
        menu: 'main',
        menuLabel: 'Jobs',
        // A menu parent name (e.g. 'system') or null if none.
        menuParent: null,
        
        // You may enable recursive app lookup:
        findAppRecursive: false, // default
        
        // you may disable the ACL while testing for instance,
        // otherwise only users with the right permissions can access the page.
        withAcl: false,
    ),
],
```

**Recursive App Lookup**

The `findAppRecursive` option controls how the jobs feature resolves the correct application when requeuing jobs.

`findAppRecursive: false` // default

If set to `false`, the job's app is resolved directly using `AppFinder::findById()`.  
This works for most setups, including typical multi-app projects (e.g., backend + frontend).

If set to `true`, the jobs feature resolves the app using `AppFinder::findByIdRecursive()`, which is only needed if your application structure contains nested or hierarchical apps.

See [App Finder](https://github.com/tobento-ch/apps#app-finder)

> **Note**  
> Recursive lookup is rarely needed. Enable it only if your app structure involves nested apps that require recursive resolution.

**ACL Permissions**

* ```jobs``` User can access jobs
* ```jobs.requeue``` User can requeue jobs.

If using the [App Backend](https://github.com/tobento-ch/app-backend), you can assign the permissions in the roles or users page.

### Monitor Jobs Feature

The monitor jobs feature records jobs using the job repository.

**Config**

In the [config file](#job-config) you can configure this feature:

```php
'features' => [
    Feature\MonitorJobs::class,
],
```

## Console

### Purge Jobs Command

Use the following command to purge monitored jobs:

**Purging jobs older than 24 hours**

```
php ap jobs:purge
```

**Available Options**

| Option | Description |
| --- | --- |
| ```--hours=24``` | The number of hours to retain jobs data. |
| ```--queued=true``` | If defined it purges only jobs which are queued (true) or not (false). |
| ```--status=failed``` | Purges only the jobs with the defined status. |
| ```--appId[]``` | Purges only the jobs which belongs to the defined app IDs. |

If you would like to automate this process, consider installing the [App Schedule](https://github.com/tobento-ch/app-schedule) bundle and using a command task:

```php
use Tobento\Service\Schedule\Task;
use Butschster\CronExpression\Generator;

$schedule->task(
    new Task\CommandTask(
        command: 'jobs:purge',
    )
    // schedule task:
    ->cron(Generator::create()->daily())
);
```

Or you may install the [App Task](https://github.com/tobento-ch/app-task) bundle and use the [Command Task Registry](https://github.com/tobento-ch/app-task#command-task-registry) to register this command.

## Learn More

### Monitor Jobs From Another App

When using different [Apps](https://github.com/tobento-ch/apps), you may monitor jobs from all apps. Make sure that you configure the same job repository connection in the [config file](#job-config).

For instance, you may use the [App Backend](https://github.com/tobento-ch/app-backend) and configure it like:

```php
'features' => [
    Feature\Jobs::class,
    Feature\MonitorJobs::class,
],

'interfaces' => [
    JobRepositoryInterface::class =>
    static function(DatabasesInterface $databases, JobEntityFactory $entityFactory): JobRepositoryInterface {
        return new JobStorageRepository(
            storage: $databases->default('shared:storage')->storage()->new(),
            table: 'jobs',
            entityFactory: $entityFactory,
        );
    },
],
```

Next, if you have created another app:

```php
'features' => [
    // only monitor jobs:
    Feature\MonitorJobs::class,
],

'interfaces' => [
    JobRepositoryInterface::class =>
    static function(DatabasesInterface $databases, JobEntityFactory $entityFactory): JobRepositoryInterface {
        return new JobStorageRepository(
            storage: $databases->default('shared:storage')->storage()->new(),
            table: 'jobs',
            entityFactory: $entityFactory,
        );
    },
],
```

Finally, in the [database config file](https://github.com/tobento-ch/app-database#database-config), in both apps, configure the ```shared:storage``` database:

```php
'defaults' => [
    'pdo' => 'mysql',
    'storage' => 'file',
    'shared:storage' => 'shared:file',
],

'databases' => [
    'shared:file' => [
        'factory' => \Tobento\Service\Database\Storage\StorageDatabaseFactory::class,
        'config' => [
            'storage' => \Tobento\Service\Storage\JsonFileStorage::class,
            'dir' => directory('app:parent').'storage/database/file/',
        ],
    ],
],
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)