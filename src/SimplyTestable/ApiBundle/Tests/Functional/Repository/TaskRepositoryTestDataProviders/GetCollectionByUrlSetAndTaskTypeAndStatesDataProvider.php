<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;

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
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
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
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
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
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                    'http://example.com/foo%20bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedTaskIndices' => [0, 2, 6, 8],
            ],
        ];
    }
}
