<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Functional\Repository\TaskRepositoryTestDataProviders;

class TaskRepositoryTest extends AbstractBaseTestCase
{
    use TaskRepositoryTestDataProviders\FindUrlCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\FindUrlsByJobDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\FindUrlsByJobAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByTaskTypeAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetIdsByStateDataProvider;
    use TaskRepositoryTestDataProviders\GetCollectionByUrlSetAndTaskTypeAndStatesDataProvider;
    use TaskRepositoryTestDataProviders\GetOutputCollectionByJobAndStateDataProvider;
    use TaskRepositoryTestDataProviders\GetIdsByJobAndStatesDataProvider;
    use TaskRepositoryTestDataProviders\GetCountWithIssuesByJobDataProvider;
    use TaskRepositoryTestDataProviders\GetErrorCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\GetWarningCountByJobDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByJobAndStatesDataProvider;
    use TaskRepositoryTestDataProviders\GetTaskOutputByTypeDataProvider;
    use TaskRepositoryTestDataProviders\GetThroughputSinceDataProvider;
    use TaskRepositoryTestDataProviders\FindOutputByJobAndTypeDataProvider;
    use TaskRepositoryTestDataProviders\GetCountByUsersAndStateForPeriodDataProvider;

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

        $this->taskRepository = $this->container->get('simplytestable.repository.task');
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
     * @dataProvider getIdsByJobAndStatesDataProvider
     *
     * @param array $jobValuesCollection
     * @param int $jobIndex
     * @param int $limit
     * @param string[] $taskStateNames
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByJobAndStates(
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

        $retrievedTaskIds = $this->taskRepository->getIdsByJobAndStates($job, $states, $limit);

        $this->assertEquals($expectedTaskIds, $retrievedTaskIds);
    }

    /**
     * @dataProvider getCountWithIssuesByJobDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $jobIndex
     * @param string $issueType
     * @param string[] $stateNamesToExclude
     * @param int $expectedCount
     */
    public function testGetCountWithIssuesByJob(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $jobIndex,
        $issueType,
        $stateNamesToExclude,
        $expectedCount
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

        $count = $this->taskRepository->getCountWithIssuesByJob(
            $job,
            $issueType,
            $statesToExclude
        );

        $this->assertEquals($expectedCount, $count);
    }

    /**
     * @dataProvider getErrorCountByJobDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $jobIndex
     * @param int $expectedErrorCount
     */
    public function testGetErrorCountByJob(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $jobIndex,
        $expectedErrorCount
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $job = $jobs[$jobIndex];
        $tasks = $this->getTasksFromJobCollection($jobs);

        $taskOutputFactory = new TaskOutputFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $errorCount = $this->taskRepository->getErrorCountByJob($job);

        $this->assertEquals($expectedErrorCount, $errorCount);
    }

    /**
     * @dataProvider getWarningCountByJobDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param int $jobIndex
     * @param int $expectedWarningCount
     */
    public function testGetWarningCountByJob(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $jobIndex,
        $expectedWarningCount
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $job = $jobs[$jobIndex];
        $tasks = $this->getTasksFromJobCollection($jobs);

        $taskOutputFactory = new TaskOutputFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $warningCount = $this->taskRepository->getWarningCountByJob($job);

        $this->assertEquals($expectedWarningCount, $warningCount);
    }

    /**
     * @dataProvider getCountByJobAndStatesDataProvider
     *
     * @param array $jobValuesCollection
     * @param int $jobIndex
     * @param string[] $stateNames
     * @param int $expectedTaskCount
     */
    public function testGetCountByJobAndStates($jobValuesCollection, $jobIndex, $stateNames, $expectedTaskCount)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $states = $stateService->fetchCollection($stateNames);

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $job = $jobs[$jobIndex];

        $taskCount = $this->taskRepository->getCountByJobAndStates($job, $states);

        $this->assertEquals($expectedTaskCount, $taskCount);
    }

    /**
     * @dataProvider getTaskOutputByTypeDataProvider
     *
     * @param array $jobValuesCollection
     * @param array $taskOutputValuesCollection
     * @param string $taskTypeName
     * @param int[] $expectedOutputIndices
     */
    public function testGetTaskOutputByType(
        $jobValuesCollection,
        $taskOutputValuesCollection,
        $taskTypeName,
        $expectedOutputIndices
    ) {
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $taskOutputRepository = $this->container->get('simplytestable.repository.taskoutput');

        $taskType = $taskTypeService->getByName($taskTypeName);

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

        /* @var Output[] $taskOutputs */
        $taskOutputs = $taskOutputRepository->findAll();

        $expectedOutputIds = [];

        foreach ($taskOutputs as $taskOutputIndex => $taskOutput) {
            if (in_array($taskOutputIndex, $expectedOutputIndices)) {
                $expectedOutputIds[] = $taskOutput->getId();
            }
        }

        $retrievedOutputIds = $this->taskRepository->getTaskOutputByType($taskType);

        $this->assertEquals($expectedOutputIds, $retrievedOutputIds);
    }

    /**
     * @dataProvider getThroughputSinceDataProvider
     *
     * @param array $jobValuesCollection
     * @param \DateTime[] $taskEndDateTimeCollection
     * @param \DateTime $sinceDateTime
     * @param int $expectedThroughput
     */
    public function testGetThroughputSince(
        $jobValuesCollection,
        $taskEndDateTimeCollection,
        $sinceDateTime,
        $expectedThroughput
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);

        $taskFactory = new TaskFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskEndDateTimeCollection[$taskIndex])) {
                $taskFactory->setEndDateTime($task, $taskEndDateTimeCollection[$taskIndex]);
            }
        }

        $throughput = $this->taskRepository->getThroughputSince($sinceDateTime);

        $this->assertEquals($expectedThroughput, $throughput);
    }

    /**
     * @dataProvider findOutputByJobAndTypeDataProvider
     *
     * @param array $jobValuesCollection
     * @param \DateTime[] $taskEndDateTimeCollection
     * @param array $taskOutputValuesCollection
     * @param int $taskIndex
     * @param bool $limit
     * @param string $expectedRawTaskOutputs
     */
    public function testFindOutputByJobAndType(
        $jobValuesCollection,
        $taskEndDateTimeCollection,
        $taskOutputValuesCollection,
        $taskIndex,
        $limit,
        $expectedRawTaskOutputs
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $users);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);
        $selectedTask = $tasks[$taskIndex];

        $taskFactory = new TaskFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskEndDateTimeCollection[$taskIndex])) {
                $taskFactory->setEndDateTime($task, $taskEndDateTimeCollection[$taskIndex]);
            }
        }

        $taskOutputFactory = new TaskOutputFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskOutputValuesCollection[$taskIndex])) {
                $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

                $taskOutputFactory->create($task, $taskOutputValues);
            }
        }

        $retrievedRawTaskOutputs = $this->taskRepository->findOutputByJobAndType($selectedTask, $limit);

        $this->assertEquals($expectedRawTaskOutputs, $retrievedRawTaskOutputs);
    }

    /**
     * @dataProvider getCountByUsersAndStatesForPeriodDataProvider
     *
     * @param array $jobValuesCollection
     * @param \DateTime[] $taskEndDateTimeCollection
     * @param string[] $userNames
     * @param string[] $stateNames
     * @param string $periodStart
     * @param string $periodEnd
     * @param int $expectedCount
     */
    public function testGetCountByUsersAndStatesForPeriod(
        $jobValuesCollection,
        $taskEndDateTimeCollection,
        $userNames,
        $stateNames,
        $periodStart,
        $periodEnd,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $allUsers = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $jobValuesCollection = $this->populateJobValuesCollectionUsers($jobValuesCollection, $allUsers);

        $jobs = $this->jobFactory->createResolveAndPrepareCollection($jobValuesCollection);
        $tasks = $this->getTasksFromJobCollection($jobs);

        $taskFactory = new TaskFactory($this->container);

        foreach ($tasks as $taskIndex => $task) {
            if (isset($taskEndDateTimeCollection[$taskIndex])) {
                $taskFactory->setEndDateTime($task, $taskEndDateTimeCollection[$taskIndex]);
            }
        }

        $users = [];
        foreach ($allUsers as $userIdentifier => $user) {
            if (in_array($userIdentifier, $userNames)) {
                $users[] = $user;
            }
        }

        $states = $stateService->fetchCollection($stateNames);

        $count = $this->taskRepository->getCountByUsersAndStatesForPeriod($users, $states, $periodStart, $periodEnd);

        $this->assertEquals($expectedCount, $count);
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