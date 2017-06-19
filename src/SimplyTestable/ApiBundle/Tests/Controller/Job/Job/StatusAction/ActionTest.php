<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\StatusAction;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Guzzle\Http\Message\Response as GuzzleResponse;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use Symfony\Component\HttpFoundation\Request;

class ActionTest extends BaseControllerJsonTestCase
{
    protected function getActionName()
    {
        return 'statusAction';
    }

    public function testStatusAction()
    {
        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $response = $jobController->statusAction($canonicalUrl, $job->getId());
        $responseJsonObject = json_decode($response->getContent());

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals($job->getId(), $responseJsonObject->id);
        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertEquals($canonicalUrl, $responseJsonObject->website);
        $this->assertEquals('new', $responseJsonObject->state);
        $this->assertEquals(0, $responseJsonObject->url_count);
        $this->assertEquals(0, $responseJsonObject->task_count);
        $this->assertEquals('Full site', $responseJsonObject->type);

        foreach ($responseJsonObject->task_count_by_state as $stateName => $taskCount) {
            $this->assertEquals(0, $taskCount);
        }

        $this->assertEquals(0, $responseJsonObject->errored_task_count);
        $this->assertEquals(0, $responseJsonObject->cancelled_task_count);
        $this->assertEquals(0, $responseJsonObject->skipped_task_count);
        $this->assertEquals(0, $responseJsonObject->warninged_task_count);
    }

    public function testStatusForRejectedDueToPlanFullSiteConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $fullSiteJobsPerSiteConstraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');

        $jobFactory = $this->createJobFactory();

        $rejectedJob = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $jobFactory->reject($rejectedJob, 'plan-constraint-limit-reached', $fullSiteJobsPerSiteConstraint);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $jobStatusObject = json_decode(
            $jobController->statusAction($canonicalUrl, $rejectedJob->getId())->getContent()
        );

        $this->assertNotNull($jobStatusObject->rejection);
        $this->assertEquals('plan-constraint-limit-reached', $jobStatusObject->rejection->reason);

        $this->assertNotNull($jobStatusObject->rejection->constraint);
        $this->assertNotNull($jobStatusObject->rejection->constraint->name);
        $this->assertEquals('full_site_jobs_per_site', $jobStatusObject->rejection->constraint->name);
    }

    public function testStatusForRejectedDueToPlanSingleUrlConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $singleJobsPerUrlConstraint = $userAccountPlan->getPlan()->getConstraintNamed('single_url_jobs_per_url');

        $jobFactory = $this->createJobFactory();

        $rejectedJob = $jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $jobFactory->reject($rejectedJob, 'plan-constraint-limit-reached', $singleJobsPerUrlConstraint);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $jobStatusObject = json_decode(
            $jobController->statusAction($canonicalUrl, $rejectedJob->getId())->getContent()
        );

        $this->assertNotNull($jobStatusObject->rejection);
        $this->assertEquals('plan-constraint-limit-reached', $jobStatusObject->rejection->reason);

        $this->assertNotNull($jobStatusObject->rejection->constraint);
        $this->assertNotNull($jobStatusObject->rejection->constraint->name);
        $this->assertEquals('single_url_jobs_per_url', $jobStatusObject->rejection->constraint->name);
    }

    public function testStatusForJobUrlLimitAmmendment()
    {
        $this->createJobFactory();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->createJobFactory()->createResolveAndPrepare([], [
            'prepare' => [
                GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml"),
                GuzzleResponse::fromMessage(sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
                    SitemapFixtureFactory::load('example.com-eleven-urls')
                )),
            ],
        ]);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $statusResponse = $jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
        $jobObject = json_decode($statusResponse->getContent());

        $this->assertNotNull($jobObject->ammendments);
        $this->assertEquals(1, count($jobObject->ammendments));
        $this->assertEquals('plan-url-limit-reached:discovered-url-count-11', $jobObject->ammendments[0]->reason);
        $this->assertEquals('urls_per_job', $jobObject->ammendments[0]->constraint->name);
    }

    public function testDefaultIsPublicIfOwnedByPublicUser()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $statusResponse = $jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals(200, $statusResponse->getStatusCode());

        $this->assertEquals('public', $responseJsonObject->user);
        $this->assertEquals(true, $responseJsonObject->is_public);
    }

    public function testDefaultIsPrivateIfNotOwnedByPublicUser()
    {
        $user = $this->createAndActivateUser('user@example.com', 'password1');
        $this->getUserService()->setUser($user);

        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $user,
        ]);

        $request = new Request([], [
            'user' => $user->getEmail(),
        ]);
        $jobController = $this->createControllerFactory()->createJobController($request);
        $statusResponse = $jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals(200, $statusResponse->getStatusCode());

        $this->assertEquals($user->getEmail(), $responseJsonObject->user);
        $this->assertEquals(false, $responseJsonObject->is_public);
    }

    public function testParametersAreExposed()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $job = $this->createJobFactory()->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password',
            ],
        ]);

        $jobController = $this->createControllerFactory()->createJobController(new Request());
        $statusResponse = $jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
        $responseJsonObject = json_decode($statusResponse->getContent());

        $this->assertEquals(200, $statusResponse->getStatusCode());

        $this->assertTrue(isset($responseJsonObject->parameters));
        $this->assertEquals(
            '{"http-auth-username":"example","http-auth-password":"password"}',
            $responseJsonObject->parameters
        );
    }
}
