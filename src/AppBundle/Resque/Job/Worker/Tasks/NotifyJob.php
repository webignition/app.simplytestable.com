<?php

namespace AppBundle\Resque\Job\Worker\Tasks;

use AppBundle\Command\Worker\TaskNotificationCommand;
use AppBundle\Resque\Job\CommandJob;

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
    public function getCommandName()
    {
        return TaskNotificationCommand::NAME;
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
