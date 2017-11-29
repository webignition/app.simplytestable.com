<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\CrawlJobContainerRepository;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class CrawlJobContainerRepositoryTest extends AbstractBaseTestCase
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

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->crawlJobContainerRepository = $entityManager->getRepository(CrawlJobContainer::class);

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testDoesCrawlTaskParentStateMatchState()
    {
        $websiteService = $this->container->get(WebSiteService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $stateService = $this->container->get(StateService::class);
        $taskTypeService = $this->container->get(TaskTypeService::class);

        $userFactory = new UserFactory($this->container);

        $website = $websiteService->get('http://example.com/');
        $user = $userFactory->create();

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setWebsite($website);
        $parentJob->setState($stateService->get(Job::STATE_FAILED_NO_SITEMAP));

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJob = new Job();
        $crawlJob->setUser($user);
        $crawlJob->setWebsite($website);
        $crawlJob->setState($stateService->get(Job::STATE_IN_PROGRESS));

        $entityManager->persist($crawlJob);
        $entityManager->flush();

        $task = new Task();
        $task->setType($taskTypeService->getUrlDiscoveryTaskType());
        $task->setJob($crawlJob);
        $task->setUrl('http://example.com/');
        $task->setState($stateService->get(TaskService::COMPLETED_STATE));

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
                $stateService->get(Job::STATE_FAILED_NO_SITEMAP)
            )
        );

        $this->assertFalse(
            $this->crawlJobContainerRepository->doesCrawlTaskParentJobStateMatchState(
                $task,
                $stateService->get(Job::STATE_COMPLETED)
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
     * @param bool $keyStatesNumerically
     * @param int[] $expectedCrawlJobContainerIndices
     */
    public function testGetAllForUserByCrawlJobStates(
        $userEmails,
        $userEmail,
        $stateNames,
        $keyStatesNumerically,
        $expectedCrawlJobContainerIndices
    ) {
        $stateService = $this->container->get(StateService::class);
        $websiteService = $this->container->get(WebSiteService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobStateNames = [
            Job::STATE_STARTING,
            Job::STATE_CANCELLED,
            Job::STATE_COMPLETED,
            Job::STATE_IN_PROGRESS,
            Job::STATE_PREPARING,
            Job::STATE_QUEUED,
            Job::STATE_FAILED_NO_SITEMAP,
            Job::STATE_REJECTED,
            Job::STATE_RESOLVING,
            Job::STATE_RESOLVED,
        ];

        /* @var User[] $users */
        $users = [];
        foreach ($userEmails as $email) {
            $users[$email] = $this->userFactory->create([
                UserFactory::KEY_EMAIL => $email,
            ]);
        }

        $jobFailedNoSitemapState = $stateService->get(Job::STATE_FAILED_NO_SITEMAP);

        $crawlJobContainerIds = [];

        foreach ($users as $userIndex => $user) {
            foreach ($jobStateNames as $jobStateName) {
                $url = 'http://' . $jobStateName . '.example.com/';
                $website = $websiteService->get($url);

                $crawlJob = new Job();
                $crawlJob->setState($stateService->get($jobStateName));
                $crawlJob->setUser($user);
                $crawlJob->setWebsite($website);

                $parentJob = new Job();
                $parentJob->setUser($user);
                $parentJob->setWebsite($website);
                $parentJob->setState($jobFailedNoSitemapState);

                $crawlJobContainer = new CrawlJobContainer();
                $crawlJobContainer->setCrawlJob($crawlJob);
                $crawlJobContainer->setParentJob($parentJob);

                $entityManager->persist($parentJob);
                $entityManager->flush();

                $entityManager->persist($crawlJob);
                $entityManager->flush();

                $entityManager->persist($crawlJobContainer);
                $entityManager->flush();

                $crawlJobContainerIds[] = $crawlJobContainer->getId();
            }
        }

        $expectedCrawlJobContainerIds = [];
        foreach ($expectedCrawlJobContainerIndices as $index => $expectedCrawlJobContainerIndex) {
            $expectedCrawlJobContainerIds[] = $crawlJobContainerIds[$expectedCrawlJobContainerIndex];
        }

        $stateCollection = $stateService->getCollection($stateNames);
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
                    Job::STATE_STARTING,
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
                    Job::STATE_STARTING,
                    Job::STATE_COMPLETED,
                    Job::STATE_PREPARING,
                    Job::STATE_FAILED_NO_SITEMAP,
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
                    Job::STATE_STARTING,
                    Job::STATE_COMPLETED,
                    Job::STATE_PREPARING,
                    Job::STATE_FAILED_NO_SITEMAP,
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
                    Job::STATE_STARTING,
                    Job::STATE_COMPLETED,
                    Job::STATE_PREPARING,
                    Job::STATE_FAILED_NO_SITEMAP,
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
