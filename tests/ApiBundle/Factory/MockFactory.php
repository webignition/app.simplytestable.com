<?php

namespace Tests\ApiBundle\Factory;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\CanonicalizerInterface;
use FOS\UserBundle\Util\UserManipulator;
use Mockery\Mock;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Repository\ScheduledJobRepository;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\CrawlJobUrlCollector;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService;
use SimplyTestable\ApiBundle\Services\Job\RetrievalService;
use SimplyTestable\ApiBundle\Services\Job\StartService;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobSummaryFactory;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Job\StartRequestFactory;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\StripeWebHookMailNotificationSender;
use SimplyTestable\ApiBundle\Services\Task\QueueService as TaskQueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeDomainsToIgnoreService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\Team\InviteService as TeamInviteService;
use SimplyTestable\ApiBundle\Services\Team\MemberService as TeamMemberService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\UrlFinder;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\JobConfigurationFactory as JobConfigurationFactoryService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\ScheduledJob\CronModifier\ValidationService as CronModifierValidationService;
use SimplyTestable\ApiBundle\Services\TaskOutputJoiner\Factory as TaskOutputJoinerFactory;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\Factory as TaskPostProcessorFactory;
use SimplyTestable\ApiBundle\Services\TaskPreProcessor\Factory as TaskPreProcessorFactory;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use SimplyTestable\ApiBundle\Services\WorkerTaskCancellationService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

        if (isset($calls['findUserByConfirmationToken'])) {
            $callValues = $calls['findUserByConfirmationToken'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('findUserByConfirmationToken')
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

        if (isset($calls['isSpecialUser'])) {
            $callValues = $calls['isSpecialUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userService
                ->shouldReceive('isSpecialUser')
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
     * @return Mock|TeamService
     */
    public static function createTeamService($calls = [])
    {
        /* @var Mock|TeamService $teamService */
        $teamService = \Mockery::mock(TeamService::class);

        if (isset($calls['getForUser'])) {
            $callValues = $calls['getForUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $teamService
                ->shouldReceive('getForUser')
                ->with($with)
                ->andReturn($return);
        }

        return $teamService;
    }

    /**
     * @return Mock|TeamMemberService
     */
    public static function createTeamMemberService()
    {
        /* @var Mock|TeamMemberService $teamMemberService */
        $teamMemberService = \Mockery::mock(TeamMemberService::class);

        return $teamMemberService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|TeamInviteService
     */
    public static function createTeamInviteService($calls = [])
    {
        /* @var Mock|TeamInviteService $teamInviteService */
        $teamInviteService = \Mockery::mock(TeamInviteService::class);

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

    /**
     * @return Mock|StripeEventService
     */
    public static function createStripeEventService()
    {
        /* @var Mock|StripeEventService $stripeEventService */
        $stripeEventService = \Mockery::mock(StripeEventService::class);

        return $stripeEventService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ResqueQueueService
     */
    public static function createResqueQueueService($calls = [])
    {
        /* @var Mock|ResqueQueueService $resqueQueueService */
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

        if (isset($calls['enqueue'])) {
            $callValues = $calls['enqueue'];

            $with = $callValues['with'];

            $resqueQueueService
                ->shouldReceive('enqueue')
                ->with($with);
        }

        if (isset($calls['contains'])) {
            $callValues = $calls['contains'];

            $withArgs = $callValues['withArgs'];
            $return = $callValues['return'];

            $resqueQueueService
                ->shouldReceive('contains')
                ->withArgs($withArgs)
                ->andReturn($return);
        }

        return $resqueQueueService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ResqueJobFactory
     */
    public static function createResqueJobFactory($calls = [])
    {
        /* @var Mock|ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = \Mockery::mock(ResqueJobFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $withArgs = $callValues['withArgs'];
            $return = $callValues['return'];

            $resqueJobFactory
                ->shouldReceive('create')
                ->withArgs($withArgs)
                ->andReturn($return);
        }

        return $resqueJobFactory;
    }

    /**
     * @return Mock|StripeWebHookMailNotificationSender
     */
    public static function createStripeWebHookMailNotificationSender()
    {
        /* @var Mock|StripeWebHookMailNotificationSender $sender */
        $sender = \Mockery::mock(StripeWebHookMailNotificationSender::class);

        return $sender;
    }

    /**
     * @return Mock|StateService
     */
    public static function createStateService()
    {
        /* @var Mock|StateService $stateService */
        $stateService = \Mockery::mock(StateService::class);

        return $stateService;
    }

    /**
     * @return Mock|TaskQueueService
     */
    public static function createTaskQueueService()
    {
        /* @var Mock|TaskQueueService $taskQueueService */
        $taskQueueService = \Mockery::mock(TaskQueueService::class);

        return $taskQueueService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ConfigurationService
     */
    public static function createJobConfigurationService($calls = [])
    {
        /* @var Mock|ConfigurationService $jobConfigurationService */
        $jobConfigurationService = \Mockery::mock(ConfigurationService::class);

        if (isset($calls['get'])) {
            $callValues = $calls['get'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $jobConfigurationService
                ->shouldReceive('get')
                ->with($with)
                ->andReturn($return);
        }

        return $jobConfigurationService;
    }

    /**
     * @return Mock|WebSiteService
     */
    public static function createWebSiteService()
    {
        /* @var Mock|WebSiteService $websiteService */
        $websiteService = \Mockery::mock(WebSiteService::class);

        return $websiteService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|TaskTypeService
     */
    public static function createTaskTypeService($calls = [])
    {
        /* @var Mock|TaskTypeService $taskTypeService */
        $taskTypeService = \Mockery::mock(TaskTypeService::class);

        if (isset($calls['get'])) {
            $callValues = $calls['get'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $taskTypeService
                ->shouldReceive('get')
                ->with($with)
                ->andReturn($return);
        }

        return $taskTypeService;
    }

    /**
     * @return Mock|JobTypeService
     */
    public static function createJobTypeService()
    {
        /* @var Mock|JobTypeService $jobTypeService */
        $jobTypeService = \Mockery::mock(JobTypeService::class);

        return $jobTypeService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ScheduledJobRepository
     */
    public static function createScheduledJobRepository($calls = [])
    {
        /* @var Mock|ScheduledJobRepository $scheduledJobRepository */
        $scheduledJobRepository = \Mockery::mock(ScheduledJobRepository::class);

        if (isset($calls['findOneBy'])) {
            $callValues = $calls['findOneBy'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $scheduledJobRepository
                ->shouldReceive('findOneBy')
                ->with($with)
                ->andReturn($return);
        }

        return $scheduledJobRepository;
    }

    /**
     * @param array $calls
     *
     * @return Mock|ScheduledJobService
     */
    public static function createScheduledJobService($calls = [])
    {
        /* @var Mock|ScheduledJobService $scheduledJobService */
        $scheduledJobService = \Mockery::mock(ScheduledJobService::class);

        if (isset($calls['get'])) {
            $callValues = $calls['get'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $scheduledJobService
                ->shouldReceive('get')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['delete'])) {
            $callValues = $calls['delete'];

            $with = $callValues['with'];

            $scheduledJobService
                ->shouldReceive('delete')
                ->with($with);
        }

        return $scheduledJobService;
    }

    /**
     * @return Mock|CronModifierValidationService
     */
    public static function createCronModifierValidationService()
    {
        /* @var Mock|CronModifierValidationService $validationService */
        $validationService = \Mockery::mock(CronModifierValidationService::class);

        return $validationService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|CompleteRequestFactory
     */
    public static function createCompleteRequestFactory($calls = [])
    {
        /* @var Mock|CompleteRequestFactory $completeRequestFactory */
        $completeRequestFactory = \Mockery::mock(CompleteRequestFactory::class);

        if (isset($calls['create'])) {
            $callValues = $calls['create'];

            $return = $callValues['return'];

            $completeRequestFactory
                ->shouldReceive('create')
                ->andReturn($return);
        }

        return $completeRequestFactory;
    }

    /**
     * @return Mock|JobPreparationService
     */
    public static function createJobPreparationService()
    {
        /* @var Mock|JobPreparationService $jobPreparationService */
        $jobPreparationService = \Mockery::mock(JobPreparationService::class);

        return $jobPreparationService;
    }

    /**
     * @return Mock|CrawlJobContainerService
     */
    public static function createCrawlJobContainerService()
    {
        /* @var Mock|CrawlJobContainerService $crawlJobContainerService */
        $crawlJobContainerService = \Mockery::mock(CrawlJobContainerService::class);

        return $crawlJobContainerService;
    }

    /**
     * @return Mock|TaskOutputJoinerFactory
     */
    public static function createTaskOutputJoinerFactory()
    {
        /* @var Mock|TaskOutputJoinerFactory $taskOutputJoinerFactory */
        $taskOutputJoinerFactory = \Mockery::mock(TaskOutputJoinerFactory::class);

        return $taskOutputJoinerFactory;
    }

    /**
     * @return Mock|TaskPostProcessorFactory
     */
    public static function createTaskPostProcessorFactory()
    {
        /* @var Mock|TaskPostProcessorFactory $taskPostProcessorFactory */
        $taskPostProcessorFactory = \Mockery::mock(TaskPostProcessorFactory::class);

        return $taskPostProcessorFactory;
    }

    /**
     * @return Mock|TaskTypeDomainsToIgnoreService
     */
    public static function createTaskTypeDomainsToIgnoreService()
    {
        /* @var Mock|TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService */
        $taskTypeDomainsToIgnoreService = \Mockery::mock(TaskTypeDomainsToIgnoreService::class);

        return $taskTypeDomainsToIgnoreService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|AccountPlanService
     */
    public static function createAccountPlanService($calls = [])
    {
        /* @var Mock|AccountPlanService $accountPlanService */
        $accountPlanService = \Mockery::mock(AccountPlanService::class);

        if (isset($calls['get'])) {
            $callValues = $calls['get'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $accountPlanService
                ->shouldReceive('get')
                ->with($with)
                ->andReturn($return);
        }

        return $accountPlanService;
    }

    /**
     * @param array $calls
     *
     * @return Mock|UserAccountPlanService
     */
    public static function createUserAccountPlanService($calls = [])
    {
        /* @var Mock|UserAccountPlanService $userAccountPlanService */
        $userAccountPlanService = \Mockery::mock(UserAccountPlanService::class);

        if (isset($calls['getForUser'])) {
            $callValues = $calls['getForUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userAccountPlanService
                ->shouldReceive('getForUser')
                ->with($with)
                ->andReturn($return);
        }

        return $userAccountPlanService;
    }

    /**
     * @return Mock|StripeService
     */
    public static function createStripeService()
    {
        /* @var Mock|StripeService $stripeService */
        $stripeService = \Mockery::mock(StripeService::class);

        return $stripeService;
    }

    /**
     * @return Mock|UserPostActivationPropertiesService
     */
    public static function createUserPostActivationPropertiesService()
    {
        /* @var Mock|UserPostActivationPropertiesService $userPostActivationPropertiesService */
        $userPostActivationPropertiesService = \Mockery::mock(UserPostActivationPropertiesService::class);

        return $userPostActivationPropertiesService;
    }

    /**
     * @return Mock|UserManipulator
     */
    public static function createUserManipulator()
    {
        /* @var Mock|UserManipulator $userManipulator */
        $userManipulator = \Mockery::mock(UserManipulator::class);

        return $userManipulator;
    }

    /**
     * @return Mock|CanonicalizerInterface
     */
    public static function createCanonicalizer()
    {
        /* @var Mock|CanonicalizerInterface $canonicalizer */
        $canonicalizer = \Mockery::mock(CanonicalizerInterface::class);

        return $canonicalizer;
    }

    /**
     * @param array $calls
     *
     * @return Mock|UserEmailChangeRequestService
     */
    public static function createUserEmailChangeRequestService($calls = [])
    {
        /* @var Mock|UserEmailChangeRequestService $userEmailChangeRequestService */
        $userEmailChangeRequestService = \Mockery::mock(UserEmailChangeRequestService::class);

        if (isset($calls['getForUser'])) {
            $callValues = $calls['getForUser'];

            $with = $callValues['with'];
            $return = $callValues['return'];

            $userEmailChangeRequestService
                ->shouldReceive('getForUser')
                ->with($with)
                ->andReturn($return);
        }

        if (isset($calls['removeForUser'])) {
            $callValues = $calls['removeForUser'];

            $with = $callValues['with'];

            $userEmailChangeRequestService
                ->shouldReceive('removeForUser')
                ->with($with);
        }

        return $userEmailChangeRequestService;
    }

    /**
     * @return Mock|WorkerActivationRequestService
     */
    public static function createWorkerActivationRequestService()
    {
        /* @var Mock|WorkerActivationRequestService $workerActivationRequestService */
        $workerActivationRequestService = \Mockery::mock(WorkerActivationRequestService::class);

        return $workerActivationRequestService;
    }

    /**
     * @return Mock|LoggerInterface
     */
    public static function createLogger()
    {
        /* @var Mock|LoggerInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        return $logger;
    }

    /**
     * @return Mock|WebsiteResolutionService
     */
    public static function createWebsiteResolutionService()
    {
        /* @var Mock|WebsiteResolutionService $websiteResolutionService */
        $websiteResolutionService = \Mockery::mock(WebsiteResolutionService::class);

        return $websiteResolutionService;
    }

    /**
     * @return Mock|EventDispatcherInterface
     */
    public static function createEventDispatcher()
    {
        /* @var Mock|EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        return $eventDispatcher;
    }

    /**
     * @return Mock|TaskPreProcessorFactory
     */
    public static function createTaskPreProcessorFactory()
    {
        /* @var Mock|TaskPreProcessorFactory $taskPreProcessorFactory */
        $taskPreProcessorFactory = \Mockery::mock(TaskPreProcessorFactory::class);

        return $taskPreProcessorFactory;
    }

    /**
     * @return Mock|WorkerTaskAssignmentService
     */
    public static function createWorkerTaskAssignmentService()
    {
        /* @var Mock|WorkerTaskAssignmentService $workerTaskAssignmentService */
        $workerTaskAssignmentService = \Mockery::mock(WorkerTaskAssignmentService::class);

        return $workerTaskAssignmentService;
    }

    /**
     * @return Mock|WorkerTaskCancellationService
     */
    public static function createWorkerTaskCancellationService()
    {
        /* @var Mock|WorkerTaskCancellationService $workerTaskCancellationService */
        $workerTaskCancellationService = \Mockery::mock(WorkerTaskCancellationService::class);

        return $workerTaskCancellationService;
    }

    /**
     * @return Mock|JobUserAccountPlanEnforcementService
     */
    public static function createJobUserAccountPlanEnforcementService()
    {
        /* @var Mock|JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService */
        $jobUserAccountPlanEnforcementService = \Mockery::mock(JobUserAccountPlanEnforcementService::class);

        return $jobUserAccountPlanEnforcementService;
    }

    /**
     * @return Mock|UrlFinder
     */
    public static function createUrlFinder()
    {
        /* @var Mock|UrlFinder $urlFinder */
        $urlFinder = \Mockery::mock(UrlFinder::class);

        return $urlFinder;
    }

    /**
     * @return Mock|CrawlJobUrlCollector
     */
    public static function createCrawlJobUrlCollector()
    {
        /* @var Mock|CrawlJobUrlCollector $crawlJobUrlCollector */
        $crawlJobUrlCollector = \Mockery::mock(CrawlJobUrlCollector::class);

        return $crawlJobUrlCollector;
    }
}
