<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\InvalidRequest;

use  SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\RequestTest;

abstract class InvalidRequestTest extends RequestTest {

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    public function setUp() {
        parent::setUp();

        $this->preCall();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName();
    }

    protected function preCall() {}

    abstract protected function getExpectedResponseExceptionMessage();

    public function testResponseStatusCode() {
        $this->assertEquals(400, $this->response->getStatusCode());
    }

    public function testResponseExceptionMessage() {
        $this->assertEquals($this->getExpectedResponseExceptionMessage(), $this->response->headers->get('X-Message'));
    }

}