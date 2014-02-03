<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\ResolveWebsiteCommand;
 
class FullSiteJobTest extends CommandTest {
    
    /**
     * domains to ignore are set
     * resque job is queued?
     */
    
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
            'full site',
            array('CSS Validation')
        ));
        
        $this->assertReturnCode(0, array(
            'id' => $this->job->getId()
        ));
    }
    
    public function testJobStateIsResolved() {        
        $this->assertEquals($this->getJobService()->getResolvedState(), $this->job->getState());
    }
    
    public function testNoTasksAreCreated() {
        $this->assertEquals(0, $this->job->getTasks()->count());
    }    
  
    public function testResqueQueueContainsJobPreparationJob() {            
        $this->assertTrue($this->getResqueQueueService()->contains(
            'SimplyTestable\ApiBundle\Resque\Job\JobPrepareJob',
            'job-prepare',
            array(
                'id' => $this->job->getId()
            )  
        ));         
    }
}
