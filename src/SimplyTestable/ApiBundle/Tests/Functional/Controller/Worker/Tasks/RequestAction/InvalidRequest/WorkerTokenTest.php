<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\InvalidRequest;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class WorkerTokenTest extends InvalidRequestTest
{
    protected function getExpectedResponseExceptionMessage()
    {
        return 'Invalid token';
    }

    protected function preCall()
    {
        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create('worker.example.com');
    }
}
