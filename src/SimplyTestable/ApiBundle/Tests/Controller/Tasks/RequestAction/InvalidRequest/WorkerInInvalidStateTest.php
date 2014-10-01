<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction\InvalidRequest;

class WorkerInInvalidStateTest extends InvalidRequestTest {

    protected function getExpectedResponseExceptionMessage() {
        return 'Worker is not active';
    }


    protected  function preCall() {
        $worker = $this->createWorker('worker.example.com', 'foo')->setState($this->getStateService()->fetch('worker-offline'));
        $this->getWorkerService()->persistAndFlush($worker);
    }


    public function testRequestIsRetryable() {
        $this->assertEquals(1, $this->response->headers->get('X-Retryable'));
    }
    
}