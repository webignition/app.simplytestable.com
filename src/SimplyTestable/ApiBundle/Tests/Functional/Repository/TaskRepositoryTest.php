<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskOutputFactory;
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
    use TaskRepositoryTestDataProviders\GetOutputCollectionByJobAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetIdsByJobAndTaskStatesDataProvider;
    use TaskRepositoryTestDataProviders\GetIdsByJobAndUrlExclusionSetDataProvider;
    use TaskRepositoryTestDataProviders\GetErroredCountByJobDataProvider;

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
     * @param string $taskStateName
     * @param string[] $expectedUrls
     */
    public function testFindUrlsByJobAndState($jobValues, $taskStateName, $expectedUrls)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);

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
     * @param string $taskTypeName
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByTaskTypeAndState(
        $jobValuesCollection,
        $taskTypeName,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);

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
     * @param int $jobIndex
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByJobAndState(
        $jobValuesCollection,
        $jobIndex,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
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
     * @param string $taskStateName
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByState(
        $jobValuesCollection,
        $taskStateName,
        $expectedTaskIndices
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);
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
     * @param array $httpFixturesCollection
     * @param string[] $urlSet
     * @param string $taskTypeName
     * @param string[] $stateNames
     * @param int[] $expectedTaskIndices
     */
    public function testGetCollectionByUrlSetAndTaskTypeAndStates(
        $jobValuesCollection,
        $httpFixturesCollection,
        $urlSet,
        $taskTypeName,
        $stateNames,
        $expectedTaskIndices
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $taskType = $taskTypeService->getByName($taskTypeName);
        $states = $stateService->fetchCollection($stateNames);

        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection, $httpFixturesCollection);

        $tasks = $this->getTasksFromJobCollection($jobs);

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        $retrievedTasks = $this->taskRepository->getCollectionByUrlSetAndTaskTypeAndStates($urlSet, $taskType, $states);

        $taskIds = [];

        foreach ($retrievedTasks as $retrievedTask) {
            $taskIds[] = $retrievedTask->getId();
        }

        $this->assertEquals($expectedTaskIds, $taskIds);
    }


    /**
     * @dataProvider getOutputCollectionByJobAndStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $jobIndex
     * @param string $taskStateName
     * @param string[] $expectedTaskOutputValues
     */
    public function testGetOutputCollectionByJobAndState(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $jobIndex,
        $taskStateName,
        $expectedTaskOutputValues
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $state = $stateService->fetch($taskStateName);

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);

        $taskOutputFactory = new TaskOutputFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $job = $jobs[$jobIndex];

        $retrievedRawTaskOutputCollection = $this->taskRepository->getOutputCollectionByJobAndState($job, $state);

        $this->assertEquals($expectedTaskOutputValues, $retrievedRawTaskOutputCollection);
    }

    public function testGetIdsByJob()
    {
        $job = $this->jobFactory->createResolveAndPrepare();
        $tasks = $job->getTasks();

        $taskIds = [];
        foreach ($tasks as $task) {
            $taskIds[] = $task->getId();
        }

        $this->assertNotEmpty($taskIds);
        $this->assertEquals($taskIds, $this->taskRepository->getIdsByJob($job));
    }

    /**
     * @dataProvider getIdsByJobAndTaskStatesDataProvider
     *
     * @param array $jobValuesCollection
     * @param int $jobIndex
     * @param int $limit
     * @param string[] $taskStateNames
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByJobAndTaskStates(
        $jobValuesCollection,
        $jobIndex,
        $limit,
        $taskStateNames,
        $expectedTaskIndices
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);

        $expectedTaskIds = [];
        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        $job = $jobs[$jobIndex];

        $states = $stateService->fetchCollection($taskStateNames);

        $retrievedTaskIds = $this->taskRepository->getIdsByJobAndTaskStates($job, $states, $limit);

        $this->assertEquals($expectedTaskIds, $retrievedTaskIds);
    }

    /**
     * @dataProvider getIdsByJobAndUrlExclusionSetDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $httpFixturesCollection
     * @param int $jobIndex
     * @param string[] $urlExclusionSet
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByJobAndUrlExclusionSet(
        $jobValuesCollection,
        $httpFixturesCollection,
        $jobIndex,
        $urlExclusionSet,
        $expectedTaskIndices
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection, $httpFixturesCollection);
        $job = $jobs[$jobIndex];

        $tasks = $this->getTasksFromJobCollection($jobs);

        $expectedTaskIds = [];
        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        $retrievedTaskIds = $this->taskRepository->getIdsByJobAndUrlExclusionSet($job, $urlExclusionSet);

        $this->assertEquals($expectedTaskIds, $retrievedTaskIds);
    }

    /**
     * @dataProvider getErroredCountByJobDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $jobIndex
     * @param string[] $stateNamesToExclude
     * @param int $expectedErroredCount
     */
    public function testGetErroredCountByJob(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $jobIndex,
        $stateNamesToExclude,
        $expectedErroredCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $job = $jobs[$jobIndex];
        $tasks = $this->getTasksFromJobCollection($jobs);
        $statesToExclude = $stateService->fetchCollection($stateNamesToExclude);

        $taskOutputFactory = new TaskOutputFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $erroredCount = $this->taskRepository->getErroredCountByJob($job, $statesToExclude);

        $this->assertEquals($expectedErroredCount, $erroredCount);
    }

    /**
     * @param array $jobValuesCollection
     * @param User[] $users
     *
     * @return array
     */
    private function populateJobValuesCollectionUsers($jobValuesCollection, $users)
    {
        if (empty($users)) {
            return $jobValuesCollection;
        }

        foreach ($jobValuesCollection as $jobValuesIndex => $jobValues) {
            if (isset($jobValues[JobFactory::KEY_USER])) {
                $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
                $jobValuesCollection[$jobValuesIndex] = $jobValues;
            }
        }

        return $jobValuesCollection;
    }

    /**
     * @param Job[] $jobs
     *
     * @return Task[]
     */
    private function getTasksFromJobCollection($jobs)
    {
        $tasks = [];

        foreach ($jobs as $job) {
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        return $tasks;
    }
}
