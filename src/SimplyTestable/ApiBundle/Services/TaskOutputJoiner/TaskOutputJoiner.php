<?php
namespace SimplyTestable\ApiBundle\Services\TaskOutputJoiner;

use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;

abstract class TaskOutputJoiner {    
    
    /**
     *
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;
    
    
    /**
     * @var $taskOutputs array
     */
    abstract public function process($taskOutputs);
    
    
    /**
     * 
     * @param array $taskOutputs
     * @return TaskOutput
     */
    public function join($taskOutputs) {
        $filteredTaskOutputs = array();
        foreach ($taskOutputs as $taskOutput) {
            if ($taskOutput instanceof TaskOutput) {
                $filteredTaskOutputs[] = $taskOutput;
            }
        }
        
        if (count($filteredTaskOutputs) === 1) {
            return $filteredTaskOutputs[0];
        }
        
        return $this->process($filteredTaskOutputs);
    }
  
    
    
    /**
     * 
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(\Symfony\Component\DependencyInjection\Container $container) {
        $this->container = $container;
    }
    
}