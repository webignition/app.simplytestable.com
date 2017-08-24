<?php

namespace SimplyTestable\ApiBundle\Resque\Job\Job;

use SimplyTestable\ApiBundle\Command\Job\ResolveWebsiteCommand;
use SimplyTestable\ApiBundle\Resque\Job\CommandJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;

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
    public function getCommand()
    {
        /* @var ApplicationStateService $applicationStateService */
        $applicationStateService = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var JobService $jobService */
        $jobService = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var WebsiteResolutionService $websiteResolutionService */
        $websiteResolutionService = $this->getContainer()->get($this->args['serviceIds'][4]);

        /* @var JobPreparationService $jobPreparationService */
        $jobPreparationService = $this->getContainer()->get($this->args['serviceIds'][5]);

        $predefinedDomainsToIgnore = $this->args['parameters']['predefinedDomainsToIgnore'];

        return new ResolveWebsiteCommand(
            $applicationStateService,
            $resqueQueueService,
            $resqueJobFactory,
            $jobService,
            $websiteResolutionService,
            $jobPreparationService,
            $predefinedDomainsToIgnore
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
