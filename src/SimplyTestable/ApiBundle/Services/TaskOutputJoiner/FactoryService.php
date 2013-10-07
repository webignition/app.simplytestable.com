<?php
namespace SimplyTestable\ApiBundle\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Entity\Task\Task;

class FactoryService { 

    /**
     *
     * @var array
     */
    private $taskOutputJoiners = array();

    public function __construct(
        \Symfony\Component\DependencyInjection\Container $container,
        $taskOutputJoiners
    ) { 
        foreach ($taskOutputJoiners as $key => $properties) {
            $className = $properties['class'];
            $taskOutputJoiner = new $className;
            $taskOutputJoiner->setContainer($container);
            
            $this->taskOutputJoiners[$key] = $taskOutputJoiner;
        }
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return boolean
     */
    public function hasTaskOutputJoiner(Task $task) {
        $taskTypeKey = str_replace(' ', '-', strtolower($task->getType()->getName()));
        return isset($this->taskOutputJoiners[$taskTypeKey]);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     * @return \SimplyTestable\ApiBundle\Services\TaskOutputJoiner\TaskOutputJoiner
     */
    public function getTaskOutputJoiner(Task $task) {
        $taskTypeKey = str_replace(' ', '-', strtolower($task->getType()->getName()));
        return $this->taskOutputJoiners[$taskTypeKey];
    }
    
}