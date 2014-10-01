<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Tasks\RequestAction\InvalidRequest;

class WorkerHostnameTest extends InvalidRequestTest {

    protected function getExpectedResponseExceptionMessage() {
        return 'Invalid hostname "worker.example.com"';
    }
    
}