<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks;

use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\Worker\TaskNotificationService;

class NotifyJob extends CommandJob
{
    const QUEUE_NAME = 'tasks-notify';

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
        /* @var TaskNotificationService $workerTaskNotificationService */
        $workerTaskNotificationService = $this->getContainer()->get($this->args['serviceIds'][0]);

        return new TaskNotificationCommand(
            $workerTaskNotificationService
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return 'default';
    }
}
