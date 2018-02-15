<?php

namespace Tests\ApiBundle\Functional\Services\Job\Retrieval;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\Job\Configuration as JobConfiguration;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Services\Job\StartService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\Job\Start\Exception as JobStartServiceException;
use SimplyTestable\ApiBundle\Exception\Services\Job\UserAccountPlan\Enforcement\Exception as
    UserAccountPlanEnforcementException;

class StartServiceTest extends AbstractBaseTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var WebSiteService
     */
    private $websiteService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteService = $this->container->get(WebSiteService::class);
    }

    public function testStartWithUnroutableWebsite()
    {
        $this->expectException(JobStartServiceException::class);
        $this->expectExceptionMessage('Unroutable website');
        $this->expectExceptionCode(JobStartServiceException::CODE_UNROUTABLE_WEBSITE);

        $website = new WebSite();
        $website->setCanonicalUrl('http://foo');

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setWebsite($website);

        $jobStartService = $this->createJobStartService();

        $jobStartService->start($jobConfiguration);
    }

    /**
     * @dataProvider startFailsAccountPlanEnforcementDataProvider
     *
     * @param string $user
     * @param string $plan
     * @param string $jobTypeName
     * @param string $isLimitReachedMethodName
     * @param $isLimitReachedReturnValue
     * @param $isUserCreditLimitReached
     * @param string $constraintName
     * @param array $expectedException
     */
    public function testStartFailsAccountPlanEnforcement(
        $user,
        $plan,
        $jobTypeName,
        $isLimitReachedMethodName,
        $isLimitReachedReturnValue,
        $isUserCreditLimitReached,
        $constraintName,
        $expectedException
    ) {
        $jobUserAccountPlanEnforcementService = $this->container->get(JobUserAccountPlanEnforcementService::class);
        $jobTypeService = $this->container->get(JobTypeService::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $user,
            UserFactory::KEY_PLAN_NAME => $plan,
        ]);

        $jobUserAccountPlanEnforcementService->setUser($user);

          $jobType = $jobTypeService->get($jobTypeName);

        $website = $this->websiteService->get('http://example.com');

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setUser($user);
        $jobConfiguration->setType($jobType);

        $mockJobUserAccountPlanEnforcementService = $this->createJobUserAccountPlanEnforcementService(
            $user,
            $jobType,
            [
                $isLimitReachedMethodName => $isLimitReachedReturnValue,
                'isUserCreditLimitReached' => $isUserCreditLimitReached
            ]
        );

        $jobStartService = $this->createJobStartService([
            JobUserAccountPlanEnforcementService::class => $mockJobUserAccountPlanEnforcementService
        ]);

        $this->expectException($expectedException['class']);
        $this->expectExceptionMessage($expectedException['message']);
        $this->expectExceptionCode($expectedException['code']);

        $jobStartService->start($jobConfiguration);
    }

    /**
     * @return array
     */
    public function startFailsAccountPlanEnforcementDataProvider()
    {
        return [
            'full site job limit' => [
                'user' => 'foo@example.com',
                'plan' => 'public',
                'jobTypeName' => 'Full site',
                'isLimitReachedMethodName' => 'isFullSiteJobLimitReachedForWebSite',
                'isLimitReachedReturnValue' => true,
                'isCreditLimitReached' => false,
                'constraintName' => JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME,
                'expectedException' => [
                    'class' => UserAccountPlanEnforcementException::class,
                    'message' => 'Full site job limit reached for website',
                    'code' => UserAccountPlanEnforcementException::CODE_FULL_SITE_JOB_LIMIT_REACHED,
                ],
            ],
            'single url job limit' => [
                'user' => 'foo@example.com',
                'plan' => 'public',
                'jobTypeName' => 'Single URL',
                'isLimitReachedMethodName' => 'isSingleUrlLimitReachedForWebsite',
                'isLimitReachedReturnValue' => true,
                'isCreditLimitReached' => false,
                'constraintName' => JobUserAccountPlanEnforcementService::SINGLE_URL_JOBS_PER_URL_CONSTRAINT_NAME,
                'expectedException' => [
                    'class' => UserAccountPlanEnforcementException::class,
                    'message' => 'Single URL job limit reached for website',
                    'code' => UserAccountPlanEnforcementException::CODE_SINGLE_URL_JOB_LIMIT_REACHED,
                ],
            ],
            'credit limit reached' => [
                'user' => 'user@example.com',
                'plan' => 'agency',
                'jobTypeName' => 'Full site',
                'isLimitReachedMethodName' => 'isFullSiteJobLimitReachedForWebSite',
                'isLimitReachedReturnValue' => false,
                'isCreditLimitReached' => true,
                'constraintName' => JobUserAccountPlanEnforcementService::CREDITS_PER_MONTH_CONSTRAINT_NAME,
                'expectedException' => [
                    'class' => UserAccountPlanEnforcementException::class,
                    'message' => 'Credit limit reached',
                    'code' => UserAccountPlanEnforcementException::CODE_CREDIT_LIMIT_REACHED,
                ],
            ],
        ];
    }

    public function testReuseExistingJob()
    {
        $userService = $this->container->get(UserService::class);
        $jobTypeService = $this->container->get(JobTypeService::class);

        $user = $userService->getPublicUser();
        $jobType = $jobTypeService->getFullSiteType();
        $website = $this->websiteService->get('http://example.com');

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setUser($user);
        $jobConfiguration->setType($jobType);


        $jobStartService = $this->createJobStartService();

        /* @var Job[] $jobs */
        $jobs = [
            $jobStartService->start($jobConfiguration),
            $jobStartService->start($jobConfiguration)
        ];

        $this->assertEquals($jobs[0], $jobs[1]);
    }

    /**
     * @dataProvider startDataProvider
     *
     * @param string $userEmail
     * @param string $url
     * @param string $jobTypeName
     * @param bool $expectedIsPublic
     */
    public function testStart($userEmail, $url, $jobTypeName, $expectedIsPublic)
    {
        $resqueQueueService = $this->container->get(QueueService::class);
        $jobTypeService = $this->container->get(JobTypeService::class);

        $resqueQueueService->getResque()->getQueue('job-resolve')->clear();

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $jobType = $jobTypeService->get($jobTypeName);
        $website = $this->websiteService->get($url);

        $jobConfiguration = new JobConfiguration();
        $jobConfiguration->setWebsite($website);
        $jobConfiguration->setUser($user);
        $jobConfiguration->setType($jobType);

        $jobStartService = $this->createJobStartService();

        $job = $jobStartService->start($jobConfiguration);

        $this->assertEquals($expectedIsPublic, $job->getIsPublic());

        $this->assertTrue($resqueQueueService->contains(
            'job-resolve',
            ['id' => $job->getId(),]
        ));
    }

    /**
     * @return array
     */
    public function startDataProvider()
    {
        return [
            'public user full site' => [
                'userEmail' => 'public@simplytestable.com',
                'url' => 'http://example.com',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'expectedIsPublic' => true,
            ],
            'public user single url' => [
                'userEmail' => 'public@simplytestable.com',
                'url' => 'http://example.com',
                'jobTypeName' => JobTypeService::SINGLE_URL_NAME,
                'expectedIsPublic' => true,
            ],
            'private user full site' => [
                'userEmail' => 'private@example.com',
                'url' => 'http://example.com',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'expectedIsPublic' => false,
            ],
        ];
    }

    /**
     * @param User $user
     * @param Type $type
     * @param array $calls
     *
     * @return Mock|JobUserAccountPlanEnforcementService
     */
    private function createJobUserAccountPlanEnforcementService(User $user, Type $type, $calls)
    {
        /* @var Mock|JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService */
        $jobUserAccountPlanEnforcementService = \Mockery::mock(JobUserAccountPlanEnforcementService::class);
        $jobUserAccountPlanEnforcementService
            ->shouldReceive('setUser')
            ->with($user);

        $jobUserAccountPlanEnforcementService
            ->shouldReceive('setJobType')
            ->with($type);

        foreach ($calls as $method => $returnValue) {
            $jobUserAccountPlanEnforcementService
                ->shouldReceive($method)
                ->andReturn($returnValue);
        }

        return $jobUserAccountPlanEnforcementService;
    }

    /**
     * @param array $services
     *
     * @return StartService
     */
    private function createJobStartService($services = [])
    {
        $requiredServiceIds = [
            JobUserAccountPlanEnforcementService::class,
            JobTypeService::class,
            JobService::class,
            UserService::class,
            QueueService::class,
            StateService::class,
            UserAccountPlanService::class,
            ResqueJobFactory::class,
            'doctrine.orm.entity_manager',
        ];

        $requiredServices = [];

        foreach ($services as $serviceId => $service) {
            $requiredServices[$serviceId] = $service;
        }

        foreach ($requiredServiceIds as $requiredServiceId) {
            if (!array_key_exists($requiredServiceId, $requiredServices)) {
                $requiredServices[$requiredServiceId] = $this->container->get($requiredServiceId);
            }
        }

        return new StartService(
            $requiredServices[JobUserAccountPlanEnforcementService::class],
            $requiredServices[JobTypeService::class],
            $requiredServices[JobService::class],
            $requiredServices[UserService::class],
            $requiredServices[QueueService::class],
            $requiredServices[StateService::class],
            $requiredServices[UserAccountPlanService::class],
            $requiredServices[ResqueJobFactory::class],
            $requiredServices['doctrine.orm.entity_manager']
        );
    }
}
