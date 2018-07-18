<?php

namespace App\Resque\Job\ScheduledJob;

use App\Command\ScheduledJob\ExecuteCommand;
use App\Resque\Job\CommandJob;

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
