<?php

namespace Tests\ApiBundle\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\Job\StartService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobSummaryFactory;
use SimplyTestable\ApiBundle\Services\Request\Factory\Job\StartRequestFactory;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\JobConfigurationFactory as JobConfigurationFactoryService;

class MockFactory
{
    /**
     * @param array $calls
     *
     * @return Mock|UserService
     */
    public static function createUserService($calls = [])
    {
        /* @var UserService|Mock $userService */
        $userService = \Mockery::mock(UserService::class);

        if (isset($calls['exists'])) {
            $callValues = $calls['exists'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('exists')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['findUserByEmail'])) {
            $callValues = $calls['findUserByEmail'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('findUserByEmail')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['getConfirmationToken'])) {
            $callValues = $calls['getConfirmationToken'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('getConfirmationToken')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['isPublicUser'])) {
            $callValues = $calls['isPublicUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('isPublicUser')
                ->with($with)
                ->andReturn($return);
        }

        return $userService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|User
     */
    public static function createUser($calls = [])
    {
        /* @var User|Mock $user */
        $user = \Mockery::mock(User::class);

        if (isset($calls['isEnabled'])) {
            $callValues = $calls['isEnabled'];

            $return = $callValues['return'];

            $user
                ->shouldReceive('isEnabled')
                ->andReturn($return);
        }

        return $user;
    }

    /**
     * @param array $calls
     *
     * @return Mock|InviteService
     */
    public static function createTeamInviteService($calls = [])
    {
        /* @var Mock|InviteService $teamInviteService */
        $teamInviteService = \Mockery::mock(InviteService::class);

        if (isset($calls['hasAnyForUser'])) {
            $callValues = $calls['hasAnyForUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $teamInviteService
                ->shouldReceive('hasAnyForUser')
                ->with($with)
                ->andReturn($return);
        }

        return $teamInviteService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|JobRepository
     */
    public static function createJobRepository($calls = [])
    {
        /* @var Mock|JobRepository $jobRepository */
        $jobRepository = \Mockery::mock(JobRepository::class);

        if (isset($calls['getIsPublicByJobId'])) {
            $callValues = $calls['getIsPublicByJobId'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobRepository
                ->shouldReceive('getIsPublicByJobId')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['find'])) {
            $callValues = $calls['find'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobRepository
                ->shouldReceive('find')
                ->with($with)
                ->andReturn($return);
        }

        return $jobRepository;
    }

    /**
     * @param array $calls
     *
     * @return Mock|TaskRepository
     */
    public static function createTaskRepository($calls = [])
    {
        /* @var Mock|TaskRepository $taskRepository */
        $taskRepository = \Mockery::mock(TaskRepository::class);

        if (isset($calls['findUrlsByJob'])) {
            $callValues = $calls['findUrlsByJob'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $taskRepository
                ->shouldReceive('findUrlsByJob')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['findBy'])) {
            $callValues = $calls['findBy'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $taskRepository
                ->shouldReceive('findBy')
                ->with($with)
                ->andReturn($return);
        }

        return $taskRepository;
    }

    /**
     * @param array $calls
     *
     * @return EntityManagerInterface|Mock
     */
    public static function createEntityManager($calls = [])
    {
        /* @var Mock|EntityManagerInterface $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        if (isset($calls['getRepository'])) {
            $callValues = $calls['getRepository'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $entityManager
                ->shouldReceive('getRepository')
                ->with($with)
                ->andReturn($return);
        }

        return $entityManager;
    }

    /**
     * @param array $calls
     *
     * @return RetrievalService|Mock
     */
    public static function createJobRetrievalService($calls = [])
    {
        /* @var Mock|RetrievalService $jobRetrievalService */
        $jobRetrievalService = \Mockery::mock(RetrievalService::class);

        if (isset($calls['retrieve'])) {
            $callValues = $calls['retrieve'];

            $with = $callValues['with'];

            if (isset($callValues['return'])) {
                $return = $callValues['return'];

                $jobRetrievalService
                    ->shouldReceive('retrieve')
                    ->with($with)
                    ->andReturn($return);
            } elseif (isset($callValues['throw'])) {
                $exception = $callValues['throw'];

                $jobRetrievalService
                    ->shouldReceive('retrieve')
                    ->with($with)
                    ->andThrow($exception);
            }
        }

        return $jobRetrievalService;
    }

    /**
     * @param array $calls
     *
     * @return JobSummaryFactory|Mock
     */
    public static function createJobSummaryFactory($calls = [])
    {
        /* @var Mock|JobSummaryFactory $jobSummaryFactory */
        $jobSummaryFactory = \Mockery::mock(JobSummaryFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobSummaryFactory
                ->shouldReceive('create')
                ->with($with)
                ->andReturn($return);
        }

        return $jobSummaryFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|TaskService
     */
    public static function createTaskService($calls = [])
    {
        /* @var Mock|TaskService $taskService */
        $taskService = \Mockery::mock(TaskService::class);

        if (isset($calls['isFinished'])) {
            $callValues = $calls['create'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $taskService
                ->shouldReceive('create')
                ->with($with)
                ->andReturn($return);
        }

        return $taskService;
    }

    /**
     * @return Mock|Task
     */
    public static function createTask()
    {
        /* @var Mock|Task $task */
        $task = \Mockery::mock(Task::class);

        return $task;
    }

    /**
     * @param bool $isReadOnly
     *
     * @return Mock|ApplicationStateService
     */
    public static function createApplicationStateService($isReadOnly = false)
    {
        /* @var Mock|ApplicationStateService $applicationStateService */
        $applicationStateService = \Mockery::mock(ApplicationStateService::class);

        $applicationStateService
            ->shouldReceive('isInReadOnlyMode')
            ->andReturn($isReadOnly);

        return $applicationStateService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|StartService
     */
    public static function createJobStartService($calls = [])
    {
        /* @var Mock|StartService $jobStartService */
        $jobStartService = \Mockery::mock(StartService::class);

        if (isset($calls['start'])) {
            $callValues = $calls['start'];

            $with = $callValues['with'];

            if (isset($callValues['return'])) {
                $return = $callValues['return'];

                $jobStartService
                    ->shouldReceive('start')
                    ->with($with)
                    ->andReturn($return);
            } elseif (isset($callValues['throw'])) {
                $exception = $callValues['throw'];

                $jobStartService
                    ->shouldReceive('start')
                    ->with($with)
                    ->andThrow($exception);
            }
        }

        return $jobStartService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|StartRequestFactory
     */
    public static function createJobStartRequestFactory($calls = [])
    {
        /* @var Mock|StartRequestFactory $jobStartRequestFactory */
        $jobStartRequestFactory = \Mockery::mock(StartRequestFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $return = $callValues['return'];

            $jobStartRequestFactory
                ->shouldReceive('create')
                ->andReturn($return);
        }

        return $jobStartRequestFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|JobConfigurationFactoryService
     */
    public static function createJobConfigurationFactory($calls = [])
    {
        /* @var Mock|JobConfigurationFactoryService $jobConfigurationFactory */
        $jobConfigurationFactory = \Mockery::mock(JobConfigurationFactoryService::class);

        if (isset($calls['createFromJobStartRequest'])) {
            $callValues = $calls['createFromJobStartRequest'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobConfigurationFactory
                ->shouldReceive('createFromJobStartRequest')
                ->with($with)
                ->andReturn($return);
        }

        return $jobConfigurationFactory;
    }

    /**
     * @param array $calls
     *
     * @return Mock|JobService
     */
    public static function createJobService($calls = [])
    {
        /* @var Mock|JobService $jobService */
        $jobService = \Mockery::mock(JobService::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobService
                ->shouldReceive('create')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['reject'])) {
            $callValues = $calls['reject'];

            $withArgs = $callValues['withArgs'];

            $jobService
                ->shouldReceive('reject')
                ->withArgs($withArgs);
        }

        if (isset($calls['isFinished'])) {
            $callValues = $calls['isFinished'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobService
                ->shouldReceive('isFinished')
                ->with($with)
                ->andReturn($return);
        }

        return $jobService;
    }
}
