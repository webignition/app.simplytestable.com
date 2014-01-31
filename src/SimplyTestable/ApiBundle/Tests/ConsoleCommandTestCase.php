<?php

namespace SimplyTestable\ApiBundle\Tests;

use Symfony\Component\Console\Tester\CommandTester;

abstract class ConsoleCommandTestCase extends BaseSimplyTestableTestCase {
    
    private $command;
    protected $commandTester;
    
    public function setUp() {
        parent::setUp();
        
        foreach ($this->getCommands() as $command) {
            $this->application->add($command);
        }

        $this->command = $this->application->find($this->getCommandName());
        $this->commandTester = new CommandTester($this->command);                
    }
 
    
    /**
     * @return string
     */
    abstract protected function getCommandName();
    
    
    /**
     * 
     * @return int
     */
    protected function execute($arguments = array()) {
        $arguments['command'] = $this->command->getName();
        
        return $this->commandTester->execute($arguments);
    }
    
    
    /**
     * 
     * @param int $returnCode
     * @param array $arguments
     */
    protected function assertReturnCode($returnCode, $arguments = array()) {
        $this->assertEquals($returnCode, $this->execute($arguments));
    }
    
}
