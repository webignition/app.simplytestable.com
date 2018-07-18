<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\SitemapFixtureFactory;

trait GetCollectionByUrlSetAndTaskTypeAndStatesDataProvider
{
    /**
     * @return array
     */
    public function getCollectionByUrlSetAndTaskTypeAndStatesDataProvider()
    {
        return [
            'multiple jobs, single url, html validation task type, queued state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                        ],
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    Task::STATE_QUEUED,
                ],
                'expectedTaskIndices' => [0, 6],
            ],
            'multiple jobs, single url, css validation task type, queued state and cancelled state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'taskStateNames' => [
                    Task::STATE_QUEUED,
                    Task::STATE_CANCELLED,
                ],
                'expectedTaskIndices' => [1, 7],
            ],
            'multiple jobs, two urls, html validation task type, queued state and cancelled state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_QUEUED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                        ],
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                    [
                        'prepare' => [
                            new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                            new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])),
                        ],
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                    'http://example.com/foo%20bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    Task::STATE_QUEUED,
                    Task::STATE_CANCELLED,
                ],
                'expectedTaskIndices' => [0, 2, 6, 8],
            ],
        ];
    }
}
