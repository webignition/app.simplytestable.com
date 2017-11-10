<?php

namespace Tests\ApiBundle\Functional\Resque\Job;

use SimplyTestable\ApiBundle\Resque\Job\Job;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractJobTest extends AbstractBaseTestCase
{
    /**
     * @param array $args
     * @param string $queue
     *
     * @return Job
     */
    public function createJob($args, $queue)
    {
        $resqueJobFactory = $this->container->get(ResqueJobFactory::class);

        $job = $resqueJobFactory->create($queue, $args);

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}
