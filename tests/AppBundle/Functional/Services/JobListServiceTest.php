<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Entity\Job\Job;
use AppBundle\Model\JobList\Configuration;
use AppBundle\Services\JobListService;
use AppBundle\Services\JobTypeService;
use Tests\AppBundle\Fixtures\Loader\JobLoader;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class JobListServiceTest extends AbstractBaseTestCase
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

        $this->jobListService = self::$container->get(JobListService::class);

        $userFactory = new UserFactory(self::$container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $this->defaultConfigurationValues = [
            Configuration::KEY_USER => $users['public'],
        ];


        $jobLoader = new JobLoader(self::$container);
        $this->jobs = $jobLoader->load('jobs.yml', $users);
    }

    public function testGetWithNoConfiguration()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET);
        $this->expectExceptionCode(JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET);

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
                'type' => $listedJob->getType()->getName(),
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET);
        $this->expectExceptionCode(JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET);

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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(JobListService::EXCEPTION_MESSAGE_CONFIGURATION_NOT_SET);
        $this->expectExceptionCode(JobListService::EXCEPTION_CODE_CONFIGURATION_NOT_SET);

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
