<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks;

use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;

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
        return new TaskNotificationCommand();
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
