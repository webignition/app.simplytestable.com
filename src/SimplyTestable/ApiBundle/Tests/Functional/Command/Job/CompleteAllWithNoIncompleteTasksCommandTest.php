<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\CompleteAllWithNoIncompleteTasksCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class CompleteAllWithNoIncompleteTasksCommandTest extends ConsoleCommandTestCase
{
    const RETURN_CODE_DONE = 0;
    const RETURN_CODE_IN_MAINTENANCE_MODE = 1;
    const RETURN_CODE_NO_MATCHING_JOBS = 2;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
    }

    /**
     * @return string
     */
    protected function getCommandName()
    {
        return 'simplytestable:job:complete-all-with-no-incomplete-tasks';
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return array(
            new CompleteAllWithNoIncompleteTasksCommand()
        );
    }

    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(self::RETURN_CODE_IN_MAINTENANCE_MODE);
    }

    public function testWithNoJobs()
    {
        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
    }

    public function testWithOnlyCrawlJobs()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();
        $job->setType($this->getJobTypeService()->getCrawlType());
        $this->setJobTasksCompleted($job);

        $this->getJobService()->persistAndFlush($job);

        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
    }

    public function testWithSingleJobWithIncompleteTasks()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        $job->setState($jobInProgressState);
        $this->getJobService()->persistAndFlush($job);

        $this->assertReturnCode(self::RETURN_CODE_NO_MATCHING_JOBS);
        $this->assertEquals(JobService::IN_PROGRESS_STATE, $job->getState()->getName());
    }

    public function testWithSingleJobWithNoIncompleteTasks()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
        ]);

        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }

        $job->setState($jobInProgressState);
        $this->getJobService()->persistAndFlush($job);

        $this->assertReturnCode(self::RETURN_CODE_DONE);
        $this->assertEquals(JobService::COMPLETED_STATE, $job->getState()->getName());
    }

    public function testWithCollectionOfJobsWithNoIncompleteTasks()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        /* @var Job[] $jobs */
        $jobs = [];

        $jobs[] = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('one.example.com'),
            ],
        ]);

        $jobs[] = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('two.example.com'),
            ],
        ]);

        foreach ($jobs as $job) {
            $this->setJobTasksCompleted($job);
        }

        $this->assertReturnCode(self::RETURN_CODE_DONE);

        foreach ($jobs as $job) {
            $this->assertEquals(JobService::COMPLETED_STATE, $job->getState()->getName());
        }
    }

    public function testWithCollectionOfJobsSomeWithIncompleteTasksAndSomeWithout()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        /* @var Job[] $jobs */
        $jobs = array();

        $jobs[] = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://one.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('one.example.com'),
            ],
        ]);

        $jobs[] = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://two.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('two.example.com'),
            ],
        ]);

        foreach ($jobs[1]->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
        }
        $this->getJobService()->persistAndFlush($jobs[1]);

        $this->assertReturnCode(self::RETURN_CODE_DONE);

        foreach ($jobs as $jobIndex => $job) {
            if ($jobIndex === 0) {
                $this->assertEquals(JobService::QUEUED_STATE, $job->getState()->getName());
            } else {
                $this->assertEquals(JobService::COMPLETED_STATE, $job->getState()->getName());
            }
        }
    }
}
