<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class JobRepositoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var JobRepository
     */
    private $jobRepository;

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

        $this->jobRepository = $this->getManager()->getRepository(Job::class);
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @dataProvider getByStatesAndTaskStatesDataProvider
     *
     * @param string[] $jobStateNames
     * @param string[] $taskStateNames
     * @param int[] $expectedJobIndices
     */
    public function testGetByStatesAndTaskStates($jobStateNames, $taskStateNames, $expectedJobIndices)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $jobs = $this->createJobsForAllJobStatesWithTasksForAllTaskStates();

        $expectedJobIds = [];

        foreach ($jobs as $jobIndex => $job) {
            if (in_array($jobIndex, $expectedJobIndices)) {
                $expectedJobIds[] = $job->getId();
            }
        }

        $jobStates = $stateService->fetchCollection($jobStateNames);
        $taskStates = $stateService->fetchCollection($taskStateNames);

        $retrievedJobs = $this->jobRepository->getByStatesAndTaskStates($jobStates, $taskStates);

        $this->assertEquals($expectedJobIds, $this->getJobIds($retrievedJobs));
    }

    public function getByStatesAndTaskStatesDataProvider()
    {
        return [
            'job-states[none specified], task-states[none specified]' => [
                'jobStateNames' => [],
                'taskStateNames' => [],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[none specified]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [],
                'expectedJobIndices' => [],
            ],
            'job-states[none specified], task-states[in-progress]' => [
                'jobStateNames' => [],
                'taskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[cancelled]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [
                    TaskService::CANCELLED_STATE,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[completed]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                ],
                'expectedJobIndices' => [
                    7
                ],
            ],
            'job-states[in-progress, cancelled], task-states[completed]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                    JobService::CANCELLED_STATE,
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                ],
                'expectedJobIndices' => [
                    1, 7
                ],
            ],
        ];
    }

    /**
     * @return Job[]
     */
    private function createJobsForAllJobStatesWithTasksForAllTaskStates()
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobStateNames = array_merge($jobService->getFinishedStateNames(), $jobService->getIncompleteStateNames());

        $taskStateNames = $taskService->getAvailableStateNames();

        /* @var Job[] $jobs */
        $jobs = [];

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobStateNames as $jobStateNameIndex => $jobStateName) {
            $domain = $jobStateName . '.example.com';

            $job = $this->jobFactory->createResolveAndPrepare(
                [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://' . $domain . '/',
                    JobFactory::KEY_STATE => $jobStateName,
                ],
                [],
                $domain
            );

            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        $taskStateCount = count($taskStateNames);

        foreach ($tasks as $taskIndex => $task) {
            $taskState = $stateService->fetch($taskStateNames[$taskIndex % $taskStateCount]);
            $task->setState($taskState);
            $entityManager->persist($task);
        }

        $entityManager->flush();

        return $jobs;
    }

    /**
     * @param Job[] $jobs
     *
     * @return int[] array
     */
    private function getJobIds($jobs)
    {
        $jobIds = [];

        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
        }

        return $jobIds;
    }
}
