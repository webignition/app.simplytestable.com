<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

abstract class TaskPreProcessor {    
    
    /**
     *
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;
    
    /**
     * @param \SimplyTestable\ApiBundle\Entity\Task\Task $task
     */
    abstract public function process(\SimplyTestable\ApiBundle\Entity\Task\Task $task);
  
    
    
    /**
     * 
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function setContainer(\Symfony\Component\DependencyInjection\Container $container) {
        $this->container = $container;
    }
    
}