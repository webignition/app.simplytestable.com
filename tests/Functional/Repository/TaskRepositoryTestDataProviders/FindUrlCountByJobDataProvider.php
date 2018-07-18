<?php

namespace App\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\SitemapFixtureFactory;

trait FindUrlCountByJobDataProvider
{
    /**
     * @return array
     */
    public function findUrlCountByJobDataProvider()
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
                'expectedUrlCount' => 3,
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
                'expectedUrlCount' => 5,
            ],
        ];
    }
}
