<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\User;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';    
    const JOB_START_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobStartController';    
    const USER_CREATION_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserCreationController';
    const USER_PASSWORD_RESET_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserPasswordResetController';
    const USER_EMAIL_CHANGE_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserEmailChangeController';
    const USER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserController';
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\WorkerController';
    const TASK_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\TaskController';
    
    public function setUp() {
        parent::setUp();
        $this->removeAllJobs();
        $this->removeAllTasks();
        $this->removeAllWorkers();
        $this->removeAllUserAccountPlans();
        $this->removeTestAccountPlanContraints();
        $this->removeTestAccountPlans();
        $this->removeAllUserEmailChangeRequests();
        $this->rebuildDefaultUserState();
        $this->clearRedis();
    }   

    
    
    protected function rebuildDefaultUserState() {
        $this->removeAllUsers();        
        $this->createPublicUserIfMissing();
        $this->createAdminUserIfMissing();
        $this->createPublicUserAccountPlan();        
    }
    
    
    public function createPublicUserAccountPlan() {
        $user = $this->getUserService()->getPublicUser();
        $plan = $this->getAccountPlanService()->find('public');
        
        $this->getUserAccountPlanService()->create($user, $plan);          
    }
    
    
    protected function removeTestAccountPlans() {        
        $testPlanNames = array('foo-plan', 'bar-plan');
        
        foreach ($testPlanNames as $constraintName) {
            $plans = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan')->findByName($constraintName);
            if (is_array($plans) && count($plans) > 0) {
                $this->getEntityManager()->remove($plans[0]);
                $this->getEntityManager()->flush();
            }
        }
    }    
    
    
    protected function removeTestAccountPlanContraints() {        
        $testPlanConstraintNames = array('foo', 'bar');
        
        foreach ($testPlanConstraintNames as $constraintName) {
            $constraints = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint')->findByName($constraintName);
            if (is_array($constraints) && count($constraints) > 0) {
                $this->getEntityManager()->remove($constraints[0]);
                $this->getEntityManager()->flush();
            }
        }
    }
    
    
    /**
     * 
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager() {
        return $this->container->get('doctrine')->getManager();
    }
    

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
    protected function getUserEmailChangeController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_EMAIL_CHANGE_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\WorkerController
     */
    protected function getWorkerController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::WORKER_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }     
    
    
    /**
     * 
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\TaskController
     */
    protected function getTaskController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::TASK_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
    
    
    protected function createAndPrepareJob($canonicalUrl, $userEmail = null) {
        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail);
        $this->prepareJob($canonicalUrl, $job_id);
        return $job_id;
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
            $job_id =>  true
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
     * @param string $canonicalUrl
     * @param int $job_id
     * @return array
     */
    protected function getTaskIds($canonicalUrl, $job_id) {
        return json_decode($this->getJobController('taskIdsAction')->taskIdsAction($canonicalUrl, $job_id)->getContent());        
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
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */
    protected function getAccountPlanService() {
        return $this->container->get('simplytestable.services.accountplanservice');
    }    
    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    protected function getUserAccountPlanService() {
        return $this->container->get('simplytestable.services.useraccountplanservice');
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
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */        
    protected function getStateService() {
        return $this->container->get('simplytestable.services.stateservice');
    }  
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WorkerActivationRequestService
     */        
    protected function getWorkerActivationRequestService() {
        return $this->container->get('simplytestable.services.workeractivationrequestservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\HttpClientService
     */
    protected function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
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
        
        $worker = $this->getWorkerService()->get($hostname);
        $worker->setState($this->getStateService()->fetch('worker-active'));
        
        $this->getWorkerService()->persistAndFlush($worker);
        return $worker;
    }
    
    
    protected function removeAllWorkers() {
        $this->removeAllWorkerActivationRequests();
        $this->removeAllTasks();
        
        $workers = $this->getWorkerService()->getEntityRepository()->findAll();
        foreach ($workers as $worker) {
            $this->getWorkerService()->getEntityManager()->remove($worker);
        }
        
        $this->getWorkerService()->getEntityManager()->flush();
    }
    
    protected function removeAllJobs() {
        $this->removeAllTasks();
        
        $jobs = $this->getJobService()->getEntityRepository()->findAll();
        foreach ($jobs as $job) {
            $this->getJobService()->getEntityManager()->remove($job);
        }
        
        $this->getJobService()->getEntityManager()->flush();
    }
    
    protected function removeAllUsers() {
        $this->removeAllJobs();
        
        $users = $this->getUserService()->findUsers();
        foreach ($users as $user) {
            $this->getUserService()->deleteUser($user);
        }
    }
    
    
    protected function removeAllUserAccountPlans() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\UserAccountPlan');
    }
    
    
    protected function removeAllUserEmailChangeRequests() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest');      
    }
    
    
    private function removeAllForEntity($entityName) {
        $entities = $this->getEntityManager()->getRepository($entityName)->findAll();
        if (is_array($entities) && count($entities) > 0) {
            foreach ($entities as $entity) {
                $this->getEntityManager()->remove($entity);
                $this->getEntityManager()->flush();                
            }
        }        
    }    
    
    
    protected function createPublicUserIfMissing() {        
        if (!$this->getUserService()->exists('public@simplytestable.com')) {
            $user = new User();
            $user->setEmail('public@simplytestable.com');
            $user->setPlainPassword('public');
            $user->setUsername('public');        

            $userManager = $this->container->get('fos_user.user_manager');        
            $userManager->updateUser($user);

            $manipulator = $this->container->get('fos_user.util.user_manipulator');
            $manipulator->activate($user->getUsername());      
        }
    }
    
    
    protected function createAdminUserIfMissing() {        
        if (!$this->getUserService()->exists('admin@simplytestable.com')) {
            $user = new User();
            $user->setEmail($this->container->getParameter('admin_user_email'));
            $user->setPlainPassword($this->container->getParameter('admin_user_password'));
            $user->setUsername('admin'); 
            $user->addRole('role_admin');

            $userManager = $this->container->get('fos_user.user_manager');        
            $userManager->updateUser($user);

            $manipulator = $this->container->get('fos_user.util.user_manipulator');
            $manipulator->activate($user->getUsername());      
        }
    }    
      
    
    protected function removeAllTasks() {
        $tasks = $this->getTaskService()->getEntityRepository()->findAll();
        foreach ($tasks as $task) {
            $this->getTaskService()->getEntityManager()->remove($task);
        }
        
        $this->getTaskService()->getEntityManager()->flush();        
    }
    
    protected function removeAllWorkerActivationRequests() {
        $requests = $this->getWorkerActivationRequestService()->getEntityRepository()->findAll();
        foreach ($requests as $request) {
            $this->getWorkerActivationRequestService()->getEntityManager()->remove($request);
        }
        
        $this->getWorkerActivationRequestService()->getEntityManager()->flush();        
    }
    
    
    /**
     * Create and return a collection of workers
     * 
     * @param int $requestedWorkerCount
     * @return array
     */
    protected function createWorkers($requestedWorkerCount) {
        $workers = array();
        
        for ($workerIndex = 0; $workerIndex < $requestedWorkerCount; $workerIndex++) {
            $workers[] = $this->createWorker('worker'.$workerIndex.'.worker.simplytestable.com');
        } 
        
        return $workers;
    }
    
    
    
    /**
     * 
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan
     */
    protected function createAccountPlan($name = null) {
        if (is_null($name)) {
            $name = 'foo-plan';
        }
        
        $plan = new \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
        $plan->setName($name); 
        
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();         
        
        return $plan;
    }
}
