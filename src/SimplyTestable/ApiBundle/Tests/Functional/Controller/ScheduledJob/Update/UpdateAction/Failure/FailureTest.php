<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\Failure;

use SimplyTestable\ApiBundle\Controller\ScheduledJob\UpdateController;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Update\UpdateAction\UpdateTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class FailureTest extends UpdateTest {

    private $requestPostData = [
        'job-configuration' => 'foo',
        'schedule' => '* * * * *',
        'is-recurring' =>  '1'
    ];

    /**
     * @var Response
     */
    private $response;

    protected function setUp() {
        parent::setUp();

        $this->setUser($this->getCurrentUser());

        $this->preCallController();

        $controller = new UpdateController();
        $controller->setContainer($this->container);

        $request = new Request([], $this->getRequestPostData());
        $this->response = $controller->updateAction($request, $this->getScheduledJobId());
    }

    protected function getScheduledJobId() {
        return 1;
    }

    protected function preCallController() {

    }

    abstract protected function getCurrentUser();
    abstract protected function getHeaderErrorCode();
    abstract protected function getHeaderErrorMessage();

    public function testResponseStatusCodeIs400() {
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function testResponseHeaderErrorCode() {
        $this->assertEquals(
            [
                'code' => $this->getHeaderErrorCode(),
                'message' => $this->getHeaderErrorMessage()
            ],
            json_decode($this->response->headers->get('X-ScheduledJobUpdate-Error'), true)
        );
    }

    protected function getRequestPostData() {
        return $this->requestPostData;
    }
}