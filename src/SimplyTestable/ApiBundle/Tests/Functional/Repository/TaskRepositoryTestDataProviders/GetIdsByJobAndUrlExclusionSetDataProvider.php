<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;

trait GetIdsByJobAndUrlExclusionSetDataProvider
{
    /**
     * @return array
     */
    public function getIdsByJobAndUrlExclusionSetDataProvider()
    {
        return [
            'job zero, no url exclusion set' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
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
                    ],
                    [
                        'prepare' => [
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
                    ],
                ],
                'jobIndex' => 0,
                'urlExclusionSet' => [],
                'expectedTaskIndices' => [0, 1, 2],
            ],
            'job one, no url exclusion set' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
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
                    ],
                    [
                        'prepare' => [
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
                    ],
                ],
                'jobIndex' => 1,
                'urlExclusionSet' => [],
                'expectedTaskIndices' => [3, 4, 5],
            ],
            'job zero, with url exclusion set' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'httpFixturesCollection' => [
                    [
                        'prepare' => [
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
                    ],
                    [
                        'prepare' => [
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
                    ],
                ],
                'jobIndex' => 0,
                'urlExclusionSet' => [
                    'http://example.com/1',
                    'http://example.com/3',
                ],
                'expectedTaskIndices' => [1],
            ],
        ];
    }
}
