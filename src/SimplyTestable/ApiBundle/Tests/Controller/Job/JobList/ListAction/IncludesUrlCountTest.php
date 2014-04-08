<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction;

class IncludesUrlCountTest extends ListContentTest {
    
    protected function createJobs() {
        $this->getJobService()->getById($this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL, null, 'single url'));
    }
    
    protected function getCanonicalUrls() {
        return array(
            self::DEFAULT_CANONICAL_URL
        );
    }

    protected function getExpectedJobListUrls() {
        return $this->getCanonicalUrls();
    }

    protected function getExpectedListLength() {
        return 1;
    }

    protected function getQueryParameters() {
        return array();
    }
    
    
    public function testListItemsIncludeUrlCount() {
        $this->assertEquals(1, $this->list->jobs[0]->url_count); 
    }

}


