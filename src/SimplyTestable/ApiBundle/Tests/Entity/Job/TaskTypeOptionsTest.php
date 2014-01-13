<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions;

class TaskTypeOptionsTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Options() {
        $canonicalUrl = 'http://example.com/';        
        $job = $this->getJobService()->getById($this->createJobAndGetId($canonicalUrl));        
        
        $taskType = $this->getTaskTypeService()->getByName('HTML Validation');
        
        $optionsValue = 'ɸ';
        
        $options = new TaskTypeOptions();
        $options->setJob($job);
        $options->setTaskType($taskType);
        $options->setOptions($optionsValue);
      
        $this->getEntityManager()->persist($options);        
        $this->getEntityManager()->flush();
        
        $optionsId = $options->getId();
        
        $this->getEntityManager()->clear();
        
        $this->assertEquals($optionsValue, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions')->find($optionsId)->getOptions());         
    }   
}
