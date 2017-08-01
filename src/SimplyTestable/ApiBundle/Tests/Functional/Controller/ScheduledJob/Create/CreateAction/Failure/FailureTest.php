<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\Failure;

use SimplyTestable\ApiBundle\Tests\Functional\Controller\ScheduledJob\Create\CreateAction\CreateTest;
use Symfony\Component\HttpFoundation\Response;

abstract class FailureTest extends CreateTest {

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

        $this->getUserService()->setUser($this->getCurrentUser());

        $this->preCallController();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName(
            $this->container->get('request')
        );
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
            json_decode($this->response->headers->get('X-ScheduledJobCreate-Error'), true)
        );
    }

    protected function getRequestPostData() {
        return $this->requestPostData;
    }
}