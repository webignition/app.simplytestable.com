<?php

namespace SimplyTestable\ApiBundle\Tests;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\TimePeriod;

abstract class BaseSimplyTestableTestCase extends BaseTestCase {
    
    const JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\Job\JobController';       
    const CRAWL_JOB_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\CrawlJobController';    
    const JOB_START_CONTROLLER_NAME = 'SimplyTestable\ApiBundle\Controller\Job\JobStartController';    
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
    
    const DEFAULT_CANONICAL_URL = 'http://example.com/';
    const DEFAULT_REQUIRED_SITEMAP_XML_URL_COUNT = 3;
    
    private $requiredSitemapXmlUrlCount = null;
    
    public function setUp() {
        parent::setUp();        
        $this->removeAllJobs();
        $this->removeAllWebsites();
        $this->removeAllTasks();
        $this->removeAllWorkers();        
        $this->removeTestTaskTypes();
        $this->removeTestTaskTypeClasses();
        $this->removeTestAccountPlanContraints();
        $this->removeAllUserAccountPlans();
        $this->removeTestAccountPlans();
        $this->removeTestStates();
        $this->removeTestJobTypes();
        $this->removeAllUserEmailChangeRequests();
        $this->removeAllStripeEvents();
        $this->rebuildDefaultDataState();
        $this->clearRedis();
        $this->requiredSitemapXmlUrlCount = null;
    }
   
    
    protected function rebuildDefaultDataState() {
        $this->removeAllUsers();        
        $this->loadDataFixtures();
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
    protected function getController($controllerName, $methodName, array $postData = array(), array $queryData = array()) {        
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
    
    
    protected function createAndResolveJob($canonicalUrl, $userEmail = null, $type = null, $testTypes = null, $testTypeOptions = null, $parameters = null) {
        $this->queueResolveHttpFixture();
        
        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);
        return $job_id;
    }
    
    
    protected function createAndResolveDefaultJob() {
        return $this->createAndResolveJob(self::DEFAULT_CANONICAL_URL);
    }
    
    
    protected function createResolveAndPrepareJob($canonicalUrl, $userEmail = null, $type = null, $testTypes = null, $testTypeOptions = null, $parameters = null) {        
        $this->queueResolveHttpFixture();        
        $this->queuePrepareHttpFixturesForJob($canonicalUrl);
        
        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);
        $this->prepareJob($canonicalUrl, $job_id);        
        
        return $job_id;        
    }
    
    
    protected function createResolveAndPrepareCrawlJob($canonicalUrl, $userEmail = null, $type = null, $testTypes = null, $testTypeOptions = null, $parameters = null) {        
        $this->queueResolveHttpFixture();        
        $this->queuePrepareHttpFixturesForCrawlJob($canonicalUrl);
        
        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);
        $this->prepareJob($canonicalUrl, $job_id);        
        
        return $job_id;        
    }    
    
    
    protected function createResolveAndPrepareDefaultJob() {        
        return $this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL);
    }
    
   protected function createResolveAndPrepareDefaultCrawlJob() {        
        return $this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL);
    }    
    
    
    protected function queueResolveHttpFixture() {        
        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200'
        )));
    }    
    
    protected function queuePrepareHttpFixturesForJob($url) {        
        $fixtureMessages = array(
            $this->getDefaultRobotsTxtFixtureContent(),
            $this->getDefaultSitemapXmlFixtureContent()
        );
        
        foreach ($fixtureMessages as $index => $fixtureMessage) {            
            if ($url != self::DEFAULT_CANONICAL_URL && substr_count($fixtureMessage, self::DEFAULT_CANONICAL_URL)) {
                $fixtureMessage = str_replace(self::DEFAULT_CANONICAL_URL, $url, $fixtureMessage);
                $fixtureMessages[$index] = $fixtureMessage;
            }
        }
        
        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet($fixtureMessages));
    }
    
    protected function getDefaultRobotsTxtFixtureContent() {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/plain

User-Agent: *
Sitemap: http://example.com/sitemap.xml
EOD;
    }
    
    
    /**
     * 
     * @param int $count
     */
    protected function setRequiredSitemapXmlUrlCount($count) {
        $this->requiredSitemapXmlUrlCount = $count;
    }
    
    
    /**
     * @return int
     */
    protected function getRequiredSitemapXmlUrlCount() {
        return (is_null($this->requiredSitemapXmlUrlCount)) ? self::DEFAULT_REQUIRED_SITEMAP_XML_URL_COUNT : $this->requiredSitemapXmlUrlCount;
    }
    

    protected function getDefaultSitemapXmlFixtureContent() {
        $urls = array();
        for ($index = 0; $index < $this->getRequiredSitemapXmlUrlCount(); $index++) {
            $urls[] = '<url><loc>' . self::DEFAULT_CANONICAL_URL . $index . '/</loc></url>';
        }
        
        $urlsString = implode("\n", $urls);
        
return <<<EOD
HTTP/1.1 200 OK
Content-Type: text/xml

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
$urlsString
</urlset>
EOD;
    }    
    
    protected function queuePrepareHttpFixturesForCrawlJob($url) {       
        $fixtureMessages = array(
            "HTTP/1.0 200 OK\nContent-Type: text/plain\n\nUser-Agent: *",
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
        );
        
        foreach ($fixtureMessages as $index => $fixtureMessage) {            
            if ($url != self::DEFAULT_CANONICAL_URL && substr_count($fixtureMessage, self::DEFAULT_CANONICAL_URL)) {
                $fixtureMessage = str_replace(self::DEFAULT_CANONICAL_URL, $url, $fixtureMessage);
                $fixtureMessages[$index] = $fixtureMessage;
            }
        }
        
        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet($fixtureMessages));
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
     * @param Job $job
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function cancelJob(Job $job) {
        return $this->getJobController('cancelAction')->cancelAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
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
        $this->executeCommand('simplytestable:job:prepare', array(
            'id' => $job_id           
        ));       
    
        return json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
    }
    
    
    /**
     * 
     * @param string $canonicalUrl
     * @param int $job_id
     * @return \stdClass
     */    
    protected function resolveJob($canonicalUrl, $job_id) {
        $this->executeCommand('simplytestable:job:resolve', array(
            'id' => $job_id           
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function fetchJob($canonicalUrl, $id) {        
        return $this->getJobController('statusAction')->statusAction($canonicalUrl, $id);    
    }  
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param array $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */   
    protected function fetchJobResponse(Job $job, $parameters = array()) {
        return $this->getJobController('statusAction', $parameters)->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
    }
    
    
    /**
     *
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param array $parameters
     * @return \stdClass
     */    
    protected function fetchJobStatusObject(Job $job, $parameters = array()) {        
        return json_decode($this->fetchJobResponse($job)->getContent());
    }
    
    
    /**
     * 
     * @param Job $job
     * @return array
     */
    protected function getTaskIds(Job $job) {
        return json_decode($this->getJobController('taskIdsAction')->taskIdsAction($job->getWebsite()->getCanonicalUrl(), $job->getId())->getContent());        
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
     * @return \SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService
     */
    protected function getJobWebsiteResolutionService() {
        return $this->container->get('simplytestable.services.jobwebsiteresolutionservice');
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
     * @return \SimplyTestable\ApiBundle\Services\TestHttpClientService
     */
    protected function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }  
    
    
    protected function assertSystemCurlOptionsAreSetOnAllRequests() {
        foreach ($this->getHttpClientService()->getHistoryPlugin()->getAll() as $httpTransaction) {
            foreach ($this->container->getParameter('curl_options') as $curlOption) {                                
                $expectedValueAsString = $curlOption['value'];
                
                if (is_string($expectedValueAsString)) {
                    $expectedValueAsString = '"'.$expectedValueAsString.'"';
                }                
                
                if (is_bool($curlOption['value'])) {
                    $expectedValueAsString = ($curlOption['value']) ? 'true' : 'false';
                }
                
                $this->assertEquals(
                    $curlOption['value'],
                    $httpTransaction['request']->getCurlOptions()->get(constant($curlOption['name'])),
                    'Curl option "'.$curlOption['name'].'" not set to ' . $expectedValueAsString . ' for ' .$httpTransaction['request']->getMethod() . ' ' . $httpTransaction['request']->getUrl()
                );
            }
        }
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
    
    
    protected function queueHttpFixtures($fixtures) {
        foreach ($fixtures as $fixture) {
            $this->getHttpClientService()->queueFixture($fixture);         
        }        
    }
    
    protected function getHttpFixtureMessagesFromPath($path) {
        $messages = array();        
        $fixturesDirectory = new \DirectoryIterator($path);
        
        $fixturePathnames = array();
        
        foreach ($fixturesDirectory as $directoryItem) {
            if ($directoryItem->isFile()) { 
                $fixturePathnames[] = $directoryItem->getPathname();
            }
        }
        
        sort($fixturePathnames);
        
        foreach ($fixturePathnames as $fixturePathname) {                        
            $messages[] = trim(file_get_contents($fixturePathname));
        }
        
        return $messages;        
    }
    
    
    protected function getFixture($path) {
        return file_get_contents($path);
    }
    
    
    /**
     * 
     * @param array $items Collection of http messages and/or curl exceptions
     * @return array
     */
    protected function buildHttpFixtureSet($items) {
        $fixtures = array();
        
        foreach ($items as $item) {
            switch ($this->getHttpFixtureItemType($item)) {
                case 'httpMessage':
                    $fixtures[] = \Guzzle\Http\Message\Response::fromMessage($item);
                    break;
                
                case 'curlException':
                    $fixtures[] = $this->getCurlExceptionFromCurlMessage($item);                    
                    break;
                
                default:
                    throw new \LogicException();
            }
        }
        
        return $fixtures;
    }    
    
    
    /**
     * 
     * @param string $item
     * @return string
     */
    private function getHttpFixtureItemType($item) {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }
        
        return 'curlException';
    }  
    
    
    /**
     * 
     * @param string $curlMessage
     * @return \Guzzle\Http\Exception\CurlException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage) {
        $curlMessageParts = explode(' ', $curlMessage, 2);
        
        $curlException = new \Guzzle\Http\Exception\CurlException();
        if (isset($curlMessageParts[1])) {
            $curlException->setError($curlMessageParts[1], (int)str_replace('CURL/', '', $curlMessageParts[0]));
        } else {
            $curlException->setError('Default Curl Message', (int)str_replace('CURL/', '', $curlMessageParts[0]));
        }
        
        return $curlException;
    }      
}
