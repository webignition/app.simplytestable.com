<?php

namespace AppBundle\Resque\Job\ScheduledJob;

use AppBundle\Command\ScheduledJob\ExecuteCommand;
use AppBundle\Resque\Job\CommandJob;

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
    public function getCommandName()
    {
        return ExecuteCommand::NAME;
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
