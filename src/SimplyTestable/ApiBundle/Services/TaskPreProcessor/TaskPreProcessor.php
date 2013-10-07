<?php
namespace SimplyTestable\ApiBundle\Services\TaskPreProcessor;

abstract class TaskPreProcessor {    
    
    /**
     *
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;
    
    /**
     *
     * @var array
     */
    private $parameters;
    
    
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
    
    
    /**
     * 
     * @param array $parameters
     */
    public function setParameters($parameters) {
        $this->parameters = $parameters;
    }
    
    
    protected function getParameter($name) {        
        if (!is_array($this->parameters)) {
            return null;
        }
        
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }
    
}