<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Functional\Repository\TaskRepositoryTestDataProviders;

class TaskRepositoryTest extends BaseSimplyTestableTestCase
{
    use TaskRepositoryTestDataProviders\FindUrlCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\FindUrlsByJobDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\FindUrlsByJobAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByTaskTypeAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByJobAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetIdsByStateDataProvider;
    use TaskRepositoryTestDataProviders\GetCollectionByUrlSetAndTaskTypeAndStatesDataProvider;

    /**
     * @var TaskRepository
     */
    private $taskRepository;

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

        $this->taskRepository = $this->getManager()->getRepository(Task::class);
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @dataProvider findUrlCountByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param int $expectedUrlCount
     */
    public function testFindUrlCountByJob(
        $jobValues,
        $prepareHttpFixtures,
        $expectedUrlCount
    ) {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedUrlCount,
            $this->taskRepository->findUrlCountByJob($job)
        );
    }

    /**
     * @dataProvider findUrlsByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param array $expectedUrls
     */
    public function testFindUrlsByJob(
        $jobValues,
        $prepareHttpFixtures,
        $expectedUrls
    ) {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedUrls,
            $this->taskRepository->findUrlsByJob($job)
        );
    }

    /**
     * @dataProvider getCountByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param int $expectedTaskCount
     */
    public function testGetCountByJob(
        $jobValues,
        $prepareHttpFixtures,
        $expectedTaskCount
    ) {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedTaskCount,
            $this->taskRepository->getCountByJob($job)
        );
    }

    /**
     * @dataProvider findUrlsByJobAndStateDataProvider
     *
     * @param array $jobValues
     * @param string[] $taskStateNames
     * @param string $taskStateName
     * @param string[] $expectedUrls
     */
    public function testFindUrlsByJobAndState($jobValues, $taskStateNames, $taskStateName, $expectedUrls)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        $tasks = $job->getTasks();

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            /* @var Task $task */
            $task = $tasks->get($taskStateIndex);
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $urls = $this->taskRepository->findUrlsByJobAndState($job, $stateService->fetch($taskStateName));

        $this->assertEquals(
            $expectedUrls,
            $urls
        );
    }

    /**
     * @dataProvider findUrlExistsByJobAndUrlDataProvider
     *
     * @param string $url
     * @param bool $expectedExists
     */
    public function testFindUrlExistsByJobAndUrl($url, $expectedExists)
    {
        $job = $this->jobFactory->createResolveAndPrepare();

        $this->assertEquals(
            $expectedExists,
            $this->taskRepository->findUrlExistsByJobAndUrl($job, $url)
        );
    }

    /**
     * @return array
     */
    public function findUrlExistsByJobAndUrlDataProvider()
    {
        return [
            'exists' => [
                'url' => 'http://example.com/one',
                'expectedExists' => true,
            ],
            'does not exist' => [
                'url' => 'http://example.com/foo',
                'expectedExists' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCountByTaskTypeAndStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param string $taskTypeName
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByTaskTypeAndState(
        $jobValuesCollection,
        $taskStateNames,
        $taskTypeName,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $taskType = $taskTypeService->getByName($taskTypeName);
        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedCount,
            $this->taskRepository->getCountByTaskTypeAndState($taskType, $state)
        );
    }

    /**
     * @dataProvider getCountByJobAndStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param int $jobIndex
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByJobAndState(
        $jobValuesCollection,
        $taskStateNames,
        $jobIndex,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $job = $jobs[$jobIndex];
        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedCount,
            $this->taskRepository->getCountByJobAndState($job, $state)
        );
    }

    /**
     * @dataProvider getIdsByStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param string $taskStateName
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByState(
        $jobValuesCollection,
        $taskStateNames,
        $taskStateName,
        $expectedTaskIndices
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedTaskIds,
            $this->taskRepository->getIdsByState($state)
        );
    }

    /**
     * @dataProvider getCollectionByUrlSetAndTaskTypeAndStatesDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNamesToSet
     * @param array $prepareHttpFixturesCollection
     * @param string[] $urlSet
     * @param string $taskTypeName
     * @param string[] $stateNames
     * @param int[] $expectedTaskIndices
     */
    public function testGetCollectionByUrlSetAndTaskTypeAndStates(
        $jobValuesCollection,
        $taskStateNamesToSet,
        $prepareHttpFixturesCollection,
        $urlSet,
        $taskTypeName,
        $stateNames,
        $expectedTaskIndices
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $taskType = $taskTypeService->getByName($taskTypeName);
        $taskStatesToSet = $stateService->fetchCollection($taskStateNamesToSet);
        $states = $stateService->fetchCollection($stateNames);

        /* @var Job[] $jobs */
        $jobs = [];

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobValuesCollection as $jobIndex => $jobValues) {
            $prepareHttpFixtures = isset($prepareHttpFixturesCollection[$jobIndex])
                ? $prepareHttpFixturesCollection[$jobIndex]
                : null;

            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $this->jobFactory->createResolveAndPrepare($jobValues, [
                'prepare' => $prepareHttpFixtures,
            ]);

            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }

            if (isset($taskStateNamesToSet[$taskIndex])) {
                $task->setState($taskStatesToSet[$taskStateNamesToSet[$taskIndex]]);

                $entityManager->persist($task);
                $entityManager->flush($task);
            }
        }

        $retrievedTasks = $this->taskRepository->getCollectionByUrlSetAndTaskTypeAndStates($urlSet, $taskType, $states);

        $taskIds = [];

        foreach ($retrievedTasks as $retrievedTask) {
            $taskIds[] = $retrievedTask->getId();
        }

        $this->assertEquals($expectedTaskIds, $taskIds);
    }
}
