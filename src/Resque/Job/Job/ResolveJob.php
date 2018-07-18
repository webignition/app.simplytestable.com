<?php

namespace App\Resque\Job\Job;

use App\Command\Job\ResolveWebsiteCommand;
use App\Resque\Job\CommandJob;

class ResolveJob extends CommandJob
{
    const QUEUE_NAME = 'job-resolve';

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
        return ResolveWebsiteCommand::NAME;
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
