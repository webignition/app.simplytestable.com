<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use GuzzleHttp\Psr7\Response;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\SitemapFixtureFactory;

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
                    new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                    new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                        'http://example.com/1',
                        'http://example.com/2',
                        'http://example.com/3',
                    ])),
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
                    new Response(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                    new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                        'http://example.com/1',
                        'http://example.com/2',
                        'http://example.com/3',
                        'http://example.com/4',
                        'http://example.com/5',
                    ])),
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