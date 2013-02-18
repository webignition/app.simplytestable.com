<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\User;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';    
    const JOB_START_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobStartController';    
    const USER_CREATION_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserCreationController';
    const USER_PASSWORD_RESET_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserPasswordResetController';
    const USER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserController';

    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\ApiBundle\Controller\JobStartController
     */
    protected function getJobStartController($methodName, $postData = array()) {
        return $this->getController(self::JOB_START_CONTROLLER_NAME, $methodName, $postData);
    }    
    
    
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
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createJob($canonicalUrl, $userEmail = null, $type = null) {
        $postData = array();
        if (!is_null($userEmail)) {
            $postData['user'] = $userEmail;
        }
        
        if (!is_null($type)) {
            $postData['type'] = $type;
        }
        
        return $this->getJobStartController('startAction', $postData)->startAction($canonicalUrl);
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
     * @param int $job_id
     * @return \stdClass
     */
    protected function prepareJob($canonicalUrl, $job_id) {
        $this->runConsole('simplytestable:job:prepare', array(
            $job_id =>  true,
            $this->getCommonFixturesDataPath() . '/HttpResponses' => true
        ));        
    
        return json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
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
     * @return \SimplyTestable\ApiBundle\Services\TaskService
     */
    protected function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }      
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\TaskTypeService
     */
    protected function getTaskTypeService() {
        return $this->container->get('simplytestable.services.tasktypeservice');
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\ResqueQueueService
     */        
    protected function getResqueQueueService() {
        return $this->container->get('simplytestable.services.resqueQueueService');
    }     
    
    
    /**
     * 
     * @param string hostnanme
     * @return \SimplyTestable\ApiBundle\Entity\Worker
     */
    protected function createWorker($hostname = null) {
        if (is_null($hostname)) {
            $hostname = md5(time()) . '.worker.simplytestable.com';
        }        
        
        $worker = new Worker();
        $worker->setHostname($hostname);
        
        $this->getWorkerService()->persistAndFlush($worker);
        return $worker;
    }
}
