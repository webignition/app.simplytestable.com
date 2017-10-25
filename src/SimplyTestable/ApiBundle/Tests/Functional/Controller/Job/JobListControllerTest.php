<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\JobListController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Fixtures\Loader\JobLoader;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JobListControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var Job[]
     */
    private $jobs;

    /**
     * @var User[]
     */
    private $users;

    /**
     * @var JobListController
     */
    private $jobListController;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();

        $jobLoader = new JobLoader($this->container);
        $this->jobs = $jobLoader->load('jobs.yml', $this->users);

        $this->jobListController = new JobListController();
        $this->jobListController->setContainer($this->container);

        $this->requestStack = $this->container->get('request_stack');
    }

    public function testCountActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate('job_joblist_count');

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider countActionDataProvider
     *
     * @param string $user
     * @param int $expectedResponseData
     */
    public function testCountAction($user, $expectedResponseData)
    {
        $this->setUser($this->users[$user]);

        $this->requestStack->push(new Request());

        $response = $this->jobListController->countAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent());

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function countActionDataProvider()
    {
        return [
            'public' => [
                'user' => 'public',
                'expectedResponseData' => 5,
            ],
            'private' => [
                'user' => 'private',
                'expectedResponseData' => 6,
            ],
        ];
    }

    public function testListActionGetRequest()
    {
        $requestUrl = $this->container->get('router')->generate(
            'job_joblist_list',
            [
                'limit' => 0,
                'offset' => 0,
            ]
        );

        $this->getCrawler([
            'url' => $requestUrl,
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider listActionDataProvider
     *
     * @param string $user
     * @param int $limit
     * @param int $offset
     * @param array $query
     * @param int $expectedResponseMaxResults
     * @param int $expectedResponseLimit
     * @param int $expectedResponseOffset
     * @param array $expectedListedJobs
     */
    public function testListActionSuccess(
        $user,
        $limit,
        $offset,
        $query,
        $expectedResponseMaxResults,
        $expectedResponseLimit,
        $expectedResponseOffset,
        $expectedListedJobs
    ) {
        $jobIdIndex = [];

        foreach ($this->jobs as $job) {
            $jobIdIndex[] = $job->getId();
        }

        $this->setUser($this->users[$user]);

        $request = new Request($query);

        $this->requestStack->push($request);

        $response = $this->jobListController->listAction($limit, $offset);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals($expectedResponseMaxResults, $responseData['max_results']);
        $this->assertEquals($expectedResponseLimit, $responseData['limit']);
        $this->assertEquals($expectedResponseOffset, $responseData['offset']);

        $listedJobs = $responseData['jobs'];

        foreach ($listedJobs as $listedJobIndex => $listedJob) {
            $listedJobData = [
                'id' => $listedJob['id'],
                'url' => $listedJob['website'],
                'type' => $listedJob['type'],
                'state' => $listedJob['state'],

            ];

            $expectedListedJob = $expectedListedJobs[$listedJobIndex];
            $expectedListedJob['id'] = $jobIdIndex[$expectedListedJob['id']];

            $this->assertEquals($expectedListedJob, $listedJobData);
        }
    }

    /**
     * @return array
     */
    public function listActionDataProvider()
    {
        return [
            'public, limit 1, offset 0' => [
                'user' => 'public',
                'limit' => 1,
                'offset' => 0,
                'query' => [],
                'expectedResponseMaxResults' => 5,
                'expectedResponseLimit' => 1,
                'expectedResponseOffset' => 0,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'new',
                    ],
                ],
            ],
            'public, limit 10, offset 0' => [
                'user' => 'public',
                'limit' => 10,
                'offset' => 0,
                'query' => [],
                'expectedResponseMaxResults' => 5,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 0,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 4,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 3,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 2,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'completed',
                    ],
                ],
            ],
            'public, limit 10, offset 0, exclude current' => [
                'user' => 'public',
                'limit' => 10,
                'offset' => 0,
                'query' => [
                    'exclude-current' => '1',
                ],
                'expectedResponseMaxResults' => 1,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 0,
                'expectedListedJobs' => [
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'completed',
                    ],
                ],
            ],
            'public, limit 10, offset 0, exclude finished' => [
                'user' => 'public',
                'limit' => 10,
                'offset' => 0,
                'query' => [
                    'exclude-finished' => '1',
                ],
                'expectedResponseMaxResults' => 4,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 0,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 4,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 3,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 2,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                ],
            ],
            'public, limit 10, offset 0, exclude states [new]' => [
                'user' => 'public',
                'limit' => 10,
                'offset' => 0,
                'query' => [
                    'exclude-states' => [
                        'new',
                    ],
                ],
                'expectedResponseMaxResults' => 1,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 0,
                'expectedListedJobs' => [
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'completed',
                    ],
                ],
            ],
            'public, limit 10, offset 3' => [
                'user' => 'public',
                'limit' => 10,
                'offset' => 3,
                'query' => [],
                'expectedResponseMaxResults' => 5,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 3,
                'expectedListedJobs' => [
                    [
                        'id' => 2,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'completed',
                    ],
                ],
            ],
            'private, limit 10, offset 3' => [
                'user' => 'private',
                'limit' => 10,
                'offset' => 3,
                'query' => [],
                'expectedResponseMaxResults' => 6,
                'expectedResponseLimit' => 10,
                'expectedResponseOffset' => 3,
                'expectedListedJobs' => [
                    [
                        'id' => 7,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 6,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'new',
                    ],
                    [
                        'id' => 1,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'completed',
                    ],
                ],
            ],
        ];
    }

    public function testWebsitesActionGetRequest()
    {
        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_joblist_websites'),
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider websitesActionDataProvider
     *
     * @param string $user
     * @param int $expectedResponseData
     */
    public function testWebsitesActionFoo($user, $expectedResponseData)
    {
        $this->requestStack->push(new Request());

        $this->setUser($this->users[$user]);

        $response = $this->jobListController->websitesAction();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent());

        $this->assertEquals($expectedResponseData, $responseData);
    }

    /**
     * @return array
     */
    public function websitesActionDataProvider()
    {
        return [
            'public' => [
                'user' => 'public',
                'expectedResponseData' => [
                    'http://0.example.com/',
                    'http://1.example.com/',
                ],
            ],
            'private' => [
                'user' => 'private',
                'expectedResponseData' => [
                    'http://0.example.com/',
                    'http://1.example.com/',
                    'http://2.example.com/',
                ],
            ],
        ];
    }
}
