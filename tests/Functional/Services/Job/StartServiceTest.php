<?php

namespace App\Tests\Functional\Services\Job;

use App\Repository\JobRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use App\Entity\Job\Configuration as JobConfiguration;
use App\Entity\Job\Job;
use App\Entity\Job\Type;
use App\Entity\User;
use App\Entity\WebSite;
use App\Services\Job\StartService;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\Resque\QueueService;
use App\Services\StateService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Services\WebSiteService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Exception\Services\Job\Start\Exception as JobStartServiceException;
use App\Exception\Services\Job\UserAccountPlan\Enforcement\Exception as
    UserAccountPlanEnforcementException;
use App\Model\Job\TaskConfiguration\Collection as TaskConfigurationCollection;

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

        $this->websiteService = self::$container->get(WebSiteService::class);
    }

    /**
     * @dataProvider startWithInvalidWebsiteDataProvider
     *
     * @param string $url
     *
     * @throws JobStartServiceException
     * @throws UserAccountPlanEnforcementException
     */
    public function testStartWithInvalidWebsite($url)
    {
        $this->expectException(JobStartServiceException::class);
        $this->expectExceptionMessage('Unroutable website');
        $this->expectExceptionCode(JobStartServiceException::CODE_UNROUTABLE_WEBSITE);

        $website = new WebSite();
        $website->setCanonicalUrl($url);

        $jobConfiguration = JobConfiguration::create(
            '',
            \Mockery::mock(User::class),
            $website,
            \Mockery::mock(Type::class),
            new TaskConfigurationCollection(),
            ''
        );

        $jobStartService = $this->createJobStartService();

        $jobStartService->start($jobConfiguration);
    }

    /**
     * @return array
     */
    public function startWithInvalidWebsiteDataProvider()
    {
        return [
            'unroutable host' => [
                'url' => 'http://foo',
            ],
            'unix-like local path' => [
                'url' => '/home/users/foo',
            ],
            'windows-like local path' => [
                'url' => 'c:\Users\foo\Desktop\file.html',
            ],
            'not even close' => [
                'url' => 'vertical-align:top',
            ],
        ];
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
     * @param array $expectedException
     */
    public function testStartFailsAccountPlanEnforcement(
        $user,
        $plan,
        $jobTypeName,
        $isLimitReachedMethodName,
        $isLimitReachedReturnValue,
        $isUserCreditLimitReached,
        $expectedException
    ) {
        $jobUserAccountPlanEnforcementService = self::$container->get(JobUserAccountPlanEnforcementService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $user,
            UserFactory::KEY_PLAN_NAME => $plan,
        ]);

        $jobUserAccountPlanEnforcementService->setUser($user);

          $jobType = $jobTypeService->get($jobTypeName);

        $website = $this->websiteService->get('http://example.com');

        $jobConfiguration = JobConfiguration::create(
            '',
            $user,
            $website,
            $jobType,
            new TaskConfigurationCollection(),
            ''
        );

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
        $userService = self::$container->get(UserService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $user = $userService->getPublicUser();
        $jobType = $jobTypeService->getFullSiteType();
        $website = $this->websiteService->get('http://example.com');

        $jobConfiguration = JobConfiguration::create(
            '',
            $user,
            $website,
            $jobType,
            new TaskConfigurationCollection(),
            ''
        );

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
        $resqueQueueService = self::$container->get(QueueService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $resqueQueueService->getResque()->getQueue('job-resolve')->clear();

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_EMAIL => $userEmail,
        ]);

        $jobType = $jobTypeService->get($jobTypeName);
        $website = $this->websiteService->get($url);

        $jobConfiguration = JobConfiguration::create(
            '',
            $user,
            $website,
            $jobType,
            new TaskConfigurationCollection(),
            ''
        );

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
            'doctrine.orm.entity_manager',
            JobRepository::class
        ];

        $requiredServices = [];

        foreach ($services as $serviceId => $service) {
            $requiredServices[$serviceId] = $service;
        }

        foreach ($requiredServiceIds as $requiredServiceId) {
            if (!array_key_exists($requiredServiceId, $requiredServices)) {
                $requiredServices[$requiredServiceId] = self::$container->get($requiredServiceId);
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
            $requiredServices['doctrine.orm.entity_manager'],
            $requiredServices[JobRepository::class]
        );
    }
}
