<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\AssignmentSelectionCommand;

use SimplyTestable\ApiBundle\Tests\ConsoleCommandTestCase;

class UrlDiscoverySelectionTest extends ConsoleCommandTestCase {    
    
    /**
     * 
     * @return string
     */
    protected function getCommandName() {
        return 'simplytestable:task:assign:select';
    }
    
    
    /**
     * 
     * @return \Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand[]
     */
    protected function getAdditionalCommands() {        
        return array(
            new \SimplyTestable\ApiBundle\Command\Task\AssignmentSelectionCommand()
        );
    }
    
    public function testUrlDiscoveryTaskIsSelectedForAssigment() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultCrawlJob());
        $this->createWorker();
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $this->assertReturnCode(0);  
        $this->assertEquals('task-queued-for-assignment', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getState()->getName());
    }
    
}
