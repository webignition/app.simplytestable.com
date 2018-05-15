<?php

namespace Tests\ApiBundle\Functional\Services\JobPreparation;

use GuzzleHttp\Psr7\Response;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\SitemapFixtureFactory;

/**
 * @group Services/JobPreparationService
 */
class JobPreparationServiceTest extends AbstractJobPreparationServiceTest
{
    /**
     * @var array
     */
    private $cookie = [
        'domain' => '.example.com',
        'name' => 'cookie-name',
        'value' => 'cookie-value',
    ];

    /**
     * @dataProvider prepareDataProvider
     *
     * @param array $jobValues
     * @param string $user
     * @param array $httpFixtures
     * @param string $expectedJobState
     * @param bool $expectedHasCrawlJobContainer
     * @param array $expectedAmmendments
     * @param array $expectedTasks
     */
    public function testPrepare(
        $jobValues,
        $user,
        $httpFixtures,
        $expectedJobState,
        $expectedHasCrawlJobContainer,
        $expectedAmmendments,
        $expectedTasks
    ) {
        $this->httpClientService->appendFixtures($httpFixtures);

        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValues['user'] = $users[$user];

        $job = $this->jobFactory->create($jobValues);

        $jobResolvedState = $stateService->get(Job::STATE_RESOLVED);
        $job->setState($jobResolvedState);

        $entityManager->persist($job);
        $entityManager->flush();

        $this->jobPreparationService->prepare($job);

        $this->assertEquals($expectedJobState, (string)$job->getState());
        $this->assertEquals($expectedHasCrawlJobContainer, $this->crawlJobContainerService->hasForJob($job));

        if ($expectedHasCrawlJobContainer) {
            $crawlJob = $this->crawlJobContainerService->getForJob($job)->getCrawlJob();
            $this->assertEquals($job->getParametersArray(), $crawlJob->getParametersArray());
        }

        $ammendments = $job->getAmmendments();

        foreach ($ammendments as $ammendmentIndex => $ammendment) {
            /* @var Ammendment $ammendment */
            $expectedAmmendment = $expectedAmmendments[$ammendmentIndex];

            $this->assertEquals($expectedAmmendment['reason'], $ammendment->getReason());
            $this->assertEquals($expectedAmmendment['constraint']['name'], $ammendment->getConstraint()->getName());
        }

        $tasks = $job->getTasks();
        $this->assertCount(count($expectedTasks), $tasks);

        foreach ($job->getTasks() as $taskIndex => $task) {
            /* @var Task $task */
            $expectedTask = $expectedTasks[$taskIndex];

            $this->assertEquals($expectedTask, [
                'url' => $task->getUrl(),
                'parameters' => $task->getParametersArray(),
            ]);
        }
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        $notFoundResponse = new Response(404);

        return [
            'no urls found, public user' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                ],
                'user' => 'public',
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'expectedJobState' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedHasCrawlJobContainer' => false,
                'expectedAmmendments' => [],
                'expectedTasks' => [],
            ],
            'no urls found, private user, creates crawl job' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    JobFactory::KEY_PARAMETERS => [
                        'parent-foo' => 'parent-bar',
                    ],
                ],
                'user' => 'private',
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'expectedJobState' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedHasCrawlJobContainer' => true,
                'expectedAmmendments' => [],
                'expectedTasks' => [],
            ],
            'single url job with css validation options' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    JobFactory::KEY_TEST_TYPES => ['css validation'],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        'css validation' => [
                            'ignore-common-cdns' => '1',
                            'domains-to-ignore' => ['domain-one', 'domain-two'],
                        ],
                    ],
                    JobFactory::KEY_PARAMETERS => [
                        'job-foo' => 'job-bar',
                    ],
                ],
                'user' => 'private',
                'httpFixtures' => [],
                'expectedJobState' => Job::STATE_QUEUED,
                'expectedHasCrawlJobContainer' => false,
                'expectedAmmendments' => [],
                'expectedTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'parameters' => [
                            'job-foo' => 'job-bar',
                            'ignore-common-cdns' => '1',
                            'domains-to-ignore' => [
                                'predefined',
                                'domain-one',
                                'domain-two',
                            ],
                        ],
                    ],
                ],
            ],
            'urls_per_job ammendment' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    JobFactory::KEY_PARAMETERS => [
                        'cookies' => [
                            $this->cookie,
                        ],
                    ],
                ],
                'user' => 'private',
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
                'expectedJobState' => Job::STATE_QUEUED,
                'expectedHasCrawlJobContainer' => false,
                'expectedAmmendments' => [
                    [
                        'reason' => 'plan-url-limit-reached:discovered-url-count-11',
                        'constraint' => [
                            'name' => 'urls_per_job',
                        ],
                    ],
                ],
                'expectedTasks' => [
                    [
                        'url' => 'http://example.com/one',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/three',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/four',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/five',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/six',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/seven',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/eight',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/nine',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                    [
                        'url' => 'http://example.com/ten',
                        'parameters' => [
                            'cookies' => [
                                $this->cookie,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
