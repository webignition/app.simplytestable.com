<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\Command;

class MarksEquivalentTasksAsInProgressTest extends CommandTest {
    
    private $job1;
    private $job2;
    
    private $job1AssignReturnCode;
    private $job2AssignReturnCode;
    
    public function setUp() {
        parent::setUp();
        
        $this->setJobTypeConstraintLimits();
        $this->createWorker();
        $this->createJobs();
        
        $this->queueTaskAssignResponseHttpFixture();        
        $this->job1AssignReturnCode = $this->execute(array(
            'id' => $this->job1->getTasks()->get(0)->getId()
        ));
        
        $this->queueTaskAssignResponseHttpFixture();        
        $this->job2AssignReturnCode = $this->execute(array(
            'id' => $this->job2->getTasks()->get(0)->getId()
        ));         
    }
    
    
    public function testJob1AssignReturnCode() {
        $this->assertEquals(0, $this->job1AssignReturnCode);
    }
    
    
    /**
     * @depends testJob1AssignReturnCode
     */
    public function testJob2AssignReturnCode() {
        $this->assertEquals(0, $this->job2AssignReturnCode);
    }
    
    
    /**
     * @depends testJob2AssignReturnCode
     */    
    public function testJob1TaskState() {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->job1->getTasks()->get(0)->getState());
    }
    
    
    /**
     * @depends testJob1TaskState
     */    
    public function testJob2TaskState() {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->job2->getTasks()->get(1)->getState());
    }
    
    
    private function setJobTypeConstraintLimits() {
        $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUserService()->getPublicUser());
        
        $fullSiteJobsPerSiteConstraint = $this->getJobUserAccountPlanEnforcementService()->getFullSiteJobLimitConstraint();
        $singleUrlJobsPerUrlConstraint = $this->getJobUserAccountPlanEnforcementService()->getSingleUrlJobLimitConstraint();
        
        $fullSiteJobsPerSiteConstraint->setLimit(2);
        $singleUrlJobsPerUrlConstraint->setLimit(2);
        
        $this->getJobService()->getManager()->persist($fullSiteJobsPerSiteConstraint);
        $this->getJobService()->getManager()->persist($singleUrlJobsPerUrlConstraint);
    }
    
    
    private function createJobs() {
        $this->job1 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site',
            array(
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
            )
        )));
        
        $this->job2 = $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'full site',
            array(
                'HTML validation',
                'CSS validation'
            ),
            array(
                'CSS validation' => array(
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
            )
        )));         
    }
    
}
