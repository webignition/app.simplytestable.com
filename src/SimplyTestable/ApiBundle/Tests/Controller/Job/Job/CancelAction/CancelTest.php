<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Tests\Controller\Job\Job\ActionTest;

abstract class CancelTest extends ActionTest {

    /**
     * @var Job
     */
    protected $job;


    /**
     * @var State
     */
    protected $jobStartingState;


    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;


    public function setUp() {
        parent::setUp();

        $this->job = $this->getJob();
        $this->jobStartingState = clone $this->job->getState();

        $this->preCall();

        if (is_null($this->getUserService()->getUser())) {
            $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        }

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName(
            $this->job->getWebsite()->getCanonicalUrl(),
            $this->job->getId()
        );
    }

    protected function preCall() {
    }

    /**
     * @return Job
     */
    abstract protected function getJob();


    /**
     * @return State
     */
    abstract protected function getExpectedJobStartingState();

    /**
     * @return State
     */
    abstract protected function getExpectedJobEndingState();


    /**
     * @return int
     */
    abstract protected function getExpectedResponseCode();

    public function testResponseStatusCode() {
        $this->assertEquals($this->getExpectedResponseCode(), $this->response->getStatusCode());
    }


    public function testJobStartingState() {
        $this->assertEquals($this->getExpectedJobStartingState()->getName(), $this->jobStartingState->getName());
    }


    public function testJobEndingState() {
        $this->assertEquals($this->getExpectedJobEndingState(), $this->job->getState());
    }


    protected function getRouteParameters() {
        return [
            'site_root_url' => $this->job->getWebsite()->getCanonicalUrl(),
            'test_id' => $this->job->getId()
        ];
    }
    
}


