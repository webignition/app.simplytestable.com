<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Model\Job\Summary;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Model\Job\Summary\CrawlSummary;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\ModelFactory;

class CrawlSummaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param Job $crawlJob
     * @param int $processedUrlCount
     * @param int $discoveredUrlCount
     * @param int $limit
     * @param array $expectedSerializedData
     */
    public function testJsonSerialize(
        Job $crawlJob,
        $processedUrlCount,
        $discoveredUrlCount,
        $limit,
        $expectedSerializedData
    ) {
        $crawlSummary = new CrawlSummary(
            $crawlJob,
            $processedUrlCount,
            $discoveredUrlCount,
            $limit
        );

        $this->assertEquals($expectedSerializedData, $crawlSummary->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'no processed urls, no discovered url, no limit' => [
                'crawlJob' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-in-progress'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::CRAWL_NAME,
                    ]),
                ]),
                'processedUrlCount' => 0,
                'discoveredUrlCount' => 0,
                'limit' => null,
                'expectedReturnValue' => [
                    'id' => 1,
                    'state' => 'in-progress',
                    'processed_url_count' => 0,
                    'discovered_url_count' => 0,
                ],
            ],
            'has processed urls, has discovered url, has limit' => [
                'crawlJob' => ModelFactory::createJob([
                    ModelFactory::JOB_ID => 1,
                    ModelFactory::JOB_USER => ModelFactory::createUser([
                        ModelFactory::USER_EMAIL => 'user@example.com',
                    ]),
                    ModelFactory::JOB_WEBSITE => ModelFactory::createWebsite([
                        ModelFactory::WEBSITE_CANONICAL_URL => 'http://example.com/',
                    ]),
                    ModelFactory::JOB_STATE => ModelFactory::createState('job-in-progress'),
                    ModelFactory::JOB_URL_COUNT => 12,
                    ModelFactory::JOB_REQUESTED_TASK_TYPES => [],
                    ModelFactory::JOB_TASK_TYPE_OPTIONS_COLLECTION => [],
                    ModelFactory::JOB_TYPE => ModelFactory::createJobType([
                        ModelFactory::JOB_TYPE_NAME => JobTypeService::CRAWL_NAME,
                    ]),
                ]),
                'processedUrlCount' => 10,
                'discoveredUrlCount' => 20,
                'limit' => 30,
                'expectedReturnValue' => [
                    'id' => 1,
                    'state' => 'in-progress',
                    'processed_url_count' => 10,
                    'discovered_url_count' => 20,
                    'limit' => 30,
                ],
            ],
        ];
    }
}
