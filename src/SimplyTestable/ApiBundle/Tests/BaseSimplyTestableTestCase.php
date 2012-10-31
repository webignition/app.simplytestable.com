<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\User;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';    
    const USER_CREATION_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserCreationController';
    const USER_PASSWORD_RESET_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserPasswordResetController';
    const USER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserController';
    
    
    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\ApiBundle\Controller\JobController
     */
    protected function getJobController($methodName, $postData = array()) {
        return $this->getController(self::JOB_CONTROLLER_NAME, $methodName, $postData);
    }
    
    /**
     * 
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\UserCreationController
     */
    protected function getUserCreationController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_CREATION_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }    
    
    
    /**
     * 
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\UserPasswordResetController
     */
    protected function getUserPasswordResetController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_PASSWORD_RESET_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
     * @param string $userEmail
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createJob($canonicalUrl, $userEmail = null) {
        $postData = (is_null($userEmail)) ? array() : array(
            'user' => $userEmail
        );
        
        return $this->getJobController('startAction', $postData)->startAction($canonicalUrl);
    } 
    
    
    /**
     * 
     * @param string $canonicalUrl
     * @param string $userEmail
     * @return int
     */
    protected function createJobAndGetId($canonicalUrl, $userEmail = null) {
        $response = $this->createJob($canonicalUrl, $userEmail);
        return $this->getJobIdFromUrl($response->getTargetUrl());
    }
    
    
    /**
     * 
     * @param string $canonicalUrl
     * @param int $jobId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function getJobStatus($canonicalUrl, $jobId, $userEmail = null) {        
        $postData = (is_null($userEmail)) ? array() : array(
            'user' => $userEmail
        );        
        
        return $this->getJobController('statusAction', $postData)->statusAction($canonicalUrl, $jobId);     
    }
    
    
    /**
     * 
     * @param string $email
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createUser($email, $password) {        
        return $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ))->createAction();  
    }
    
    
    /**
     * 
     * @param string $email
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    protected function createAndFindUser($email, $password) {        
        $this->createUser($email, $password);
        
        return $this->getUserService()->findUserByEmail($email);      
    }     
    

    /**
     * 
     * @param string $email
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    protected function createAndActivateUser($email, $password) {        
        $this->createUser($email, $password);
        
        $user = $this->getUserService()->findUserByEmail($email);            
        $this->getUserCreationController('activateAction')->activateAction($user->getConfirmationToken());
        
        return $user;          
    } 
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return string
     */
    protected function getPasswordResetToken(User $user) {//        
        $this->getUserPasswordResetController('getTokenAction')->getTokenAction($user->getEmail());      
        return $this->getUserService()->getConfirmationToken($user);        
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
