<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;

class FactoryService { 

    /**
     *
     * @var array
     */
    private $taskPreProcessors = array();

    public function __construct($taskPreProcessors) {
        foreach ($taskPreProcessors as $key => $properties) {
            $className = $properties['class'];
            $preprocessor = new $className;
            $this->taskPreProcessors[$key] = $preprocessor;
        }
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Type\Type $taskType
     * @return boolean
     */
    public function hasPreprocessor(TaskType $taskType) {
        $taskTypeKey = str_replace(' ', '-', strtolower($taskType->getName()));
        return isset($this->taskPreProcessors[$taskTypeKey]);
    }
    
}