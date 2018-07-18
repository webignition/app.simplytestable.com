<?php

namespace Tests\AppBundle\Functional\Controller\Job;

use AppBundle\Controller\Job\StartController;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Job\RejectionReason;
use AppBundle\Entity\Job\TaskTypeOptions;
use AppBundle\Services\JobTypeService;
use AppBundle\Services\JobUserAccountPlanEnforcementService;
use AppBundle\Services\UserAccountPlanService;
use AppBundle\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\AppBundle\Factory\JobFactory;
use Symfony\Component\HttpFoundation\Request;
use Tests\AppBundle\Functional\Controller\AbstractControllerTest;

/**
 * @group Controller/Job/StartController
 */
class JobStartControllerTest extends AbstractControllerTest
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

        $this->jobStartController = self::$container->get(StartController::class);
    }

    public function testStartActionGetRequest()
    {
        $router = self::$container->get('router');
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
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

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, $job->getState()->getName());
    }

    public function testReTestActionGetRequest()
    {
        $router = self::$container->get('router');
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $siteRootUrl = 'http://example.com/';

        $jobFactory = new JobFactory(self::$container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
            JobFactory::KEY_STATE => Job::STATE_COMPLETED,
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

        /* @var Job $newJob */
        $newJob = $jobRepository->findAll()[1];

        $this->assertNotEquals($job->getId(), $newJob->getId());

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $siteRootUrl . '/' . $newJob->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, $newJob->getState()->getName());
    }

    /**
     * @dataProvider startActionInvalidWebsiteDataProvider
     *
     * @param string $siteRootUrl
     * @param string $expectedRejectedUrl
     */
    public function testStartActionInvalidWebsite($siteRootUrl, $expectedRejectedUrl)
    {
        $userService = self::$container->get(UserService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $request = new Request();
        $request->attributes->set('site_root_url', $siteRootUrl);

        $this->setUser($userService->getPublicUser());

        /* @var RedirectResponse $response */
        $response = $this->jobStartController->startAction($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];
        $this->assertEquals(
            sprintf('http://localhost/job/%s/%s/', $expectedRejectedUrl, $job->getId()),
            $response->getTargetUrl()
        );

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertEquals(Job::STATE_REJECTED, $job->getState()->getName());
        $this->assertEquals('unroutable', $jobRejectionReason->getReason());
        $this->assertNull($jobRejectionReason->getConstraint());
    }

    /**
     * @return array
     */
    public function startActionInvalidWebsiteDataProvider()
    {
        return [
            'unroutable host' => [
                'siteRootUrl' => 'http://foo/',
                'expectedRejectedUrl' => 'http://foo/',
            ],
            'unix-like local path' => [
                'siteRootUrl' => '/home/users/foo',
                'expectedRejectedUrl' => '/home/users/foo',
            ],
            'windows-like local path' => [
                'siteRootUrl' => 'c:\Users\foo\Desktop\file.html',
                'expectedRejectedUrl' => 'c:%5CUsers%5Cfoo%5CDesktop%5Cfile.html',
            ],
            'not even close' => [
                'siteRootUrl' => 'vertical-align:top',
                'expectedRejectedUrl' => 'vertical-align:top',
            ],
        ];
    }

    public function testStartActionAccountPlanLimitReached()
    {
        $userService = self::$container->get(UserService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
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

        $jobFactory = new JobFactory(self::$container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
        ]);
        $jobFactory->cancel($job);

        $response = $this->jobStartController->startAction($request);

        /* @var Job $job */
        $job = $jobRepository->findAll()[1];

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_REJECTED, $job->getState()->getName());
        $this->assertEquals('plan-constraint-limit-reached', $jobRejectionReason->getReason());
        $this->assertEquals($constraint, $jobRejectionReason->getConstraint());
    }

    public function testStartActionSuccess()
    {
        $userService = self::$container->get(UserService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);

        $siteRootUrl = 'http://example.com/';

        $request = new Request();
        $request->attributes->set('site_root_url', $siteRootUrl);

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->startAction($request);

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, $job->getState()->getName());
    }

    /**
     * @return array
     */
    public function retestActionForUnfinishedJobDataProvider()
    {
        return [
            Job::STATE_STARTING => [
                'stateName' => Job::STATE_STARTING,
            ],
            Job::STATE_RESOLVING => [
                'stateName' => Job::STATE_RESOLVING,
            ],
            Job::STATE_RESOLVED => [
                'stateName' => Job::STATE_RESOLVED,
            ],
            Job::STATE_IN_PROGRESS => [
                'stateName' => Job::STATE_IN_PROGRESS,
            ],
            Job::STATE_PREPARING => [
                'stateName' => Job::STATE_PREPARING,
            ],
            Job::STATE_QUEUED => [
                'stateName' => Job::STATE_QUEUED,
            ],
        ];
    }

    /**
     * @dataProvider retestActionDataProvider
     *
     * @param array $jobValues
     */
    public function testRetestActionFoo($jobValues)
    {
        $userService = self::$container->get(UserService::class);
        $jobFactory = new JobFactory(self::$container);

        $jobValues[JobFactory::KEY_STATE] = Job::STATE_COMPLETED;

        $job = $jobFactory->create($jobValues);

        $request = new Request();

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->retestAction(
            $request,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $newJob = $jobFactory->getFromResponse($response);

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
}
