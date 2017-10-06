<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Services\JobService;
use Symfony\Component\HttpFoundation\Response;

class StatusController extends ApiController
{
    /**
     * @return Response
     */
    public function indexAction()
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');

        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        /* @var Worker[] $workers */
        $workerRepository = $entityManager->getRepository(Worker::class);
        $workers = $workerRepository->findAll();

        $workerSummary = [];
        foreach ($workers as $worker) {
            $workerSummary[] = [
                'hostname' => $worker->getHostname(),
                'state' => $worker->getPublicSerializedState()
            ];
        }

        return $this->sendResponse([
            'state' => $applicationStateService->getState(),
            'workers' => $workerSummary,
            'version' => $this->getLatestGitHash(),
            'task_throughput_per_minute' => $taskService->getEntityRepository()->getThroughputSince(
                new \DateTime('-1 minute')
            ),
            'in_progress_job_count' => $jobRepository->getCountByState($jobInProgressState)
        ]);
    }

    /**
     * @return string
     */
    private function getLatestGitHash()
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
