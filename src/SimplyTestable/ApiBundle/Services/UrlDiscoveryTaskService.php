<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use webignition\NormalisedUrl\NormalisedUrl;

class UrlDiscoveryTaskService
{
    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var Type
     */
    private $urlDiscoveryTaskType;

    /**
     * @param StateService $stateService
     * @param TaskTypeService $taskTypeService
     */
    public function __construct(
        StateService $stateService,
        TaskTypeService $taskTypeService
    ) {
        $this->stateService = $stateService;
        $this->urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();
    }

    /**
     * @param Job $crawlJob
     * @param string $parentUrl
     * @param string $taskUrl
     *
     * @return Task
     */
    public function create(Job $crawlJob, $parentUrl, $taskUrl)
    {
        $parentCanonicalUrl = new NormalisedUrl($parentUrl);

        $scope = [
            (string)$parentCanonicalUrl
        ];

        $hostParts = $parentCanonicalUrl->getHost()->getParts();
        if ($hostParts[0] === 'www') {
            $variant = clone $parentCanonicalUrl;
            $variant->setHost(implode('.', array_slice($parentCanonicalUrl->getHost()->getParts(), 1)));
            $scope[] = (string)$variant;
        } else {
            $variant = new NormalisedUrl($parentCanonicalUrl);
            $variant->setHost('www.' . (string)$variant->getHost());
            $scope[] = (string)$variant;
        }

        $taskParameters = [
            'scope' => $scope
        ];

        if ($crawlJob->hasParameters()) {
            $taskParameters = array_merge(
                $taskParameters,
                json_decode($crawlJob->getParameters(), true)
            );
        }

        $taskQueuedState = $this->stateService->get(TaskService::QUEUED_STATE);

        $task = new Task();
        $task->setJob($crawlJob);
        $task->setParameters(json_encode($taskParameters));
        $task->setState($taskQueuedState);
        $task->setType($this->urlDiscoveryTaskType);
        $task->setUrl($taskUrl);

        return $task;
    }
}
