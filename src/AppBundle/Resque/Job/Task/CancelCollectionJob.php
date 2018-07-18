<?php

namespace AppBundle\Resque\Job\Task;

use AppBundle\Command\Task\Cancel\CollectionCommand;
use AppBundle\Resque\Job\CommandJob;

class CancelCollectionJob extends CommandJob
{
    const QUEUE_NAME = 'task-cancel-collection';

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
        return CollectionCommand::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [
            'ids' => $this->args['ids']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['ids'];
    }
}