<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';    
    const USER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserController';    
    
    
    /**
     *
     * @param string $methodName
     * @return \SimplyTestable\ApiBundle\Controller\JobController
     */
    protected function getJobController($methodName) {
        return $this->getController(self::JOB_CONTROLLER_NAME, $methodName);
    }
    
    /**
     * 
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\UserController
     */
    protected function getUserController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }    
    
    
    /**
     * 
     * @param string $controllerName
     * @param string $methodName
     * @return Symfony\Bundle\FrameworkBundle\Controller\Controller
     */
    private function getController($controllerName, $methodName, array $postData = array(), array $queryData = array()) {        
        return $this->createController($controllerName, $methodName, $postData, $queryData);
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
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createUser($email) {        
        return $this->getUserController('createAction', array(
            'email' => $email           
        ))->createAction();  
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
     * @return \SimplyTestable\ApiBundle\Services\UserService
     */
    protected function getUserService() {
        return $this->container->get('simplytestable.services.userservice');
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
