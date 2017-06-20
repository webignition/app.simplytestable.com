<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\ValidRequest;

use SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\RequestTest;
use Symfony\Component\HttpFoundation\Response;

abstract class ValidRequestTest extends RequestTest
{
    const WORKER_HOSTNAME = 'worker.example.com';
    const WORKER_TOKEN = 'foo';

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $responseObject;

    public function setUp()
    {
        parent::setUp();

        $this->preCall();

        $this->clearRedis();

        $this->preController();

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController()->$methodName();
        $this->responseObject = json_decode($this->response->getContent(), true);
    }

    protected function preCall()
    {
        $this->createWorker(self::WORKER_HOSTNAME, self::WORKER_TOKEN);
    }

    protected function preController()
    {
    }

    public function testResponseStatusCode()
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }
}
