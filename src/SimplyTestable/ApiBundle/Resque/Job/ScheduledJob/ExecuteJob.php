<?php

namespace SimplyTestable\ApiBundle\Resque\Job\ScheduledJob;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Command\ScheduledJob\ExecuteCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\StartService as JobStartService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

class ExecuteJob extends CommandJob
{
    const QUEUE_NAME = 'scheduledjob-execute';

    /**
     * {@inheritdoc}
     */
    protected function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        /* @var ApplicationStateService $applicationStateService */
        $applicationStateService = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var EntityManagerInterface $entityManager */
        $entityManager = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var JobStartService $jobStartService */
        $jobStartService = $this->getContainer()->get($this->args['serviceIds'][4]);

        /* @var JobService $jobService */
        $jobService = $this->getContainer()->get($this->args['serviceIds'][5]);

        return new ExecuteCommand(
            $applicationStateService,
            $resqueQueueService,
            $resqueJobFactory,
            $entityManager,
            $jobStartService,
            $jobService
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [
            'id' => $this->args['id']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['id'];
    }
}
