<?php

namespace App\Resque\Job\Job;

use App\Command\Job\PrepareCommand;
use App\Resque\Job\CommandJob;

class PrepareJob extends CommandJob
{
    const QUEUE_NAME = 'job-prepare';

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
        return PrepareCommand::NAME;
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
