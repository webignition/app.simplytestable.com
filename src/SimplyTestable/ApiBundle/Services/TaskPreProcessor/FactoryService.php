<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

use SimplyTestable\ApiBundle\Entity\Task\Task;

class FactoryService { 

    /**
     *
     * @var array
     */
    private $taskPreProcessors = array();

    public function __construct(
        \Symfony\Component\DependencyInjection\Container $container,
        $taskPreProcessors
    ) { 
        foreach ($taskPreProcessors as $key => $properties) {
            $className = $properties['class'];
            $preprocessor = new $className;
            $preprocessor->setContainer($container);
            
            if (isset($properties['parameters'])) {
                $preprocessor->setParameters($properties['parameters']);
            }
            
            $this->taskPreProcessors[$key] = $preprocessor;
        }
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return boolean
     */
    public function hasPreprocessor(Task $task) {
        $taskTypeKey = str_replace(' ', '-', strtolower($task->getType()->getName()));
        return isset($this->taskPreProcessors[$taskTypeKey]);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return \SimplyTestable\ApiBundle\Services\TaskPreProcessor\TaskPreProcessor
     */
    public function getPreprocessor(Task $task) {
        $taskTypeKey = str_replace(' ', '-', strtolower($task->getType()->getName()));
        return $this->taskPreProcessors[$taskTypeKey];
    }
    
}