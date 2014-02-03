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
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200 OK'
        )));
        
        $this->job = $this->getJobService()->getById($this->createJobAndGetId(
            self::CANONICAL_URL,
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
