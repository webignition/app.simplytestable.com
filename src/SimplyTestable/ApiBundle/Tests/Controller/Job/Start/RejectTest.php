<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Message\Response as GuzzleResponse;

class RejectTest extends ActionTest
{
    public function testRejectDueToPlanFullSiteConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');
        $constraintLimit = $constraint->getLimit();

        $jobFactory = $this->createJobFactory();

        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            ]);
            $this->cancelJob($job);
        }

        $request = new Request();
        $startController = $this->createJobStartController($request);

        $rejectedJobResponse = $startController->startAction($request, $canonicalUrl);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(
            $this->getJobService()->getRejectedState(),
            $rejectedJob->getState()
        );
    }

    public function testRejectDueToPlanSingleUrlConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $constraint = $userAccountPlan->getPlan()->getConstraintNamed('single_url_jobs_per_url');
        $constraintLimit = $constraint->getLimit();

        $jobFactory = $this->createJobFactory();

        for ($i = 0; $i < $constraintLimit; $i++) {
            $job = $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            ]);
            $this->cancelJob($job);
        }

        $request = new Request([], [
            'type' => JobTypeService::SINGLE_URL_NAME,
        ]);
        $startController = $this->createJobStartController($request);

        $rejectedJobResponse = $startController->startAction($request, $canonicalUrl);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertTrue($rejectedJob->getState()->equals($this->getJobService()->getRejectedState()));
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
        $startController = $this->createJobStartController($request);

        $rejectedJobResponse = $startController->startAction($request, $url);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals($this->getJobService()->getRejectedState(), $rejectedJob->getState());
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
        $creditsPerMonth = 3;
        $user = $this->createUserFactory()->create('user-basic@example.com');

        $this->getAccountPlanService()
            ->find('basic')
            ->getConstraintNamed('credits_per_month')
            ->setLimit($creditsPerMonth);

        $jobFactory = $this->createJobFactory();

        $this->queueStandardJobHttpFixtures();
        $job = $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => self::DEFAULT_CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);
        $this->completeJob($job);

        $request = new Request([], [
            'user' => $user->getEmail(),
        ]);
        $startController = $this->createJobStartController($request);

        $rejectedJobResponse = $startController->startAction($request, self::DEFAULT_CANONICAL_URL);
        $rejectedJob = $this->getJobFromResponse($rejectedJobResponse);

        $this->assertEquals(
            $this->getJobService()->getRejectedState(),
            $rejectedJob->getState()
        );

        $rejectionReason = $this->getJobRejectionReasonService()->getForJob($rejectedJob);

        $this->assertEquals('plan-constraint-limit-reached', $rejectionReason->getReason());
        $this->assertEquals('credits_per_month', $rejectionReason->getConstraint()->getName());
    }

    private function queueStandardJobHttpFixtures()
    {
        $this->queueHttpFixtures([
            GuzzleResponse::fromMessage('HTTP/1.1 200 OK'),
            GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml"),
            GuzzleResponse::fromMessage(sprintf(
                "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
                SitemapFixtureFactory::load('example.com-three-urls')
            )),
        ]);
    }
}
