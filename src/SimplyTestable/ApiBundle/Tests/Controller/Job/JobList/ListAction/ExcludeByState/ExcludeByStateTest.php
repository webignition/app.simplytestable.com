<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeByState;

use SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ListContentTest;

class ExcludeByStateTest extends ListContentTest {
    
    private $canonicalUrls = array(
        'http://one.example.com/',
        'http://two.example.com/',
        'http://three.example.com/'
    );
    
    protected function createJobs() {
        foreach ($this->canonicalUrls as $canonicalUrl) {
            $this->jobs[] = $this->getJobService()->getById($this->createResolveAndPrepareJob($canonicalUrl, null, 'single url'));
        }
    }
    
    protected function applyPreListChanges() {
        $this->jobs[0]->setState($this->getJobService()->getCompletedState());
        $this->getJobService()->persistAndFlush($this->jobs[0]);
        
        $this->jobs[1]->setState($this->getJobService()->getRejectedState());
        $this->getJobService()->persistAndFlush($this->jobs[0]);         
    }    
    
    protected function getCanonicalUrls() {
        return $this->canonicalUrls;
    }

    protected function getExpectedJobListUrls() {
        return array(
            'http://one.example.com/'
        );
    }

    protected function getExpectedListLength() {
        return 1;
    }

    protected function getQueryParameters() {
        return array(
            'exclude-states' => array(
                'rejected',
                'queued'
            )            
        );
    }

}


