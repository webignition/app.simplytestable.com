<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\TimePeriod;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobController';       
    const CRAWL_JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\CrawlJobController';    
    const JOB_START_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\JobStartController';    
    const USER_CREATION_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserCreationController';
    const USER_PASSWORD_RESET_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserPasswordResetController';
    const USER_EMAIL_CHANGE_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserEmailChangeController';
    const USER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserController';
    const USER_ACCOUNT_PLAN_SUBSCRIPTION_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController';
    const WORKER_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\WorkerController';
    const TASK_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\TaskController';
    const STRIPE_WEBHOOK_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\Stripe\WebHookController';
    const USER_STRIPE_EVENT_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\UserStripeEventController';
    
    const TEST_USER_EMAIL = 'user@example.com';
    const TEST_USER_PASSWORD = 'password';
    
    public function setUp() {
        parent::setUp();        
        $this->removeAllJobs();
        $this->removeAllWebsites();
        $this->removeAllTasks();
        $this->removeAllWorkers();        
        $this->removeTestTaskTypes();
        $this->removeTestTaskTypeClasses();
        $this->removeTestAccountPlanContraints();
        $this->removeTestAccountPlans();
        $this->removeTestStates();
        $this->removeTestJobTypes();
        $this->removeAllUserEmailChangeRequests();
        $this->removeAllStripeEvents();
        $this->rebuildDefaultDataState();
        $this->clearRedis();
    }
    
    protected function rebuildDefaultDataState() {
        $this->removeAllUserAccountPlans();
        $this->removeAllUsers();        
        self::loadDataFixtures();
    }
    
    
    public function createPublicUserAccountPlan() {
        $user = $this->getUserService()->getPublicUser();
        $plan = $this->getAccountPlanService()->find('public');
        
        $this->getUserAccountPlanService()->subscribe($user, $plan);          
    }
    
    protected function removeTestStates() {        
        $this->removeAllOfEntityPrefixedWith('SimplyTestable\ApiBundle\Entity\State', 'test');
    }    
    
    
    protected function removeTestAccountPlans() {        
        $this->removeAllOfEntityPrefixedWith('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan', 'test');
    }     
    
    
    protected function removeTestJobTypes() {        
        $this->removeAllOfEntityPrefixedWith('SimplyTestable\ApiBundle\Entity\Job\Type', 'test');
    }
    
    
    protected function removeTestTaskTypes() {        
        $this->removeAllOfEntityPrefixedWith('SimplyTestable\ApiBundle\Entity\Task\Type\Type', 'test');
    }    
    
    protected function removeTestTaskTypeClasses() {        
        $this->removeAllOfEntityPrefixedWith('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass', 'test');
    }     
    
    
    private function removeAllOfEntityPrefixedWith($entityName, $prefix) {
        $allEntities = $this->getEntityManager()->getRepository($entityName)->findAll();
        foreach ($allEntities as $entity) {
            if (substr($entity->getName(), 0, strlen($prefix)) == $prefix) {
                $this->getEntityManager()->remove($entity);
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
     * @return \SimplyTestable\ApiBundle\Controller\CrawlJobController
     */
    protected function getCrawlJobController($methodName, $postData = array()) {
        return $this->getController(self::CRAWL_JOB_CONTROLLER_NAME, $methodName, $postData);
    }    
    
    /**
     *
     * @param string $methodName
     * @param array $postData
     * @return \SimplyTestable\ApiBundle\Controller\JobController
     */
    protected function getJobController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::JOB_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
     * @return \SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController
     */
    protected function getUserAccountPlanSubscriptionController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_ACCOUNT_PLAN_SUBSCRIPTION_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
     * @return \SimplyTestable\ApiBundle\Controller\UserEmailChangeController
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
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\Stripe\WebHookController
     */
    protected function getStripeWebHookController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::STRIPE_WEBHOOK_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }     
    

    /**
     * 
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return \SimplyTestable\ApiBundle\Controller\UserStripeEventController
     */
    protected function getUserStripeEventController($methodName, $postData = array(), $queryData = array()) {
        return $this->getController(self::USER_STRIPE_EVENT_CONTROLLER_NAME, $methodName, $postData, $queryData);
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
     * @param array $testTypes
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function createJob($canonicalUrl, $userEmail = null, $type = null, $testTypes = null, $testTypeOptions = null, $parameters = null) {        
        $postData = array();
        if (!is_null($userEmail)) {
            $postData['user'] = $userEmail;
        }
        
        if (!is_null($type)) {
            $postData['type'] = $type;
        }
        
        if (is_array($testTypes)) {
            $postData['test-types'] = $testTypes;
        }
        
        if (is_array($testTypeOptions)) {
            $postData['test-type-options'] = $testTypeOptions;
        } 
        
        if (is_array($parameters)) {
            $postData['parameters'] = $parameters;
        }
        
        return $this->getJobStartController('startAction', $postData)->startAction($canonicalUrl);
    } 
    
    
    protected function createAndPrepareJob($canonicalUrl, $userEmail = null, $type = null, $testTypes = null, $testTypeOptions = null, $parameters = null) {
        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->prepareJob($canonicalUrl, $job_id);
        return $job_id;
    }
    
    protected function setJobTasksCompleted(Job $job) {
        foreach ($job->getTasks() as $task) {
            /* @var $task Task */            
            $task->setState($this->getTaskService()->getCompletedState());
            
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());
            $timePeriod->setEndDateTime(new \DateTime());
            $task->setTimePeriod($timePeriod);
            
            $this->getTaskService()->getEntityManager()->persist($task);
            $this->getTaskService()->getEntityManager()->flush($task);
        }        
    }
    
    
    /**
     * 
     * @param string $canonicalUrl
     * @param string $userEmail
     * @return int
     */
    protected function createJobAndGetId($canonicalUrl, $userEmail = null, $type = 'full site', $testTypes = null, $testTypeOptions = null, $parameters = null) {
        $response = $this->createJob($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        return $this->getJobIdFromUrl($response->getTargetUrl());
    } 
    
    
    /**
     * 
     * @param string $canonicalUrl
     * @param int $jobId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function cancelJob($canonicalUrl, $jobId) {
        return $this->getJobController('cancelAction')->cancelAction($canonicalUrl, $jobId);
    }
    
    
    protected function completeJob(Job $job) {
        $this->setJobTasksCompleted($job);
        $job->setState($this->getJobService()->getInProgressState());
        $this->getJobService()->complete($job);
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
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    protected function getTestUser() {        
        $user = $this->getUserService()->findUserByEmail(self::TEST_USER_EMAIL);
        if (is_null($user)) {
            $this->createAndActivateUser(self::TEST_USER_EMAIL, self::TEST_USER_PASSWORD);
        }
        
        return $this->getUserService()->findUserByEmail(self::TEST_USER_EMAIL);
    }
    
    
    /**
     * 
     * @param int $count
     * @return array
     */
    protected function createAndActivateUserCollection($count) {
        $users = array();
        for ($index = 0; $index < $count; $index++) {
            $users[] = $this->createAndActivateUser('user'.$index.'@example.com', 'password');
        }
        
        return $users;
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
     * @return \SimplyTestable\ApiBundle\Services\CrawlJobContainerService
     */
    protected function getCrawlJobContainerService() {
        return $this->container->get('simplytestable.services.crawljobcontainerservice');
    }     
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobTypeService
     */
    protected function getJobTypeService() {
        return $this->container->get('simplytestable.services.jobtypeservice');
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
     * @return \SimplyTestable\ApiBundle\Services\JobPreparationService
     */    
    protected function getJobPreparationService() {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\WebSiteService
     */
    protected function getWebSiteService() {
        return $this->container->get('simplytestable.services.websiteservice');
    } 
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobRejectionReasonService
     */
    protected function getJobRejectionReasonService() {
        return $this->container->get('simplytestable.services.jobrejectionreasonservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService
     */
    protected function getJobUserAccountPlanEnforcementService() {
        return $this->container->get('simplytestable.services.jobuseraccountplanenforcementservice');
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
     * @return \SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService
     */
    protected function getUserEmailChangeRequestService() {
        return $this->container->get('simplytestable.services.useremailchangerequestservice');
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
     * @return \SimplyTestable\ApiBundle\Services\TestStripeService
     */
    protected function getStripeService() {
        return $this->container->get('simplytestable.services.stripeservice');
    }  
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService
     */
    protected function getStripeEventService() {
        return $this->container->get('simplytestable.services.stripeeventservice');
    }    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\CommandService
     */
    protected function getCommandService() {
        return $this->container->get('simplytestable.services.commandservice');
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
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\Job\TaskTypeOptions');
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\CrawlJobContainer');
        
        $this->removeAllTasks();
        $this->removeAllJobRejectionReasons();
        $this->removeAllJobAmmendments();
        
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

    protected function removeAllWebsites() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\WebSite');
    }    
    
    protected function removeAllUserAccountPlans() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\UserAccountPlan');
    }
    
    
    protected function removeAllUserEmailChangeRequests() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest');      
    }
    
    protected function removeAllStripeEvents() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\Stripe\Event');      
    }    
    
    
    protected function removeAllJobRejectionReasons() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\Job\RejectionReason');      
    }    
    
    protected function removeAllJobAmmendments() {
        $this->removeAllForEntity('SimplyTestable\ApiBundle\Entity\Job\Ammendment');      
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
            $name = 'test-foo-plan';
        }
        
        $plan = new \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
        $plan->setName($name); 
        
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();         
        
        return $plan;
    }
    
    
    /**
     * 
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint
     */
    protected function createAccountPlanConstraint($name = null) {
        $plan = $this->createAccountPlan();
        
        $constraint = new \SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint();
        $constraint->setName('bar');
        $constraint->setPlan($plan);
        
        $this->getEntityManager()->persist($constraint);
        $this->getEntityManager()->flush();         
        
        return $constraint;
    }
    
    
    /**
     * 
     * @param string $baseUrl
     * @param int $count
     * @return string
     */
    protected function createUrlResultSet($baseUrl, $count, $offset = 0) {
        $urlResultSet = array();
        
        for ($index = $offset; $index < $count + $offset; $index++) {
            $urlResultSet[] = $baseUrl . $index . '/';
        }
        
        return $urlResultSet;
    }    
}
