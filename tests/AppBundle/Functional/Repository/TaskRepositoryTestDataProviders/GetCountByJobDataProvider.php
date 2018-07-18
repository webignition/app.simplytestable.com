<?php

namespace Tests\AppBundle\Functional\Repository\TaskRepositoryTestDataProviders;

use GuzzleHttp\Psr7\Response;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\SitemapFixtureFactory;

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
