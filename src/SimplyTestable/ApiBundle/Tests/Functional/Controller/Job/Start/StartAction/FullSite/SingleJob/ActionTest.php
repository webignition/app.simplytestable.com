<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction\FullSite\SingleJob;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Start\StartAction\FullSite\ActionTest as BaseActionTest;

class ActionTest extends BaseActionTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private $response;

    /**
     * @var Job
     */
    private $job;

    protected function setUp() {
        parent::setUp();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(
            $this->container->get('request'),
            self::CANONICAL_URL
        );
//        $this->jobId = $this->getJobIdFromUrl($this->response->getTargetUrl());
        $this->job = $this->getJobFromResponse($this->response);
    }


    public function testResponseStatusCode() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }


    public function testJobId() {
        $this->assertGreaterThan(0, $this->job->getId());
    }

    public function testJobResolveResqueJobIsInQueue() {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $this->assertTrue($resqueQueueService->contains(
            'job-resolve',
            ['id' => $this->job->getId()]
        ));
    }


    /**
     *
     * @return array
     */
    protected function getRouteParameters() {
        return [
            'site_root_url' => self::CANONICAL_URL
        ];
    }

}