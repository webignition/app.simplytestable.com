<?php
namespace SimplyTestable\ApiBundle\Command\Job;

use SimplyTestable\ApiBundle\Command\BaseCommand;

use SimplyTestable\ApiBundle\Services\JobTypeService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResolveWebsiteCommand extends BaseCommand
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE = 1;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

    protected function configure()
    {
        $this
            ->setName('simplytestable:job:resolve')
            ->setDescription('Resolve a job\'s canonical url to be sure where we are starting off')
            ->addArgument('id', InputArgument::REQUIRED, 'id of job to process')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $applicationStateService = $this->getContainer()->get('simplytestable.services.applicationstateservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->getContainer()->get('simplytestable.services.resque.jobfactory');

        $job = $this->getJobService()->getById((int)$input->getArgument('id'));

        try {
            $this->getJobWebsiteResolutionService()->resolve($job);
        } catch (\SimplyTestable\ApiBundle\Exception\Services\Job\WebsiteResolutionException $websiteResolutionException) {
            if ($websiteResolutionException->isJobInWrongStateException()) {
                return self::RETURN_CODE_CANNOT_RESOLVE_IN_WRONG_STATE;
            }
        }

        if ($this->getJobService()->isFinished($job)) {
            return self::RETURN_CODE_OK;
        }

        if (JobTypeService::SINGLE_URL_NAME == $job->getType()->getName()) {
            foreach ($job->getRequestedTaskTypes() as $taskType) {
                /* @var $taskType TaskType */
                $taskTypeParameterDomainsToIgnoreKey = strtolower(str_replace(' ', '-', $taskType->getName())) . '-domains-to-ignore';

                if ($this->getContainer()->hasParameter($taskTypeParameterDomainsToIgnoreKey)) {
                    $this->getJobPreparationService()->setPredefinedDomainsToIgnore($taskType, $this->getContainer()->getParameter($taskTypeParameterDomainsToIgnoreKey));
                }
            }

            $this->getJobPreparationService()->prepare($job);

            $taskIds = [];
            foreach ($job->getTasks() as $task) {
                $taskIds[] = $task->getId();
            }

            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'task-assign-collection',
                    ['ids' => implode(',', $taskIds)]
                )
            );
        } else {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'job-prepare',
                    ['id' => $job->getId()]
                )
            );
        }

        return 0;
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->getContainer()->get('simplytestable.services.jobservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService
     */
    private function getJobWebsiteResolutionService() {
        return $this->getContainer()->get('simplytestable.services.jobwebsiteresolutionservice');
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */
    private function getJobPreparationService() {
        return $this->getContainer()->get('simplytestable.services.jobpreparationservice');
    }
}