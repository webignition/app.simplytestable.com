<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Resque\Job\Worker\Tasks;

use SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class NotifyJobTest extends BaseSimplyTestableTestCase
{
    const QUEUE = 'tasks-notify';

    public function testRun()
    {
        $job = $this->createJob(1);

        $returnCode = $job->run([]);

        $this->assertEquals(true, $returnCode);
    }

    /**
     * @param int $jobId
     *
     * @return NotifyJob
     */
    private function createJob($jobId)
    {
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');

        $job = $resqueJobFactory->create(
            self::QUEUE,
            [
                'id' =>  $jobId,
            ]
        );

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}
