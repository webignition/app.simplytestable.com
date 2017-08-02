<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Request;

class RejectTest extends ActionTest
{
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

    public function testRejectDueToPlanFullSiteConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');
        $constraintLimit = $constraint->getLimit();

        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $this->jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            ]);
            $this->cancelJob($job);
        }

        $request = new Request();
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);

        $rejectedJobResponse = $jobStartController->startAction($request, $canonicalUrl);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(JobService::REJECTED_STATE, $rejectedJob->getState()->getName());
    }

    public function testRejectDueToPlanSingleUrlConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('single_url_jobs_per_url');
        $constraintLimit = $constraint->getLimit();

        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $this->jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            ]);
            $this->cancelJob($job);
        }

        $request = new Request([], [
            'type' => JobTypeService::SINGLE_URL_NAME,
        ]);
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);

        $rejectedJobResponse = $jobStartController->startAction($request, $canonicalUrl);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(JobService::REJECTED_STATE, $rejectedJob->getState()->getName());
    }

    /**
     * @dataProvider rejectAsUnroutableDataProvider
     *
     * @param string $url
     */
    public function testRejectAsUnroutable($url)
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $request = new Request();
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);

        $rejectedJobResponse = $jobStartController->startAction($request, $url);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(JobService::REJECTED_STATE, $rejectedJob->getState()->getName());
        $this->assertEquals(
            'unroutable',
            $this->getJobRejectionReasonService()->getForJob($rejectedJob)->getReason()
        );
    }

    /**
     * @return array
     */
    public function rejectAsUnroutableDataProvider()
    {
        return [
            'unroutable ip' => [
                'url' => 'http://127.0.0.1/'
            ],
            'unroutable host' => [
                'url' => 'http://foo/'
            ],
        ];
    }

    public function testRejectWithCreditLimitReached()
    {
        $userFactory = new UserFactory($this->container);

        $creditsPerMonth = 3;
        $user = $userFactory->create('user-basic@example.com');

        $this->getAccountPlanService()
            ->find('basic')
            ->getConstraintNamed('credits_per_month')
            ->setLimit($creditsPerMonth);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => self::DEFAULT_CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);
        $this->completeJob($job);

        $request = new Request([], [
            'user' => $user->getEmail(),
        ]);
        $jobStartController = $this->createControllerFactory()->createJobStartController($request);

        $rejectedJobResponse = $jobStartController->startAction($request, self::DEFAULT_CANONICAL_URL);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(JobService::REJECTED_STATE, $rejectedJob->getState()->getName());

        $rejectionReason = $this->getJobRejectionReasonService()->getForJob($rejectedJob);

        $this->assertEquals('plan-constraint-limit-reached', $rejectionReason->getReason());
        $this->assertEquals('credits_per_month', $rejectionReason->getConstraint()->getName());
    }
}
