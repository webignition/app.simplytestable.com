<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare\Cookies;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;

abstract class ServiceTest extends BaseSimplyTestableTestCase {    
    
    
    /**
     *
     * @var array
     */
    protected $cookies = array(
        array(
            'domain' => '.example.com',
            'name' => 'foo',
            'value' => 'bar'
        )               
    );    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job
     */
    protected $job;
    
    public function setUp() {
        parent::setUp();
        
        $this->queueResolveHttpFixture();
        
        $this->job = $this->getJobService()->getById($this->createAndResolveJob(self::DEFAULT_CANONICAL_URL, $this->getTestUser()->getEmail(), null, array('html validation'), null, array(
            'cookies' => $this->cookies
        )));
    }    

}
