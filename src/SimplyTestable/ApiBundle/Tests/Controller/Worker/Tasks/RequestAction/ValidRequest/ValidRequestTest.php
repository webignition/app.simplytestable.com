<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\ValidRequest;

use SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\RequestTest;

abstract class ValidRequestTest extends RequestTest {

    const WORKER_HOSTNAME = 'worker.example.com';
    const WORKER_TOKEN = 'foo';

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $responseObject;

    public function setUp() {
        parent::setUp();

        $this->preCall();

        $this->clearRedis();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName();
        $this->responseObject = json_decode($this->response->getContent(), true);
    }

    protected function preCall() {
        $this->createWorker(self::WORKER_HOSTNAME, self::WORKER_TOKEN);
    }

    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }
}