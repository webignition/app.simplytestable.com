<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\ListAction;

class ExcludeByTypeTest extends AbstractListTest {      
    
    public function testExcludeByType() {
        $excludeType = 'crawl';
        $jobTotal = 3;
        
        $this->createJob('http://one.example.com', null, 'full site');
        $excludedJob = $this->getJobService()->getById( $this->createJobAndGetId('http://two.example.com'));
        $this->createJobAndGetId('http://three.example.com', null, 'single url');
        
        $excludedJob->setType($this->getJobTypeService()->getByName($excludeType));
        $this->getJobService()->getEntityManager()->persist($excludedJob);
        $this->getJobService()->getEntityManager()->flush();         
        
        $list = json_decode($this->getJobController('listAction', array(), array(
            'exclude-types' => array(
                $excludeType
            ))
        )->listAction($jobTotal)->getContent());      
        
        $this->assertEquals($jobTotal - 1, count($list->jobs));
        
        foreach ($list->jobs as $job) {
            $this->assertNotEquals($excludeType, $job->type);
        }        
    }    
    
}


