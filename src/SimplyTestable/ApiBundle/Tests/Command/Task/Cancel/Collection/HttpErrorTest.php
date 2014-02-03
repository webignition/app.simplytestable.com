<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Task\Cancel\Collection;

class HttpErrorTest extends BaseTest {
    
    public function setUp() {
        parent::setUp();
        
        $job = $this->getJobService()->getById($this->createResolveAndPrepareDefaultJob());
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 ' . $this->getStatusCode()
        )));   
        
        $worker = $this->createWorker();
        
        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getQueuedState());
            $task->setWorker($worker);
            $this->getTaskService()->getEntityManager()->persist($task);              
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->assertReturnCode(0, array(
            'ids' => implode(',', $this->getTaskIds($job))
        ));
        
        foreach ($job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getCancelledState(), $task->getState());
        }        
    }
    
    public function test400() {}
    public function test404() {}
    public function test500() {}
    public function test503() {}
    
    
    /**
     * 
     * @return int
     */
    private function getStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }

}
