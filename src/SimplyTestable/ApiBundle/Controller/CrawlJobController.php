<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Response;

class CrawlJobController extends JobController
{
    
    public function startAction($site_root_url, $test_id) {
        $this->siteRootUrl = $site_root_url;
        $this->testId = $test_id;
        
        $job = $this->getJob();        
        if ($job === false) {
            $response = new Response();
            $response->setStatusCode(403);
            return $response;  
        }
        
        if (!$job->getState()->equals($this->getJobService()->getFailedNoSitemapState())) {
            return $this->sendFailureResponse();          
        }
        
        if (!$this->getCrawlJobService()->hasForJob($job)) {
            $this->getCrawlJobService()->create($job);
        }
        
        return $this->sendResponse();
    }
       
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobService 
     */
    private function getCrawlJobService() {
        return $this->get('simplytestable.services.crawljobservice');
    }    
    
}
