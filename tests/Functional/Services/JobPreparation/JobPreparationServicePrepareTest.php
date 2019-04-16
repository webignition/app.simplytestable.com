<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\JobPreparation;

use App\Entity\User;
use App\Tests\Services\JobFactory;
use GuzzleHttp\Psr7\Response;
use App\Entity\Job\Ammendment;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\JobTypeService;
use App\Tests\Factory\HttpFixtureFactory;
use App\Tests\Factory\SitemapFixtureFactory;
use Doctrine\Common\Collections\Collection as DoctrineCollection;

/**
 * @group Services/JobPreparationService
 */
class JobPreparationServiceTest extends AbstractJobPreparationServiceTest
{
    /**
     * @var User[]
     */
    private $users;

    protected function setUp()
    {
        parent::setUp();

        $this->users = $this->userFactory->createPublicAndPrivateUserSet();
    }

    public function testPrepareForPublicUserWithNoUrlsFoundFinishesJob()
    {
        $this->httpClientService->appendFixtures(array_fill(0, 7, new Response(404)));

        $job = $this->createAndResolveJob([
            JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
            JobFactory::KEY_USER => $this->users['public'],
        ]);

        $this->assertNull($job->getTimePeriod());

        $this->jobPreparationService->prepare($job);

        $this->assertEquals(Job::STATE_FAILED_NO_SITEMAP, (string)$job->getState());
        $this->assertFalse($this->crawlJobContainerService->hasForJob($job));

        $timePeriod = $job->getTimePeriod();
        $this->assertNotNull($timePeriod);

        $startDateTime = $timePeriod->getStartDateTime();
        $endDateTime = $timePeriod->getEndDateTime();

        $this->assertNotNull($startDateTime);
        $this->assertNotNull($endDateTime);
        $this->assertEquals($startDateTime, $endDateTime);
    }

    public function testPrepareForPrivateUserWithNoUrlsFoundCreatesCrawlJob()
    {
        $this->httpClientService->appendFixtures(array_fill(0, 7, new Response(404)));

        $job = $this->createAndResolveJob([
            JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
            JobFactory::KEY_PARAMETERS => [
                'parent-foo' => 'parent-bar',
            ],
            JobFactory::KEY_USER => $this->users['private'],
        ]);

        $this->assertNull($job->getTimePeriod());

        $this->jobPreparationService->prepare($job);

        $this->assertEquals(Job::STATE_FAILED_NO_SITEMAP, (string)$job->getState());
        $this->assertNull($job->getTimePeriod());
        $this->assertTrue($this->crawlJobContainerService->hasForJob($job));

        $crawlJob = $this->crawlJobContainerService->getForJob($job)->getCrawlJob();
        $this->assertEquals(
            $job->getParameters()->getAsArray(),
            $crawlJob->getParameters()->getAsArray()
        );
    }

    public function testPrepareSingleUrlJobWithTaskTypeOptions()
    {
        $job = $this->createAndResolveJob([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_TEST_TYPES => ['css validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'css validation' => [
                    'ignore-common-cdns' => '1',
                    'domains-to-ignore' => ['domain-one', 'domain-two'],
                ],
            ],
            JobFactory::KEY_USER => $this->users['private'],
        ]);

        $this->assertNull($job->getTimePeriod());

        $this->jobPreparationService->prepare($job);

        $this->assertEquals(Job::STATE_QUEUED, (string)$job->getState());
        $this->assertFalse($this->crawlJobContainerService->hasForJob($job));

        $timePeriod = $job->getTimePeriod();
        $this->assertNotNull($timePeriod);
        $this->assertNotNull($timePeriod->getStartDateTime());
        $this->assertNull($timePeriod->getEndDateTime());

        /* @var DoctrineCollection $tasks */
        $tasks = $job->getTasks();
        $this->assertCount(1, $tasks);

        /* @var Task $task */
        $task = $tasks->current();

        $this->assertEquals('http://example.com/', $task->getUrl());
        $this->assertEquals(
            [
                'ignore-common-cdns' => '1',
                'domains-to-ignore' => [
                    'predefined',
                    'domain-one',
                    'domain-two',
                ],
            ],
            $task->getParameters()->getAsArray()
        );
    }

    /**
     * @dataProvider prepareFullSiteJobSuccessDataProvider
     */
    public function testPrepareFullSiteJobSuccess(
        array $jobValues,
        array $httpFixtures,
        array $expectedTasks,
        ?array $expectedAmendments = []
    ) {
        $this->httpClientService->appendFixtures($httpFixtures);

        $jobValues['user'] = $this->users['private'];

        $job = $this->jobFactory->create($jobValues);
        $this->jobFactory->resolve($job);

        $this->jobPreparationService->prepare($job);

        $this->assertEquals(Job::STATE_QUEUED, (string)$job->getState());
        $this->assertFalse($this->crawlJobContainerService->hasForJob($job));

// @todo: fix in #597
//        $amendments = $job->getAmmendments();
//
//        $this->assertCount(count($expectedAmendments), $amendments);
//
//        foreach ($amendments as $amendmentIndex => $amendment) {
//            /* @var Ammendment $amendment */
//            $expectedAmendment = $expectedAmendments[$amendmentIndex];
//
//            $this->assertEquals($expectedAmendment['reason'], $amendment->getReason());
//            $this->assertEquals($expectedAmendment['constraint']['name'], $amendment->getConstraint()->getName());
//        }

        /* @var Task[] $tasks */
        $tasks = $job->getTasks();
        $this->assertCount(count($expectedTasks), $tasks);

        foreach ($tasks as $taskIndex => $task) {
            /* @var Task $task */
            $expectedTask = $expectedTasks[$taskIndex];

            $this->assertEquals($expectedTask, [
                'url' => $task->getUrl(),
                'parameters' => $task->getParameters()->getAsArray(),
            ]);
        }
    }

    public function prepareFullSiteJobSuccessDataProvider(): array
    {
        return [
            'tasks retain job parameters' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    JobFactory::KEY_PARAMETERS => [
                        'job-foo' => 'job-bar',
                    ],
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                        'http://example.com/one',
                    ])),
                ],
                'expectedTasks' => [
                    [
                        'url' => 'http://example.com/one',
                        'parameters' => [
                            'job-foo' => 'job-bar',
                        ],
                    ],
                ],
            ],
            'urls_per_job amendment' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    new Response(200, ['content-type' => 'application/xml'], SitemapFixtureFactory::generate([
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                        'http://example.com/four',
                        'http://example.com/five',
                        'http://example.com/six',
                        'http://example.com/seven',
                        'http://example.com/eight',
                        'http://example.com/nine',
                        'http://example.com/ten',
                        'http://example.com/eleven',
                    ])),
                ],
                'expectedTasks' => [
                    [
                        'url' => 'http://example.com/one',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/three',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/four',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/five',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/six',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/seven',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/eight',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/nine',
                        'parameters' => [],
                    ],
                    [
                        'url' => 'http://example.com/ten',
                        'parameters' => [],
                    ],
                ],
                'expectedAmendments' => [
                    [
                        'reason' => 'plan-url-limit-reached:discovered-url-count-11',
                        'constraint' => [
                            'name' => 'urls_per_job',
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createAndResolveJob(array $jobValues): Job
    {
        $job = $this->jobFactory->create($jobValues);
        $this->jobFactory->resolve($job);

        return $job;
    }
}
