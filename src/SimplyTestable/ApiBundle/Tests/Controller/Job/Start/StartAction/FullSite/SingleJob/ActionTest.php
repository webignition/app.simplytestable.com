<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction\FullSite\SingleJob;

use SimplyTestable\ApiBundle\Tests\Controller\Job\Start\StartAction\FullSite\ActionTest as BaseActionTest;

class ActionTest extends BaseActionTest {

    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private $response;


    /**
     * @var int
     */
    private $jobId;


    public function setUp() {
        parent::setUp();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(self::CANONICAL_URL);
        $this->jobId = $this->getJobIdFromUrl($this->response->getTargetUrl());
    }


    public function testResponseStatusCode() {
        $this->assertEquals(302, $this->response->getStatusCode());
    }


    public function testJobId() {
        $this->assertGreaterThan(0, $this->jobId);
    }

    public function testJobResolveResqueJobIsInQueue() {
        $this->assertTrue($this->getResqueQueueService()->contains(
            'job-resolve',
            ['id' => $this->jobId]
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