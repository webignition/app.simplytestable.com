<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Controller\Job\StartController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JobStartControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var StartController
     */
    private $jobStartController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobStartController = new StartController();
        $this->jobStartController->setContainer($this->container);
    }

    public function testStartActionRequest()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $router = $this->container->get('router');
        $jobRepository = $entityManager->getRepository(Job::class);
        $siteRootUrl = 'http://example.com/';

        $requestUrl = $router->generate('job_start_start', [
            'site_root_url' => $siteRootUrl,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
        ]);

        $response = $this->getClientResponse();

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(JobService::STARTING_STATE, $job->getState()->getName());
    }

    public function testReTestActionRequest()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $router = $this->container->get('router');
        $jobRepository = $entityManager->getRepository(Job::class);
        $siteRootUrl = 'http://example.com/';

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
            JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
        ]);

        $requestUrl = $router->generate('job_start_retest', [
            'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            'test_id' => $job->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
        ]);

        $response = $this->getClientResponse();

        /* @var Job $job */
        $newJob = $jobRepository->findAll()[1];

        $this->assertNotEquals($job->getId(), $newJob->getId());

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $newJob->getId() . '/'));
        $this->assertEquals(JobService::STARTING_STATE, $newJob->getState()->getName());
    }

    public function testStartActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $request = new Request();
        $this->container->get('request_stack')->push($request);

        $response = $this->jobStartController->startAction();
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->jobStartController->startAction();
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testStartActionUnroutableWebsite()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $siteRootUrl = 'foo';

        $request = new Request();
        $request->attributes->set('site_root_url', $siteRootUrl);
        $this->container->get('request_stack')->push($request);

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->startAction();

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(JobService::REJECTED_STATE, $job->getState()->getName());
        $this->assertEquals('unroutable', $jobRejectionReason->getReason());
        $this->assertNull($jobRejectionReason->getConstraint());
    }

    public function testStartActionAccountPlanLimitReached()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $constraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $siteRootUrl = 'http://example.com/';

        $request = new Request();
        $request->attributes->set('site_root_url', $siteRootUrl);
        $this->container->get('request_stack')->push($request);

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
        ]);
        $jobFactory->cancel($job);

        $response = $this->jobStartController->startAction();

        /* @var Job $job */
        $job = $jobRepository->findAll()[1];

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(JobService::REJECTED_STATE, $job->getState()->getName());
        $this->assertEquals('plan-constraint-limit-reached', $jobRejectionReason->getReason());
        $this->assertEquals($constraint, $jobRejectionReason->getConstraint());
    }

    public function testStartActionSuccess()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);

        $siteRootUrl = 'http://example.com/';

        $request = new Request();
        $request->attributes->set('site_root_url', $siteRootUrl);
        $this->container->get('request_stack')->push($request);

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->startAction();

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(JobService::STARTING_STATE, $job->getState()->getName());
    }

    public function testRetestActionInvalidJobId()
    {
        $response = $this->jobStartController->retestAction(new Request(), 'foo', 1);

        $this->assertTrue($response->isClientError());
    }

    /**
     * @dataProvider retestActionForUnfinishedJobDataProvider
     *
     * @param string $stateName
     */
    public function testRetestActionForUnfinishedJob($stateName)
    {
        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_STATE => $stateName,
        ]);

        $response = $this->jobStartController->retestAction(new Request(), 'foo', $job->getId());

        $this->assertTrue($response->isClientError());
    }

    /**
     * @return array
     */
    public function retestActionForUnfinishedJobDataProvider()
    {
        return [
            'JobService::STARTING_STATE' => [
                'stateName' => JobService::STARTING_STATE,
            ],
            'JobService::RESOLVING_STATE' => [
                'stateName' => JobService::RESOLVING_STATE,
            ],
            'JobService::RESOLVED_STATE' => [
                'stateName' => JobService::RESOLVED_STATE,
            ],
            'JobService::IN_PROGRESS_STATE' => [
                'stateName' => JobService::IN_PROGRESS_STATE,
            ],
            'JobService::PREPARING_STATE' => [
                'stateName' => JobService::PREPARING_STATE,
            ],
            'JobService::QUEUED_STATE' => [
                'stateName' => JobService::QUEUED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider retestActionDataProvider
     *
     * @param array $jobValues
     */
    public function testRetestAction($jobValues)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $jobFactory = new JobFactory($this->container);

        $jobValues[JobFactory::KEY_STATE] = JobService::COMPLETED_STATE;

        $job = $jobFactory->create($jobValues);

        $request = new Request();
        $this->container->get('request_stack')->push($request);

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->retestAction(
            $request,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $newJob = $this->getJobFromResponse($response);

        $this->assertNotEquals($job->getId(), $newJob->getId());
        $this->assertEquals($job->getType(), $newJob->getType());
        $this->assertEquals($job->getTaskTypeCollection(), $newJob->getTaskTypeCollection());

        $jobTaskTypeOptions = $job->getTaskTypeOptions();
        $newJobTaskTypeOptions = $newJob->getTaskTypeOptions();

        $this->assertCount(count($jobTaskTypeOptions), $newJobTaskTypeOptions);

        foreach ($jobTaskTypeOptions as $taskTypeOptionsIndex => $expectedTaskTypeOptions) {
            /* @var TaskTypeOptions $taskTypeOptions */
            $this->assertEquals(
                $expectedTaskTypeOptions->getOptions(),
                $newJobTaskTypeOptions[$taskTypeOptionsIndex]->getOptions()
            );
        }
    }

    /**
     * @return array
     */
    public function retestActionDataProvider()
    {
        return [
            'set one' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::FULL_SITE_NAME,
                    JobFactory::KEY_TEST_TYPES => [
                        'HTML validation',
                    ],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        'HTML validation' => [
                            'html-foo' => 'html-bar',
                        ]
                    ],
                ],
            ],
            'set two' => [
                'jobValues' => [
                    JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
                    JobFactory::KEY_TEST_TYPES => [
                        'CSS validation',
                        'Link integrity',
                    ],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        'Link integrity' => [
                            'link-integrity-foo' => 'link-integrity-bar',
                        ]
                    ],
                ],
            ],
        ];
    }

    /**
     * @param User $user
     */
    private function setUser(User $user)
    {
        $securityTokenStorage = $this->container->get('security.token_storage');

        /* @var MockInterface|TokenInterface */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($user);

        $securityTokenStorage->setToken($token);
    }
}
