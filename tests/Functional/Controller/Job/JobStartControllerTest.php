<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Controller\Job;

use App\Controller\Job\StartController;
use App\Entity\Job\Job;
use App\Entity\Job\RejectionReason;
use App\Entity\Job\TaskTypeOptions;
use App\Repository\JobRepository;
use App\Services\JobTypeService;
use App\Services\JobUserAccountPlanEnforcementService;
use App\Services\Request\Factory\Job\StartRequestFactory;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Tests\Functional\Controller\AbstractControllerTest;
use Symfony\Component\HttpFoundation\Response;

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
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobStartController = self::$container->get(StartController::class);
        $this->jobRepository = self::$container->get(JobRepository::class);
    }

    public function testStartActionGetRequest()
    {
        $router = self::$container->get('router');

        $requestUrl = $router->generate('job_start_start');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'GET',
            'parameters' => [
                'url' => 'http://example.com/',
            ],
        ]);

        $response = $this->getClientResponse();

        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }

    public function testStartActionPostRequest()
    {
        $router = self::$container->get('router');

        $siteRootUrl = 'http://example.com/';
        $requestUrl = $router->generate('job_start_start');

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'parameters' => [
                'url' => $siteRootUrl,
            ],
        ]);

        $response = $this->getClientResponse();

        /* @var Job $job */
        $job = $this->jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, (string) $job->getState());
    }

    public function testReTestActionPostRequest()
    {
        $router = self::$container->get('router');
        $siteRootUrl = 'http://example.com/';

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->create([
            JobFactory::KEY_URL => $siteRootUrl,
            JobFactory::KEY_STATE => Job::STATE_COMPLETED,
        ]);

        $requestUrl = $router->generate('job_start_retest', [
            'test_id' => $job->getId(),
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
        ]);

        $response = $this->getClientResponse();

        /* @var Job $newJob */
        $newJob = $this->jobRepository->findAll()[1];

        $this->assertNotEquals($job->getId(), $newJob->getId());

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $newJob->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, (string) $newJob->getState());
    }

    /**
     * @dataProvider startActionInvalidWebsiteDataProvider
     */
    public function testStartActionInvalidWebsite(string $siteRootUrl)
    {
        $userService = self::$container->get(UserService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $request = new Request([], [
            StartRequestFactory::PARAMETER_URL => $siteRootUrl,
        ]);

        $this->setUser($userService->getPublicUser());

        /* @var RedirectResponse $response */
        $response = $this->jobStartController->startAction($request);
        $this->assertInstanceOf(RedirectResponse::class, $response);

        /* @var Job $job */
        $job = $this->jobRepository->findAll()[0];
        $this->assertEquals(
            sprintf('http://localhost/job/%s/', $job->getId()),
            $response->getTargetUrl()
        );

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertEquals(Job::STATE_REJECTED, (string) $job->getState());
        $this->assertEquals('unroutable', $jobRejectionReason->getReason());
        $this->assertNull($jobRejectionReason->getConstraint());
    }

    public function startActionInvalidWebsiteDataProvider(): array
    {
        return [
            'unroutable host' => [
                'siteRootUrl' => 'http://foo/',
            ],
            'unix-like local path' => [
                'siteRootUrl' => '/home/users/foo',
            ],
            'windows-like local path' => [
                'siteRootUrl' => 'c:\Users\foo\Desktop\file.html',
            ],
            'not even close' => [
                'siteRootUrl' => 'vertical-align:top',
            ],
        ];
    }

    public function testStartActionAccountPlanLimitReached()
    {
        $userService = self::$container->get(UserService::class);
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $user = $userService->getPublicUser();
        $this->setUser($user);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $constraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $siteRootUrl = 'http://example.com/';

        $request = new Request([], [
            StartRequestFactory::PARAMETER_URL => $siteRootUrl,
        ]);

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->create([
            JobFactory::KEY_URL => $siteRootUrl,
        ]);
        $jobFactory->cancel($job);

        $response = $this->jobStartController->startAction($request);

        /* @var Job $job */
        $job = $this->jobRepository->findAll()[1];

        /* @var RejectionReason $jobRejectionReason */
        $jobRejectionReason = $jobRejectionReasonRepository->findOneBy([
            'job' => $job,
        ]);

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_REJECTED, (string) $job->getState());
        $this->assertEquals('plan-constraint-limit-reached', $jobRejectionReason->getReason());
        $this->assertEquals($constraint, $jobRejectionReason->getConstraint());
    }

    public function testStartActionSuccess()
    {
        $userService = self::$container->get(UserService::class);

        $siteRootUrl = 'http://example.com/';

        $request = new Request([], [
            StartRequestFactory::PARAMETER_URL => $siteRootUrl,
        ]);

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->startAction($request);

        /* @var Job $job */
        $job = $this->jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('http://localhost/job/' . $job->getId() . '/'));
        $this->assertEquals(Job::STATE_STARTING, (string) $job->getState());
    }

    public function retestActionForUnfinishedJobDataProvider(): array
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
     */
    public function testRetestAction(array $jobValues)
    {
        $userService = self::$container->get(UserService::class);
        $jobFactory = self::$container->get(JobFactory::class);

        $jobValues[JobFactory::KEY_STATE] = Job::STATE_COMPLETED;

        $job = $jobFactory->create($jobValues);

        $request = new Request();

        $this->setUser($userService->getPublicUser());

        $response = $this->jobStartController->retestAction($request, $job->getId());

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

    public function retestActionDataProvider(): array
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
