# Routes

All routes are prefixed with `api/v1/` and require throttling middleware (1000 requests per minute per IP).

## Authentication Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| POST | login | AuthController@login | - |
| POST | forgot-password | AuthController@forgotPassword | - |
| POST | reset-password | AuthController@resetPassword | - |
| POST | auth/token | AuthController@token | oauth.token |
| GET | auth/user | AuthController@user | auth:api |

## Development Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | dev/truncate-data | Controller@truncateData | auth:api |

## Load Testing Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| POST | load-test | CrawlerController@load | - |

## Runner Routes (for automated systems)

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| PUT | runner/agents/{agent} | RunnerAgentController@update | auth:api |
| PUT | runner/jobs/{job} | RunnerJobController@update | auth:api |
| PUT | runner/queues/{queue} | RunnerQueueController@update | auth:api |

## Resource Routes (CRUD operations)

All resource routes require `auth:api` middleware.

| Resource | Controller |
|----------|------------|
| configurations | ConfigurationController |
| agents | AgentController |
| proxies | ProxyController |
| jobs | JobController |
| queues | QueueController |
| crawlers | CrawlerController |
| experiments | ExperimentController |
| behaviors | BehaviorController |
| reports | ReportController |
| terms | TermController |
| outlets | OutletController |
| params | ParamController |

### Standard Resource Methods

Each resource supports these standard methods:

| Method | Route Pattern | Action |
|--------|---------------|--------|
| GET | /{resource} | index |
| POST | /{resource} | store |
| GET | /{resource}/{id} | show |
| PUT/PATCH | /{resource}/{id} | update |
| DELETE | /{resource}/{id} | destroy |

## Queue Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | queues/job/{jobid} | QueueController@job | auth:api |
| GET | queues-processed | QueueController@processed | auth:api |
| GET | queues-unprocessed | QueueController@unprocessed | auth:api |
| GET | queues-reviewed | QueueController@reviewed | auth:api |
| GET | queues-unreviewed | QueueController@unreviewed | auth:api |

## Experiment-specific Queue Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | experiments/{experiment}/queues-unreviewed | QueueController@experimentUnreviewed | auth:api |
| GET | experiments/{experiment}/queues-reviewed | QueueController@experimentReviewed | auth:api |
| GET | experiments/{experiment}/queues-processed | QueueController@experimentProcessed | auth:api |
| GET | experiments/{experiment}/queues-unprocessed | QueueController@experimentUnprocessed | auth:api |

## Experiment Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | experiments/experiments-list | ExperimentController@all | auth:api |
| DELETE | experiments/{experiment}/destroy | ExperimentController@forceDelete | auth:api |
| POST | experiments/upload | ExperimentController@upload | auth:api |
| POST | experiments/{experiment}/upload | ExperimentController@upload | auth:api, formdata |
| GET | experiments/{experiment}/download | ExperimentController@download | auth:api |

## Crawler Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | crawlers-processed | CrawlerController@withProcessedQueues | auth:api |
| GET | crawlers-reviewed | CrawlerController@withReviewedQueues | auth:api |
| GET | crawlers-unprocessed | CrawlerController@withUnprocessedQueues | auth:api |
| GET | crawlers-inactive | CrawlerController@inactive | auth:api |
| DELETE | crawlers/{crawler}/destroy | CrawlerController@forceDelete | auth:api |
| GET | crawlers-disable | CrawlerController@disableProdCrawlers | auth:api |
| GET | crawlers-testing | CrawlerController@testing | auth:api |
| GET | crawlers-protected | CrawlerController@index | auth:api |
| DELETE | undo | CrawlerController@undo | auth:api |
| DELETE | cleanup | CrawlerController@cleanup | auth:api |
| POST | crawlers/{crawler}/upload | CrawlerController@upload | auth:api |

## File Handling Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | file/{report} | ReportController@getFile | auth:api |
| POST | upload | S3Controller@store | auth:api |
| GET | download/{filename} | S3Controller@download | auth:api |
| GET | download/report/{job} | ReportController@download | auth:api |

## Utility Routes

| Method | Route | Controller@method | Middleware |
|--------|-------|-------------------|------------|
| GET | ip/{ip?} | CrawlerController@ip | - |
