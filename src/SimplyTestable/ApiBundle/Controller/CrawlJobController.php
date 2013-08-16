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
        
        if (!$this->getCrawlJobContainerService()->hasForJob($job)) {
            $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
            $this->getCrawlJobContainerService()->prepare($crawlJobContainer);
            
            if ($this->getResqueQueueService()->isEmpty('task-assignment-selection')) {
                $this->getResqueQueueService()->add(
                    'SimplyTestable\ApiBundle\Resque\Job\TaskAssignmentSelectionJob',
                    'task-assignment-selection'
                );             
            }               
        }        
        
        return $this->sendResponse();
    }
    
    
    /**
     *
     * @return SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    private function getResqueQueueService() {
        return $this->get('simplytestable.services.resqueQueueService');
    }        
   
}
