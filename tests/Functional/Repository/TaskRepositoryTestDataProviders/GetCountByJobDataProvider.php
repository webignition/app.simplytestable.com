<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use App\Tests\Services\JobFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypeService;
use App\Tests\Factory\SitemapFixtureFactory;

trait GetCountByJobDataProvider
{
    /**
     * @return array
     */
    public function getCountByJobDataProvider()
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
                'expectedTaskCount' => 3,
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
                'expectedTaskCount' => 5,
            ],
        ];
    }
}
