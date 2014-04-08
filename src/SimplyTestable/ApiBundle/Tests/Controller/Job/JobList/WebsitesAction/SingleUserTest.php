<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction;

abstract class SingleUserTest extends WebsitesTest {
    
    protected $list;
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Entity\Job\Job[]
     */
    protected $jobs = array();
    
    public function setUp() {
        parent::setUp();
        
        $this->list = json_decode($this->getJobListController(
            'WebsitesAction',
            $this->getPostParameters(),
            $this->getQueryParameters())->WebsitesAction()->getContent()
       );
                 
    }
    
    abstract protected function getQueryParameters();    
    
    protected function getPostParameters() {
        return array();
    }
    
    protected function getLimit() {
        return max(1, count($this->getCanonicalUrls()));
    }
}