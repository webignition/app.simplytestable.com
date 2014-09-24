<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;
 
class SingleUrlJobTest extends CommandTest {
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    private $job;
    
    public function setUp() {
        parent::setUp();        
        $this->queueResolveHttpFixture();
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(
            self::DEFAULT_CANONICAL_URL,
            null,
            'single url',
            array('CSS Validation'),
            array(
                'CSS validation' => array(
                    'ignore-common-cdns' => 1,
                )
            )                
        ));
        
        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }
    
    public function testJobStateIsQueued() {        
        $this->assertEquals($this->getJobService()->getQueuedState(), $this->job->getState());
    }
    
    public function testTaskIsCreated() {
        $this->assertEquals(1, $this->job->getTasks()->count());
    }    
    
    public function testTaskIsQueued() {
        $this->assertEquals($this->getTaskService()->getQueuedState(), $this->job->getTasks()->first()->getState());
    }
    
    public function testDomainsToIgnoreAreSet() {
        $this->assertTrue(is_array($this->job->getTasks()->first()->getParameter('domains-to-ignore')));
    }
    
    public function testResqueQueueContainsTaskAssignmentSelectionJob() {            
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assignment-selection'
        ));         
    }
}
