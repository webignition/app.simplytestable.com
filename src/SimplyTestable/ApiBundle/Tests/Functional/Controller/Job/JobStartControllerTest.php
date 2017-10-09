<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job;

use SimplyTestable\ApiBundle\Controller\Job\StartController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\RejectionReason;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\HttpFoundation\Request;

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

    public function testStartActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $response = $this->jobStartController->startAction(new Request(), 'foo');
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY);

        $response = $this->jobStartController->startAction(new Request(), 'foo');
        $this->assertEquals(503, $response->getStatusCode());

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }

    public function testStartActionUnroutableWebsite()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $jobRejectionReasonRepository = $entityManager->getRepository(RejectionReason::class);

        $request = new Request();
        $this->container->set('request', $request);

        $siteRootUrl = 'foo';

        $response = $this->jobStartController->startAction($request, $siteRootUrl);

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
        $userService->setUser($user);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $plan = $userAccountPlan->getPlan();
        $constraint = $plan->getConstraintNamed(
            JobUserAccountPlanEnforcementService::FULL_SITE_JOBS_PER_SITE_CONSTRAINT_NAME
        );

        $request = new Request();
        $this->container->set('request', $request);

        $siteRootUrl = 'http://example.com/';

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $siteRootUrl,
        ]);
        $jobFactory->cancel($job);

        $response = $this->jobStartController->startAction($request, $siteRootUrl);

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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);

        $request = new Request();
        $this->container->set('request', $request);

        $siteRootUrl = 'http://example.com/';

        $response = $this->jobStartController->startAction($request, $siteRootUrl);

        /* @var Job $job */
        $job = $jobRepository->findAll()[0];

        $this->assertTrue($response->isRedirect('/job/' . $siteRootUrl . '/' . $job->getId() . '/'));
        $this->assertEquals(JobService::STARTING_STATE, $job->getState()->getName());
    }
}
