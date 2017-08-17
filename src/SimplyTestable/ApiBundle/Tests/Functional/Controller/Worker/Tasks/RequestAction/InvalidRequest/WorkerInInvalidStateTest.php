<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\InvalidRequest;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class WorkerInInvalidStateTest extends InvalidRequestTest
{
    protected function getExpectedResponseExceptionMessage()
    {
        return 'Worker is not active';
    }

    protected function preCall()
    {
        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => 'worker.example.com',
            WorkerFactory::KEY_TOKEN => 'foo',
        ]);

        $worker->setState($this->getStateService()->fetch('worker-offline'));
        $this->getWorkerService()->persistAndFlush($worker);
    }

    public function testRequestIsRetryable()
    {
        $this->assertEquals(1, $this->response->headers->get('X-Retryable'));
    }
}
