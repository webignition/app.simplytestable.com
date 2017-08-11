<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class CrawlJobContainerRepositoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var CrawlJobContainerRepository
     */
    private $crawlJobContainerRepository;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->crawlJobContainerRepository = $this->getManager()->getRepository(CrawlJobContainer::class);
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testDoesCrawlTaskParentStateMatchState()
    {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $userFactory = new UserFactory($this->container);

        $website = $websiteService->fetch('http://example.com/');
        $user = $userFactory->create();

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setWebsite($website);
        $parentJob->setState($stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE));

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJob = new Job();
        $crawlJob->setUser($user);
        $crawlJob->setWebsite($website);
        $crawlJob->setState($stateService->fetch(JobService::IN_PROGRESS_STATE));

        $entityManager->persist($crawlJob);
        $entityManager->flush();

        $task = new Task();
        $task->setType($taskTypeService->getByName(TaskTypeService::URL_DISCOVERY_TYPE));
        $task->setJob($crawlJob);
        $task->setUrl('http://example.com/');
        $task->setState($stateService->fetch(TaskService::COMPLETED_STATE));

        $entityManager->persist($task);
        $entityManager->flush();

        $crawlJob->addTask($task);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setParentJob($parentJob);

        $entityManager->persist($crawlJobContainer);
        $entityManager->flush();

        $this->assertTrue(
            $this->crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                $task,
                $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE)
            )
        );

        $this->assertFalse(
            $this->crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                $task,
                $stateService->fetch(JobService::COMPLETED_STATE)
            )
        );
    }

    public function testGetForJobHasForJob()
    {
        $jobHasNoCrawlJob = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://foo.example.com/',
        ]);
        $this->assertFalse($this->crawlJobContainerRepository->hasForJob($jobHasNoCrawlJob));

        $user =  $this->userFactory->create();

        $jobHasCrawlJob = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_SITE_ROOT_URL => 'http://bar.example.com/',
            JobFactory::KEY_USER => $user,
        ]);

        $this->assertTrue($this->crawlJobContainerRepository->hasForJob($jobHasCrawlJob));

        $crawlJobContainer = $this->crawlJobContainerRepository->getForJob($jobHasCrawlJob);

        $parentJob = $crawlJobContainer->getParentJob();
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $this->assertEquals($jobHasCrawlJob, $parentJob);

        $this->assertTrue($this->crawlJobContainerRepository->hasForJob($parentJob));
        $this->assertTrue($this->crawlJobContainerRepository->hasForJob($crawlJob));

        $parentJobCrawlJobContainer = $this->crawlJobContainerRepository->getForJob($parentJob);
        $crawlJobCrawlJobContainer = $this->crawlJobContainerRepository->getForJob($crawlJob);

        $this->assertEquals($crawlJobContainer, $parentJobCrawlJobContainer);
        $this->assertEquals($crawlJobContainer, $crawlJobCrawlJobContainer);
    }

    /**
     * @dataProvider getAllForUserByCrawlJobStatesDataProvider
     *
     * @param string[] $userEmails
     * @param string $userEmail
     * @param string[] $stateNames
     * @param int[] $expectedCrawlJobContainerIndices
     */
    public function testGetAllForUserByCrawlJobStates(
        $userEmails,
        $userEmail,
        $stateNames,
        $keyStatesNumerically,
        $expectedCrawlJobContainerIndices
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        $jobStateNames = [
            JobService::STARTING_STATE,
            JobService::CANCELLED_STATE,
            JobService::COMPLETED_STATE,
            JobService::IN_PROGRESS_STATE,
            JobService::PREPARING_STATE,
            JobService::QUEUED_STATE,
            JobService::FAILED_NO_SITEMAP_STATE,
            JobService::REJECTED_STATE,
            JobService::RESOLVING_STATE,
            JobService::RESOLVED_STATE,
        ];

        /* @var User[] $users */
        $users = [];
        foreach ($userEmails as $email) {
            $users[$email] = $this->userFactory->create($email);
        }

        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobFailedNoSitemapState = $stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE);

        $crawlJobContainerIds = [];

        foreach ($users as $userIndex => $user) {
            foreach ($jobStateNames as $jobStateName) {
                $url = 'http://' . $jobStateName . '.example.com/';
                $website = $websiteService->fetch($url);

                $crawlJob = new Job();
                $crawlJob->setState($stateService->fetch($jobStateName));
                $crawlJob->setUser($user);
                $crawlJob->setWebsite($website);

                $parentJob = new Job();
                $parentJob->setUser($user);
                $parentJob->setWebsite($website);
                $parentJob->setState($jobFailedNoSitemapState);

                $crawlJobContainer = new CrawlJobContainer();
                $crawlJobContainer->setCrawlJob($crawlJob);
                $crawlJobContainer->setParentJob($parentJob);

                $jobService->persistAndFlush($crawlJob);
                $jobService->persistAndFlush($parentJob);

                $entityManager->persist($crawlJobContainer);
                $entityManager->flush();

                $crawlJobContainerIds[] = $crawlJobContainer->getId();
            }
        }

        $expectedCrawlJobContainerIds = [];
        foreach ($expectedCrawlJobContainerIndices as $index => $expectedCrawlJobContainerIndex) {
            $expectedCrawlJobContainerIds[] = $crawlJobContainerIds[$expectedCrawlJobContainerIndex];
        }

        $stateCollection = $stateService->fetchCollection($stateNames);
        $states = $keyStatesNumerically
            ? array_values($stateCollection)
            : $stateCollection;

        $crawlJobContainers = $this->crawlJobContainerRepository->getAllForUserByCrawlJobStates(
            $users[$userEmail],
            $states
        );

        $this->assertEquals($expectedCrawlJobContainerIds, $this->getCrawlJobContainerIds($crawlJobContainers));
    }

    /**
     * @return array
     */
    public function getAllForUserByCrawlJobStatesDataProvider()
    {
        return [
            'user0, no states' => [
                'userEmails' => [
                    'user0@example.com',
                    'user1@example.com',
                ],
                'userEmail' => 'user0@example.com',
                'stateNames' => [],
                'keyStatesNumerically' => true,
                'expectedCrawlJobContainerIndices' => [],
            ],
            'user0, single state, state collection keyed numerically' => [
                'userEmails' => [
                    'user0@example.com',
                    'user1@example.com',
                ],
                'userEmail' => 'user0@example.com',
                'stateNames' => [
                    JobService::STARTING_STATE,
                ],
                'keyStatesNumerically' => true,
                'expectedCrawlJobContainerIndices' => [
                    0
                ],
            ],
            'user0, multiple states, state collection keyed numerically' => [
                'userEmails' => [
                    'user0@example.com',
                    'user1@example.com',
                ],
                'userEmail' => 'user0@example.com',
                'stateNames' => [
                    JobService::STARTING_STATE,
                    JobService::COMPLETED_STATE,
                    JobService::PREPARING_STATE,
                    JobService::FAILED_NO_SITEMAP_STATE,
                ],
                'keyStatesNumerically' => true,
                'expectedCrawlJobContainerIndices' => [
                    0,
                    2,
                    4,
                    6,
                ],
            ],
            'user0, multiple states, state collection keyed alphabetically' => [
                'userEmails' => [
                    'user0@example.com',
                    'user1@example.com',
                ],
                'userEmail' => 'user0@example.com',
                'stateNames' => [
                    JobService::STARTING_STATE,
                    JobService::COMPLETED_STATE,
                    JobService::PREPARING_STATE,
                    JobService::FAILED_NO_SITEMAP_STATE,
                ],
                'keyStatesNumerically' => false,
                'expectedCrawlJobContainerIndices' => [
                    0,
                    2,
                    4,
                    6,
                ],
            ],
            'user1, multiple states, state collection keyed alphabetically' => [
                'userEmails' => [
                    'user0@example.com',
                    'user1@example.com',
                ],
                'userEmail' => 'user1@example.com',
                'stateNames' => [
                    JobService::STARTING_STATE,
                    JobService::COMPLETED_STATE,
                    JobService::PREPARING_STATE,
                    JobService::FAILED_NO_SITEMAP_STATE,
                ],
                'keyStatesNumerically' => false,
                'expectedCrawlJobContainerIndices' => [
                    10,
                    12,
                    14,
                    16,
                ],
            ],
        ];
    }

    /**
     * @param CrawlJobContainer[] $crawlJobContainers
     * @return int[]
     */
    private function getCrawlJobContainerIds($crawlJobContainers)
    {
        $crawlJobContainerIds = [];

        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobContainerIds[] = $crawlJobContainer->getId();
        }

        return $crawlJobContainerIds;
    }
}