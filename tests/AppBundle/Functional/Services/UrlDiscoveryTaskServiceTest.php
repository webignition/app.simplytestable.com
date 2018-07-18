<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Task\Task;
use AppBundle\Entity\WebSite;
use AppBundle\Services\TaskTypeService;
use AppBundle\Services\UrlDiscoveryTaskService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

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

        $this->urlDiscoveryTaskService = self::$container->get(UrlDiscoveryTaskService::class);
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
        $this->assertEquals($expectedTaskParameters, $urlDiscoveryTask->getParameters()->getAsArray());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no parent parameters, non-www parent url' => [
                'crawlJob' => $this->createCrawlJob([
                    'canonical_url' => 'http://example.com/',
                ]),
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
                'crawlJob' => $this->createCrawlJob([
                    'canonical_url' => 'http://example.com/',
                ]),
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
                    'canonical_url' => 'http://example.com/',
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

        if (isset($crawlJobValues['canonical_url'])) {
            $website = new WebSite();
            $website->setCanonicalUrl($crawlJobValues['canonical_url']);
            $crawlJob->setWebsite($website);
        }

        return $crawlJob;
    }
}