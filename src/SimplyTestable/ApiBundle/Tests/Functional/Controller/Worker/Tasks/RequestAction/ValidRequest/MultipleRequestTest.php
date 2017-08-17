<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\ValidRequest;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class MultipleRequestTest extends ValidRequestTest
{
    public function preCall()
    {
        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create([
            WorkerFactory::KEY_HOSTNAME => self::WORKER_HOSTNAME,
            WorkerFactory::KEY_TOKEN => self::WORKER_TOKEN,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = new JobFactory($this->container);

        $jobFactory->createResolveAndPrepare([
            JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
        ], [
            'prepare' => [
                HttpFixtureFactory::createStandardRobotsTxtResponse(),
                HttpFixtureFactory::createStandardSitemapResponse('0.example.com/'),
            ],
        ]);
    }

    protected function preController()
    {
        $methodName = $this->getActionNameFromRouter();
        $this->getCurrentController()->$methodName();

        $this->assertFalse($this->getResqueQueueService()->isEmpty('task-assign-collection'));
        $this->assertEquals(
            1,
            $this->getResqueQueueService()->getResque()->getQueue('task-assign-collection')->getSize()
        );
    }

    protected function getRequestPostData()
    {
        return [
            'worker_hostname' => self::WORKER_HOSTNAME,
            'worker_token' => self::WORKER_TOKEN,
            'limit' => 1
        ];
    }

    public function testResqueTaskAssignCollectionQueueSizeRemainsAtOne()
    {
        $this->assertEquals(
            1,
            $this->getResqueQueueService()->getResque()->getQueue('task-assign-collection')->getSize()
        );
    }
}
