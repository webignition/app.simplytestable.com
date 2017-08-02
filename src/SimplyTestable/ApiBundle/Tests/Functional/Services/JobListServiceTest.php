<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Model\JobList\Configuration;
use SimplyTestable\ApiBundle\Services\JobListService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Fixtures\Loader\JobLoader;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class JobListServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobListService
     */
    private $jobListService;

    /**
     * @var Job[]
     */
    private $jobs;

    /**
     * @var array
     */
    private $defaultConfigurationValues;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobListService = $this->container->get('simplytestable.services.joblistservice');

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->defaultConfigurationValues = [
            Configuration::KEY_USER => $users['public'],
        ];


        $jobLoader = new JobLoader($this->container);
        $this->jobs = $jobLoader->load('jobs.yml', $users);
    }

    public function testGetWithNoConfiguration()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
            JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET
        );

        $this->jobListService->get();
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param int $limit
     * @param int $offset
     * @param array $expectedListedJobs
     */
    public function testGet($limit, $offset, $expectedListedJobs)
    {
        $jobIdIndex = [];

        foreach ($this->jobs as $job) {
            $jobIdIndex[] = $job->getId();
        }

        $configurationValues = $this->defaultConfigurationValues;

        if (!is_null($limit)) {
            $configurationValues[Configuration::KEY_LIMIT] = $limit;
        }

        if (!is_null($offset)) {
            $configurationValues[Configuration::KEY_OFFSET] = $offset;
        }

        $configuration = new Configuration($configurationValues);
        $this->jobListService->setConfiguration($configuration);

        $listedJobs = $this->jobListService->get();

        $this->assertCount(count($expectedListedJobs), $listedJobs);

        foreach ($listedJobs as $listedJobIndex => $listedJob) {
            $listedJobData = [
                'id' => $listedJob->getId(),
                'url' => $listedJob->getWebsite()->getCanonicalUrl(),
                'type' => strtolower($listedJob->getType()->getName()),
                'state' => $listedJob->getState()->getName(),

            ];

            $expectedListedJob = $expectedListedJobs[$listedJobIndex];
            $expectedListedJob['id'] = $jobIdIndex[$expectedListedJob['id']];

            $this->assertEquals($expectedListedJob, $listedJobData);
        }
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'default limit (1), no offset' => [
                'limit' => null,
                'offset' => null,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'job-new',
                    ],
                ],
            ],
            'limit exceeds user job count, no offset' => [
                'limit' => 6,
                'offset' => null,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 4,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 3,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 2,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-completed',
                    ],
                ],
            ],
            'limit less than user job count, no offset' => [
                'limit' => 3,
                'offset' => null,
                'expectedListedJobs' => [
                    [
                        'id' => 5,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 4,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::SINGLE_URL_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 3,
                        'url' => 'http://1.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-new',
                    ],
                ],
            ],
            'limit exceeds user job count, has offset' => [
                'limit' => 6,
                'offset' => 3,
                'expectedListedJobs' => [
                    [
                        'id' => 2,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-new',
                    ],
                    [
                        'id' => 0,
                        'url' => 'http://0.example.com/',
                        'type' => JobTypeService::FULL_SITE_NAME,
                        'state' => 'job-completed',
                    ],
                ],
            ],
        ];
    }

    public function testGetMaxResultsWithNoConfiguration()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
            JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET
        );

        $this->jobListService->getMaxResults();
    }

    public function testGetMaxResults()
    {
        $configuration = new Configuration($this->defaultConfigurationValues);
        $this->jobListService->setConfiguration($configuration);

        $this->assertEquals(
            5,
            $this->jobListService->getMaxResults()
        );
    }

    public function testGetWebsiteUrlsWithNoConfiguration()
    {
        $this->setExpectedException(
            \RuntimeException::class,
            JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET,
            JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET
        );

        $this->jobListService->getWebsiteUrls();
    }

    public function testGetWebsiteUrls()
    {
        $configuration = new Configuration($this->defaultConfigurationValues);
        $this->jobListService->setConfiguration($configuration);

        $this->assertEquals(
            [
                'http://0.example.com/',
                'http://1.example.com/',
            ],
            $this->jobListService->getWebsiteUrls()
        );
    }
}
