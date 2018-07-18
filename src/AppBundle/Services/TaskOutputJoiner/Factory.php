<?php
namespace AppBundle\Services\TaskOutputJoiner;

use AppBundle\Entity\Task\Type\Type as TaskType;

class Factory
{
    /**
     * @var TaskOutputJoinerInterface[]
     */
    private $taskOutputJoiners = [];

    /**
     * @param TaskOutputJoinerInterface[] $taskPreprocessors
     */
    public function __construct($taskPreprocessors)
    {
        $this->taskOutputJoiners = $taskPreprocessors;
    }

    /**
     * @param TaskType $taskType
     *
     * @return null|TaskOutputJoinerInterface
     */
    public function getPreprocessor(TaskType $taskType)
    {
        foreach ($this->taskOutputJoiners as $taskPreProcessor) {
            if ($taskPreProcessor->handles($taskType)) {
                return $taskPreProcessor;
            }
        }

        return null;
    }
}
