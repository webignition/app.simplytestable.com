<?php

namespace Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\SitemapFixtureFactory;

trait FindUrlsByJobDataProvider
{
    /**
     * @return array
     */
    public function findUrlsByJobDataProvider()
    {
        return [
            'three' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'prepareHttpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                        ])
                    ),
                ],
                'expectedUrls' => [
                    [
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'url' => 'http://example.com/2',
                    ],
                    [
                        'url' => 'http://example.com/3',
                    ],
                ],
            ],
            'five' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'prepareHttpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                        ])
                    ),
                ],
                'expectedUrls' => [
                    [
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'url' => 'http://example.com/2',
                    ],
                    [
                        'url' => 'http://example.com/3',
                    ],
                    [
                        'url' => 'http://example.com/4',
                    ],
                    [
                        'url' => 'http://example.com/5',
                    ],
                ],
            ],
        ];
    }
}
