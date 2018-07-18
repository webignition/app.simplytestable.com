<?php
namespace App\Services\TaskPostProcessor;

use App\Entity\Task\Type\Type as TaskType;

class Factory
{
    /**
     * @var TaskPostProcessorInterface[]
     */
    private $taskPostProcessors = [];

    /**
     * @param TaskPostProcessorInterface[] $taskPreprocessors
     */
    public function __construct($taskPreprocessors)
    {
        $this->taskPostProcessors = $taskPreprocessors;
    }

    /**
     * @param TaskType $taskType
     *
     * @return null|TaskPostProcessorInterface
     */
    public function getPostProcessor(TaskType $taskType)
    {
        foreach ($this->taskPostProcessors as $taskPreProcessor) {
            if ($taskPreProcessor->handles($taskType)) {
                return $taskPreProcessor;
            }
        }

        return null;
    }
}
