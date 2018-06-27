<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\UrlDiscoveryTaskService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class UrlDiscoveryTaskServiceTest extends AbstractBaseTestCase
{
    /**
     * @var UrlDiscoveryTaskService
     */
    private $urlDiscoveryTaskService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->urlDiscoveryTaskService = $this->container->get(UrlDiscoveryTaskService::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param Job $crawlJob
     * @param string $parentUrl
     * @param string $taskUrl
     * @param array $expectedTaskParameters
     */
    public function testCreate(Job $crawlJob, $parentUrl, $taskUrl, $expectedTaskParameters)
    {
        $urlDiscoveryTask = $this->urlDiscoveryTaskService->create($crawlJob, $parentUrl, $taskUrl);

        $this->assertInstanceOf(Task::class, $urlDiscoveryTask);
        $this->assertEquals(TaskTypeService::URL_DISCOVERY_TYPE, $urlDiscoveryTask->getType()->getName());
        $this->assertEquals(Task::STATE_QUEUED, $urlDiscoveryTask->getState()->getName());
        $this->assertEquals($crawlJob, $urlDiscoveryTask->getJob());
        $this->assertEquals($taskUrl, $urlDiscoveryTask->getUrl());
        $this->assertEquals($expectedTaskParameters, $urlDiscoveryTask->getParametersArray());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no parent parameters, non-www parent url' => [
                'crawlJob' => $this->createCrawlJob(),
                'parentUrl' => 'http://example.com/',
                'taskUrl' => 'http://example.com/foo',
                'expectedTaskParameters' => [
                    'scope' => [
                        'http://example.com/',
                        'http://www.example.com/',
                    ],
                ],
            ],
            'no parent parameters, www parent url' => [
                'crawlJob' => $this->createCrawlJob(),
                'parentUrl' => 'http://www.example.com/',
                'taskUrl' => 'http://www.example.com/foo',
                'expectedTaskParameters' => [
                    'scope' => [
                        'http://www.example.com/',
                        'http://example.com/',
                    ],
                ],
            ],
            'with parent parameters, non-www parent url' => [
                'crawlJob' => $this->createCrawlJob([
                    'parameters' => json_encode([
                        'foo' => 'bar',
                    ]),
                ]),
                'parentUrl' => 'http://example.com/',
                'taskUrl' => 'http://example.com/foo',
                'expectedTaskParameters' => [
                    'foo' => 'bar',
                    'scope' => [
                        'http://example.com/',
                        'http://www.example.com/',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $crawlJobValues
     *
     * @return Job
     */
    private function createCrawlJob($crawlJobValues = [])
    {
        $crawlJob = new Job();

        if (isset($crawlJobValues['parameters'])) {
            $crawlJob->setParametersString($crawlJobValues['parameters']);
        }

        return $crawlJob;
    }
}
