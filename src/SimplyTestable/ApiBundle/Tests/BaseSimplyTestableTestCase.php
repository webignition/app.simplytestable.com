<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';    
    
    /**
     *
     * @var SimplyTestable\ApiBundle\Controller\JobController
     */
    private $jobController = null;
    
    
    /**
     *
     * @param string $methodName
     * @return \SimplyTestable\ApiBundle\Controller\JobController
     */
    protected function getJobController($methodName) {
        if (is_null($this->jobController)) {
            $this->jobController = $this->createController(self::JOB_CONTROLLER_NAME, $methodName);
        }        
        
        return $this->jobController;
    }
    
    
    /**
     * 
     * @param string $url
     * @return int
     */
    protected function getJobIdFromUrl($url) {
        $urlParts = explode('/', $url);
        
        return (int)$urlParts[count($urlParts) - 2];        
    }  
    
    
    /**
     *
     * @param string $canonicalUrl
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createJob($canonicalUrl) {
        return $this->getJobController('startAction')->startAction($canonicalUrl);
    } 
        

    /**
     *
     * @param string $canonicalUrl
     * @param int $id
     * @return Job
     */
    protected function fetchJob($canonicalUrl, $id) {        
        return $this->getJobController('statusAction')->statusAction($canonicalUrl, $id);    
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    protected function getJobService() {
        return $this->container->get('simplytestable.services.jobservice');
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerService
     */
    protected function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }     
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Worker
     */
    protected function createWorker() {
        $hostname = md5(time()) . '.worker.simplytestable.com';
        
        $worker = new Worker();
        $worker->setHostname($hostname);
        
        $this->getWorkerService()->persistAndFlush($worker);
        return $worker;
    }

}
