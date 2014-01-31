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
        $this->createWorkers(1);
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__). '/HttpResponses'));
        
        $canonicalUrl = 'http://example.com/';
        $job = $this->getJobService()->getById($this->createAndPrepareJob($canonicalUrl));
        
        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
        
        $this->assertReturnCode(0);  
        $this->assertEquals('task-queued-for-assignment', $crawlJobContainer->getCrawlJob()->getTasks()->first()->getState()->getName());
    }
    
}
