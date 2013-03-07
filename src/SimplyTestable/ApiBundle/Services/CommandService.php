<?php
namespace SimplyTestable\ApiBundle\Services;

class CommandService { 
    
    /**
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;
    
    /**
     * 
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
        $this->container = $container;
    }
    
    /**
     * 
     * @param string $commandClass
     * @param array $inputArray
     * @param \CoreSphere\ConsoleBundle\Output\StringOutput $output
     * @return mixed
     */
    public function execute($commandClass, $inputArray = array(), \CoreSphere\ConsoleBundle\Output\StringOutput $output = null) {
        $command = $this->get($commandClass);       
        
        $input = new \Symfony\Component\Console\Input\ArrayInput($inputArray);
        
        if (is_null($output)) {
            $output = new \CoreSphere\ConsoleBundle\Output\StringOutput();
        }
    
        return $command->execute($input, $output);         
    }
    
    
    /**
     * 
     * @param string $commandClass
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand
     */
    public function get($commandClass) {
        $command = new $commandClass;
        $command->setContainer($this->container);
        
        return $command;
    }
    
}