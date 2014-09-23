<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Assign\SelectedCommand;

class MarksEquivalentTasksAsInProgressTest extends CommandTest {
    
    private $job1;
    private $job2;
    
    private $assignReturnCode;

    public function setUp() {
        parent::setUp();
        
        $this->setJobTypeConstraintLimits();
        $this->createWorker();
        $this->createJobs();
        
        $this->queueTaskAssignCollectionResponseHttpFixture();
        
        $task = $this->job1->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();
        
        $this->assignReturnCode = $this->execute();
    }
    
    
    public function testAssignReturnCode() {
        $this->assertEquals(0, $this->assignReturnCode);
    }
    
    public function testJob1TaskState() {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->job1->getTasks()->get(0)->getState());
    }
    
    public function testJob2TaskState() {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->job2->getTasks()->get(1)->getState());      
    }    
    
    public function testJob1State() {
        $this->assertEquals($this->getJobService()->getInProgressState(), $this->job1->getState());
    }
    
    public function testJob2State() {
        $this->assertEquals($this->getJobService()->getInProgressState(), $this->job2->getState());     
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