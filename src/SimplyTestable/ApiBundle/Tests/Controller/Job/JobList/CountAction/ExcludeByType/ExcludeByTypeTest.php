<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeByType;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ContentTest;

class ExcludeByTypeTest extends ContentTest {      
    
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );
    
    private $excludedTypeName = 'crawl';

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    protected function createJobs() {
        foreach ($this->canonicalUrls as $canonicalUrl) {
            $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob($canonicalUrl, null, 'single url'));
        }
    }
    
    protected function applyPreListChanges() {
        $this->jobs[2]->setType($this->getJobTypeService()->getByName($this->excludedTypeName));
        $this->getJobService()->persistAndFlush($this->jobs[2]);   
    }    
    
    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }

    protected function getExpectedCountValue() {
        return 2;
    }

    protected function getQueryParameters() {
        return array(
            'exclude-types' => array(
                $this->excludedTypeName
            )       
        );
    }   
    
}


