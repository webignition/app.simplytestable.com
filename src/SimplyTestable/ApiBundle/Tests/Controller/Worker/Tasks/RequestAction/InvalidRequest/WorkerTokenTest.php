<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Worker\Tasks\RequestAction\InvalidRequest;

class WorkerTokenTest extends InvalidRequestTest {

    protected function getExpectedResponseExceptionMessage() {
        return 'Invalid token';
    }

    protected  function preCall() {
        $this->createWorker('worker.example.com');
    }
    
}