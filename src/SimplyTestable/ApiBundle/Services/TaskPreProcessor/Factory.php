<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class Factory
{
    /**
     * @var TaskPreprocessorInterface[]
     */
    private $taskPreProcessors = [];

    /**
     * @param TaskPreprocessorInterface[] $taskPreprocessors
     */
    public function __construct($taskPreprocessors)
    {
        $this->taskPreProcessors = $taskPreprocessors;
    }

    /**
     * @param TaskType $taskType
     *
     * @return null|TaskPreprocessorInterface
     */
    public function getPreprocessor(TaskType $taskType)
    {
        foreach ($this->taskPreProcessors as $taskPreProcessor) {
            if ($taskPreProcessor->handles($taskType)) {
                return $taskPreProcessor;
            }
        }

        return null;
    }
}
