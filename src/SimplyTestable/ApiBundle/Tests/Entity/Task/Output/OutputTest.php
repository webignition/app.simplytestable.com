<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task\Output;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use webignition\InternetMediaType\InternetMediaType;

class TaskTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Output() {
        $outputValue = 'ɸ';
        
        $output = new Output();
        $output->setOutput($outputValue);
        
        $this->getEntityManager()->persist($output);        
        $this->getEntityManager()->flush();
      
        $outputId = $output->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($outputValue, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getOutput());                 
    }
    
    public function testUtf8ContentType() {
        $typeValue = 'ɸ';
        
        $contentType = new InternetMediaType();
        $contentType->setType($typeValue);
        $contentType->setSubtype($typeValue);               
        
        $output = new Output();
        $output->setOutput('');
        $output->setContentType($contentType);

        
        $this->getEntityManager()->persist($output);        
        $this->getEntityManager()->flush();
      
        $outputId = $output->getId();
   
        $this->getEntityManager()->clear();  
        $this->assertEquals($typeValue . '/' . $typeValue, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getContentType());                         
    }
    
    public function testUtf8Hash() {
        $hash = 'ɸ';
        
        $output = new Output();
        $output->setOutput('');
        $output->setHash($hash);
        
        $this->getEntityManager()->persist($output);        
        $this->getEntityManager()->flush();
      
        $outputId = $output->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($hash, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Output')->find($outputId)->getHash());                         
    }     
}
