<?php

namespace SimplyTestable\ApiBundle\Tests;

use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Controller\Job\JobListController;
use SimplyTestable\ApiBundle\Controller\Job\StartController as JobStartController;
use SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController as JobConfigurationCreateController;
use SimplyTestable\ApiBundle\Controller\Stripe\WebHookController as StripeWebHookController;
use SimplyTestable\ApiBundle\Controller\TaskController;
use SimplyTestable\ApiBundle\Controller\TeamInviteController;
use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Controller\UserCreationController;
use SimplyTestable\ApiBundle\Controller\UserEmailChangeController;
use SimplyTestable\ApiBundle\Controller\UserPasswordResetController;
use SimplyTestable\ApiBundle\Controller\UserStripeEventController;
use SimplyTestable\ApiBundle\Controller\WorkerController;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint as AccountPlanConstraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\CommandService;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Job\WebsiteResolutionService;
use SimplyTestable\ApiBundle\Services\JobPreparationService;
use SimplyTestable\ApiBundle\Services\JobRejectionReasonService;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\Resque\JobFactoryService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use SimplyTestable\ApiBundle\Services\Task\QueueService as TaskQueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\MemberService as TeamMemberService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\TestHttpClientService;
use SimplyTestable\ApiBundle\Services\TestStripeService;
use SimplyTestable\ApiBundle\Services\TestUserService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserEmailChangeRequestService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Guzzle\Http\Message\Response as GuzzleResponse;

abstract class BaseSimplyTestableTestCase extends BaseTestCase
{
    const ROUTER_MATCH_CONTROLLER_KEY = '_controller';

    const JOB_CONTROLLER_NAME = JobController::class;
    const JOB_LIST_CONTROLLER_NAME = JobListController::class;
    const JOB_START_CONTROLLER_NAME = JobStartController::class;
    const USER_CREATION_CONTROLLER_NAME = UserCreationController::class;
    const USER_PASSWORD_RESET_CONTROLLER_NAME = UserPasswordResetController::class;
    const USER_EMAIL_CHANGE_CONTROLLER_NAME = UserEmailChangeController::class;
    const USER_CONTROLLER_NAME = UserController::class;
    const USER_ACCOUNT_PLAN_SUBSCRIPTION_CONTROLLER_NAME = UserAccountPlanSubscriptionController::class;
    const WORKER_CONTROLLER_NAME = WorkerController::class;
    const STRIPE_WEBHOOK_CONTROLLER_NAME = StripeWebHookController::class;
    const USER_STRIPE_EVENT_CONTROLLER_NAME = UserStripeEventController::class;
    const TEAM_INVITE_CONTROLLER_NAME = TeamInviteController::class;
    const JOBCONFIGURATION_CREATE_CONTROLLER_NAME = JobConfigurationCreateController::class;

    const TEST_USER_EMAIL = 'user@example.com';
    const TEST_USER_PASSWORD = 'password';

    const DEFAULT_CANONICAL_URL = 'http://example.com/';
    const DEFAULT_REQUIRED_SITEMAP_XML_URL_COUNT = 3;

    private $requiredSitemapXmlUrlCount = null;

    public function setUp()
    {
        parent::setUp();
        $this->clearRedis();
        $this->requiredSitemapXmlUrlCount = null;
    }

    public function createPublicUserAccountPlan()
    {
        $user = $this->getUserService()->getPublicUser();
        $plan = $this->getAccountPlanService()->find('public');

        $this->getUserAccountPlanService()->subscribe($user, $plan);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getManager()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @param string $methodName
     * @param array $postData
     *
     * @return JobStartController
     */
    protected function getJobStartController($methodName, $postData = array())
    {
        return $this->getController(self::JOB_START_CONTROLLER_NAME, $methodName, $postData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     *
     * @return JobController
     */
    protected function getJobController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::JOB_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }


    /**
     * @param string $methodName
     * @param array $postData
     *
     * @return JobListController
     */
    protected function getJobListController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::JOB_LIST_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     * @return UserCreationController
     */
    protected function getUserCreationController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::USER_CREATION_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }


    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return TeamInviteController
     */
    protected function getTeamInviteController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::TEAM_INVITE_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return JobConfigurationCreateController
     */
    protected function getJobConfigurationCreateController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::JOBCONFIGURATION_CREATE_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return UserAccountPlanSubscriptionController
     */
    protected function getUserAccountPlanSubscriptionController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(
            self::USER_ACCOUNT_PLAN_SUBSCRIPTION_CONTROLLER_NAME,
            $methodName,
            $postData,
            $queryData
        );
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return UserPasswordResetController
     */
    protected function getUserPasswordResetController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::USER_PASSWORD_RESET_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return UserEmailChangeController
     */
    protected function getUserEmailChangeController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::USER_EMAIL_CHANGE_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return UserController
     */
    protected function getUserController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::USER_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return WorkerController
     */
    protected function getWorkerController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::WORKER_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return StripeWebHookController
     */
    protected function getStripeWebHookController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::STRIPE_WEBHOOK_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return UserStripeEventController
     */
    protected function getUserStripeEventController($methodName, $postData = array(), $queryData = array())
    {
        return $this->getController(self::USER_STRIPE_EVENT_CONTROLLER_NAME, $methodName, $postData, $queryData);
    }

    /**
     * @param string $controllerName
     * @param string $methodName
     * @param array $postData
     * @param array $queryData
     *
     * @return Controller
     */
    protected function getController(
        $controllerName,
        $methodName,
        array $postData = array(),
        array $queryData = array()
    ) {
        return $this->createController($controllerName, $methodName, $postData, $queryData);
    }

    /**
     * @param string $url
     * @return int
     */
    protected function getJobIdFromUrl($url)
    {
        $urlParts = explode('/', $url);

        return (int)$urlParts[count($urlParts) - 2];
    }

    /**
     * @param string $canonicalUrl
     * @param string $userEmail
     * @param string $type
     * @param array $testTypes
     *
     * @return RedirectResponse
     */
    protected function createJob(
        $canonicalUrl,
        $userEmail = null,
        $type = null,
        $testTypes = null,
        $testTypeOptions = null,
        $parameters = null
    ) {
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

        return $this->getJobStartController('startAction', $postData)->startAction(
            $this->container->get('request'),
            $canonicalUrl
        );
    }

    /**
     * @param $canonicalUrl
     * @param string $userEmail
     * @param string $type
     * @param array $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     *
     * @return int
     */
    protected function createAndResolveJob(
        $canonicalUrl,
        $userEmail = null,
        $type = null,
        $testTypes = null,
        $testTypeOptions = null,
        $parameters = null
    ) {
        $this->queueResolveHttpFixture();

        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);

        return $job_id;
    }

    protected function createAndResolveDefaultJob()
    {
        return $this->createAndResolveJob(self::DEFAULT_CANONICAL_URL);
    }

    /**
     * @param $canonicalUrl
     * @param string $userEmail
     * @param string $type
     * @param array $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     *
     * @return int
     */
    protected function createResolveAndPrepareJob(
        $canonicalUrl,
        $userEmail = null,
        $type = null,
        $testTypes = null,
        $testTypeOptions = null,
        $parameters = null
    ) {
        $this->queueResolveHttpFixture();
        $this->queuePrepareHttpFixturesForJob($canonicalUrl);

        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);
        $this->prepareJob($canonicalUrl, $job_id);

        return $job_id;
    }

    /**
     * @param $canonicalUrl
     * @param string $userEmail
     * @param string $type
     * @param array $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     *
     * @return int
     */
    protected function createResolveAndPrepareCrawlJob(
        $canonicalUrl,
        $userEmail = null,
        $type = null,
        $testTypes = null,
        $testTypeOptions = null,
        $parameters = null
    ) {
        $this->queueResolveHttpFixture();
        $this->queuePrepareHttpFixturesForCrawlJob($canonicalUrl);

        $job_id = $this->createJobAndGetId($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        $this->resolveJob($canonicalUrl, $job_id);
        $this->prepareJob($canonicalUrl, $job_id);

        return $job_id;
    }

    /**
     * @return int
     */
    protected function createResolveAndPrepareDefaultJob()
    {
        return $this->createResolveAndPrepareJob(self::DEFAULT_CANONICAL_URL);
    }

    /**
     * @return int
     */
    protected function createResolveAndPrepareDefaultCrawlJob()
    {
        return $this->createResolveAndPrepareCrawlJob(self::DEFAULT_CANONICAL_URL);
    }

    protected function queueResolveHttpFixture()
    {
        $this->getHttpClientService()->queueFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 200'
        )));
    }

    protected function queuePrepareHttpFixturesForJob($url)
    {
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

    protected function getDefaultRobotsTxtFixtureContent()
    {
return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: text/plain

User-Agent: *
Sitemap: http://example.com/sitemap.xml
EOD;
    }

    protected function queueTaskAssignCollectionResponseHttpFixture()
    {
        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.1 200 OK\nContent-Type: application/json\n\n" . '[{"id":1,"url":"http:\/\/example.com\/","state":"queued","type":"HTML validation","parameters":""}]'
        )));
    }

    /**
     * @param int $count
     */
    protected function setRequiredSitemapXmlUrlCount($count)
    {
        $this->requiredSitemapXmlUrlCount = $count;
    }

    /**
     * @return int
     */
    protected function getRequiredSitemapXmlUrlCount()
    {
        return (is_null($this->requiredSitemapXmlUrlCount))
            ? self::DEFAULT_REQUIRED_SITEMAP_XML_URL_COUNT
            : $this->requiredSitemapXmlUrlCount;
    }

    protected function getDefaultSitemapXmlFixtureContent()
    {
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

    protected function queuePrepareHttpFixturesForCrawlJob($url)
    {
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

    protected function setJobTasksCompleted(Job $job)
    {
        foreach ($job->getTasks() as $task) {
            /* @var $task Task */
            $task->setState($this->getTaskService()->getCompletedState());

            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime());
            $timePeriod->setEndDateTime(new \DateTime());
            $task->setTimePeriod($timePeriod);

            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush($task);
        }
    }

    /**
     * @param string $canonicalUrl
     * @param string $userEmail
     * @param string $type
     * @param array $testTypes
     * @param array $testTypeOptions
     * @param array $parameters
     *
     * @return int
     */
    protected function createJobAndGetId(
        $canonicalUrl,
        $userEmail = null,
        $type = 'full site',
        $testTypes = null,
        $testTypeOptions = null,
        $parameters = null
    ) {
        $response = $this->createJob($canonicalUrl, $userEmail, $type, $testTypes, $testTypeOptions, $parameters);
        return $this->getJobIdFromUrl($response->getTargetUrl());
    }

    /**
     * @param Job $job
     *
     * @return Response
     */
    protected function cancelJob(Job $job) {
        return $this->getJobController('cancelAction')->cancelAction
        ($job->getWebsite()->getCanonicalUrl(), $job->getId()
        );
    }

    protected function completeJob(Job $job)
    {
        $this->setJobTasksCompleted($job);
        $job->setState($this->getJobService()->getInProgressState());
        $job->setTimePeriod(new TimePeriod());
        $this->getJobService()->complete($job);
    }

    /**
     * @param string $canonicalUrl
     * @param int $job_id
     *
     * @return \stdClass
     */
    protected function prepareJob($canonicalUrl, $job_id)
    {
        $this->executeCommand('simplytestable:job:prepare', array(
            'id' => $job_id
        ));

        return json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
    }

    /**
     * @param string $canonicalUrl
     * @param int $job_id
     *
     * @return \stdClass
     */
    protected function resolveJob($canonicalUrl, $job_id)
    {
        $this->executeCommand('simplytestable:job:resolve', array(
            'id' => $job_id
        ));

        return json_decode($this->fetchJob($canonicalUrl, $job_id)->getContent());
    }

    /**
     * @param string $canonicalUrl
     * @param int $jobId
     * @param string $userEmail
     *
     * @return Response
     */
    protected function getJobStatus($canonicalUrl, $jobId, $userEmail = null)
    {
        $postData = (is_null($userEmail)) ? array() : array(
            'user' => $userEmail
        );

        return $this->getJobController('statusAction', $postData)->statusAction($canonicalUrl, $jobId);
    }

    /**
     * @param string $email
     *
     * @return RedirectResponse
     */
    protected function createUser($email, $password)
    {
        return $this->getUserCreationController('createAction', array(
            'email' => $email,
            'password' => $password
        ))->createAction();
    }

    /**
     * @param string $email
     *
     * @return User
     */
    protected function createAndFindUser($email, $password = 'password')
    {
        $this->createUser($email, $password);

        return $this->getUserService()->findUserByEmail($email);
    }

    /**
     * @param string $email
     * @param string $password
     *
     * @return User
     */
    protected function createAndActivateUser($email = 'user@example.com', $password = 'password')
    {
        $this->createUser($email, $password);

        $user = $this->getUserService()->findUserByEmail($email);
        $this->getUserCreationController('activateAction')->activateAction($user->getConfirmationToken());

        return $user;
    }

    /**
     * @return User
     */
    protected function getTestUser() {
        $user = $this->getUserService()->findUserByEmail(self::TEST_USER_EMAIL);
        if (is_null($user)) {
            $this->createAndActivateUser(self::TEST_USER_EMAIL, self::TEST_USER_PASSWORD);
        }

        return $this->getUserService()->findUserByEmail(self::TEST_USER_EMAIL);
    }

    /**
     * @param int $count
     *
     * @return array
     */
    protected function createAndActivateUserCollection($count)
    {
        $users = array();
        for ($index = 0; $index < $count; $index++) {
            $users[] = $this->createAndActivateUser('user'.$index.'@example.com', 'password');
        }

        return $users;
    }

    /**
     * @param User $user
     *
     * @return string
     */
    protected function getPasswordResetToken(User $user)
    {
        $this->getUserPasswordResetController('getTokenAction')->getTokenAction($user->getEmail());
        return $this->getUserService()->getConfirmationToken($user);
    }

    /**
     * @param string $canonicalUrl
     * @param int $id
     *
     * @return Response
     */
    protected function fetchJob($canonicalUrl, $id)
    {
        return $this->getJobController('statusAction')->statusAction($canonicalUrl, $id);
    }

    /**
     * @param Job $job
     * @param array $parameters
     *
     * @return Response
     */
    protected function fetchJobResponse(Job $job, $parameters = array())
    {
        return $this->getJobController('statusAction', $parameters)->statusAction(
            $job->getWebsite()->getCanonicalUrl(), $job->getId()
        );
    }

    /**
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     *
     * @return \stdClass
     */
    protected function fetchJobStatusObject(Job $job)
    {
        return json_decode($this->fetchJobResponse($job)->getContent());
    }

    /**
     * @param Job $job
     *
     * @return array
     */
    protected function getTaskIds(Job $job)
    {
        return json_decode(
            $this->getJobController('taskIdsAction')->taskIdsAction(
                $job->getWebsite()->getCanonicalUrl(),
                $job->getId()
            )->getContent()
        );
    }

    /**
     * @return CrawlJobContainerService
     */
    protected function getCrawlJobContainerService()
    {
        return $this->container->get('simplytestable.services.crawljobcontainerservice');
    }

    /**
     * @return JobTypeService
     */
    protected function getJobTypeService()
    {
        return $this->container->get('simplytestable.services.jobtypeservice');
    }

    /**
     * @return JobService
     */
    protected function getJobService()
    {
        return $this->container->get('simplytestable.services.jobservice');
    }

    /**
     * @return WebsiteResolutionService
     */
    protected function getJobWebsiteResolutionService()
    {
        return $this->container->get('simplytestable.services.jobwebsiteresolutionservice');
    }

    /**
     * @return JobPreparationService
     */
    protected function getJobPreparationService()
    {
        return $this->container->get('simplytestable.services.jobpreparationservice');
    }

    /**
     * @return WebSiteService
     */
    protected function getWebSiteService()
    {
        return $this->container->get('simplytestable.services.websiteservice');
    }

    /**
     * @return JobRejectionReasonService
     */
    protected function getJobRejectionReasonService()
    {
        return $this->container->get('simplytestable.services.jobrejectionreasonservice');
    }

    /**
     * @return JobUserAccountPlanEnforcementService
     */
    protected function getJobUserAccountPlanEnforcementService()
    {
        return $this->container->get('simplytestable.services.jobuseraccountplanenforcementservice');
    }

    /**
     * @return WorkerService
     */
    protected function getWorkerService()
    {
        return $this->container->get('simplytestable.services.workerservice');
    }

    /**
     * @return TestUserService
     */
    protected function getUserService()
    {
        return $this->container->get('simplytestable.services.userservice');
    }

    /**
     * @return AccountPlanService
     */
    protected function getAccountPlanService()
    {
        return $this->container->get('simplytestable.services.accountplanservice');
    }

    /**
     * @return UserAccountPlanService
     */
    protected function getUserAccountPlanService()
    {
        return $this->container->get('simplytestable.services.useraccountplanservice');
    }

    /**
     * @return TaskService
     */
    protected function getTaskService()
    {
        return $this->container->get('simplytestable.services.taskservice');
    }

    /**
     * @return TaskQueueService
     */
    protected function getTaskQueueService()
    {
        return $this->container->get('simplytestable.services.task.queueService');
    }

    /**
     * @return TaskTypeService
     */
    protected function getTaskTypeService()
    {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }

    /**
     * @return ResqueQueueService
     */
    protected function getResqueQueueService()
    {
        return $this->container->get('simplytestable.services.resque.queueService');
    }

    /**
     * @return JobFactoryService
     */
    protected function getResqueJobFactoryService()
    {
        return $this->container->get('simplytestable.services.resque.jobFactoryService');
    }

    /**
     * @return StateService
     */
    protected function getStateService()
    {
        return $this->container->get('simplytestable.services.stateservice');
    }

    /**
     * @return WorkerActivationRequestService
     */
    protected function getWorkerActivationRequestService()
    {
        return $this->container->get('simplytestable.services.workeractivationrequestservice');
    }

    /**
     * @return UserEmailChangeRequestService
     */
    protected function getUserEmailChangeRequestService()
    {
        return $this->container->get('simplytestable.services.useremailchangerequestservice');
    }

    /**
     * @return TestHttpClientService
     */
    protected function getHttpClientService()
    {
        return $this->container->get('simplytestable.services.httpclientservice');
    }

    protected function assertSystemCurlOptionsAreSetOnAllRequests()
    {
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
                    sprintf(
                        'Curl option "%s" not set to %s for %s %s',
                        $curlOption['name'],
                        $expectedValueAsString,
                        $httpTransaction['request']->getMethod(),
                        $httpTransaction['request']->getUrl()
                    )
                );
            }
        }
    }

    /**
     * @return TestStripeService
     */
    protected function getStripeService()
    {
        return $this->container->get('simplytestable.services.stripeservice');
    }

    /**
     * @return StripeEventService
     */
    protected function getStripeEventService()
    {
        return $this->container->get('simplytestable.services.stripeeventservice');
    }

    /**
     * @return CommandService
     */
    protected function getCommandService()
    {
        return $this->container->get('simplytestable.services.commandservice');
    }

    /**
     * @param string $hostname
     * @param string $token
     *
     * @return Worker
     */
    protected function createWorker($hostname = null, $token = null)
    {
        if (is_null($hostname)) {
            $hostname = md5(time()) . '.worker.simplytestable.com';
        }

        $worker = $this->getWorkerService()->get($hostname);
        $worker->setToken($token);
        $this->getWorkerService()->persistAndFlush($worker);

        $worker->setState($this->getStateService()->fetch('worker-active'));

        $this->getWorkerService()->persistAndFlush($worker);
        return $worker;
    }

    protected function createPublicUserIfMissing()
    {
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

    protected function createAdminUserIfMissing()
    {
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

    /**
     * Create and return a collection of workers
     *
     * @param int $requestedWorkerCount
     * @return array
     */
    protected function createWorkers($requestedWorkerCount)
    {
        $workers = array();

        for ($workerIndex = 0; $workerIndex < $requestedWorkerCount; $workerIndex++) {
            $workers[] = $this->createWorker('worker'.$workerIndex.'.worker.simplytestable.com');
        }

        return $workers;
    }

    /**
     * @param string $name
     *
     * @return Plan
     */
    protected function createAccountPlan($name = null) {
        if (is_null($name)) {
            $name = 'test-foo-plan';
        }

        $plan = new Plan;
        $plan->setName($name);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        return $plan;
    }

    /**
     * @return AccountPlanConstraint
     */
    protected function createAccountPlanConstraint()
    {
        $plan = $this->createAccountPlan();

        $constraint = new AccountPlanConstraint();
        $constraint->setName('bar');
        $constraint->setPlan($plan);

        $this->getManager()->persist($constraint);
        $this->getManager()->flush();

        return $constraint;
    }

    /**
     * @param string $baseUrl
     * @param int $count
     *
     * @return string[]
     */
    protected function createUrlResultSet($baseUrl, $count, $offset = 0)
    {
        $urlResultSet = array();

        for ($index = $offset; $index < $count + $offset; $index++) {
            $urlResultSet[] = $baseUrl . $index . '/';
        }

        return $urlResultSet;
    }

    /**
     * @param array $fixtures
     */
    protected function queueHttpFixtures($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->getHttpClientService()->queueFixture($fixture);
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    protected function getHttpFixtureMessagesFromPath($path)
    {
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

    /**
     * @param $path
     *
     * @return string
     */
    protected function getFixture($path)
    {
        return file_get_contents($path);
    }

    /**
     *
     * @param array $items Collection of http messages and/or curl exceptions
     * @return array
     */
    protected function buildHttpFixtureSet($items)
    {
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
     * @param string $item
     * @return string
     */
    private function getHttpFixtureItemType($item)
    {
        if (substr($item, 0, strlen('HTTP')) == 'HTTP') {
            return 'httpMessage';
        }

        return 'curlException';
    }

    /**
     *
     * @param string $curlMessage
     *
     * @return CurlException
     */
    private function getCurlExceptionFromCurlMessage($curlMessage)
    {
        $curlMessageParts = explode(' ', $curlMessage, 2);

        $curlException = new CurlException();
        if (isset($curlMessageParts[1])) {
            $curlException->setError($curlMessageParts[1], (int)str_replace('CURL/', '', $curlMessageParts[0]));
        } else {
            $curlException->setError('Default Curl Message', (int)str_replace('CURL/', '', $curlMessageParts[0]));
        }

        return $curlException;
    }

    /**
     * @return TeamMemberService
     */
    protected function getTeamMemberService()
    {
        return $this->container->get('simplytestable.services.teammemberservice');
    }

    /**
     * @return TeamService
     */
    protected function getTeamService()
    {
        return $this->container->get('simplytestable.services.teamservice');
    }

    /**
     * @return InviteService
     */
    protected function getTeamInviteService()
    {
        return $this->container->get('simplytestable.services.teaminviteservice');
    }

    /**
     * @return string
     */
    protected function getControllerNameFromRouter()
    {
        return explode('::', $this->getRouteController())[0];
    }

    /**
     * @return string
     */
    protected function getActionNameFromRouter($routeParameters = null)
    {
        return explode('::', $this->getRouteController($routeParameters))[1];
    }

    /**
     * @param array $routeParameters
     *
     * @return mixed
     */
    protected function getRouteController($routeParameters = null)
    {
        $routeParameters = (is_null($routeParameters)) ? $this->getRouteParameters() : $routeParameters;

        return $this
            ->getRouter()
            ->match($this->getCurrentRequestUrl($routeParameters))[self::ROUTER_MATCH_CONTROLLER_KEY];
    }

    /**
     * @param array $routeParameters
     *
     * @return string
     */
    protected function getCurrentRequestUrl($routeParameters = null)
    {
        $routeParameters = (is_null($routeParameters)) ? $this->getRouteParameters() : $routeParameters;

        return $this->getCurrentController()->generateUrl($this->getRouteFromTestNamespace(), $routeParameters);
    }

    /**
     * @param array $postData
     * @param array $queryData
     *
     * @return Controller
     */
    protected function getCurrentController(array $postData = [], array $queryData = [])
    {
        return $this->getController(
            $this->getControllerNameFromTestNamespace(),
            $this->getActionNameFromTestNamespace(),
            $this->getControllerPostData($postData),
            $this->getControllerQueryData($queryData)
        );
    }

    /**
     * @param array $postData
     *
     * @return array
     */
    private function getControllerPostData(array $postData = [])
    {
        if (empty($postData)) {
            return $this->getRequestPostData();
        }

        return $postData;
    }

    /**
     * @return array
     */
    protected function getRequestPostData()
    {
        return [];
    }

    /**
     * @param array $queryData
     *
     * @return array
     */
    private function getControllerQueryData(array $queryData = [])
    {
        if (empty($queryData)) {
            return $this->getRequestQueryData();
        }

        return $queryData;
    }

    /**
     * @return array
     */
    protected function getRequestQueryData()
    {
        return [];
    }

    /**
     * Get route name for current test
     *
     * Is extracted from the class namespace as follows:
     * \Acme\FooBundle\Tests\Controller\Foo => 'foo'
     * \Acme\FooBundle\Tests\Controller\FooBar => 'foo_bar'
     * \Acme\FooBundle\Tests\Controller\FooBar\Bar => 'foobar_bar'
     *
     * @return string
     */
    protected function getRouteFromTestNamespace()
    {
        return strtolower(
            implode('_', $this->getControllerRelatedNamespaceParts())
            . '_'
            . str_replace('Action', '', $this->getActionNameFromTestNamespace())
        );
    }

    /**
     * @return string
     */
    protected function getExpectedRouteController()
    {
        return $this->getControllerNameFromTestNamespace() . '::' . $this->getActionNameFromTestNamespace();
    }

    /**
     * Get controller name from current test namespace
     *
     * @return string
     */
    private function getControllerNameFromTestNamespace()
    {
        return implode('\\', $this->getControllerNamespaceParts()) . 'Controller';
    }

    /**
     * Get controller action from current test namespace
     *
     * @return string
     */
    private function getActionNameFromTestNamespace()
    {
        foreach ($this->getNamespaceParts() as $part) {
            if (preg_match('/.+Action$/', $part)) {
                return lcfirst($part);
            }
        }
    }

    /**
     * @return string[]
     */
    private function getControllerNamespaceParts()
    {
        $relevantParts = array();

        foreach ($this->getNamespaceParts() as $part) {
            if (preg_match('/.+Action$/', $part)) {
                return $relevantParts;
            }

            if ($part != 'Tests') {
                $relevantParts[] = $part;
            }
        }

        return $relevantParts;
    }

    /**
     *
     * @return string[]
     */
    private function getControllerRelatedNamespaceParts()
    {
        $parts = $this->getControllerNamespaceParts();

        foreach ($parts as $index => $part) {
            if ($part === 'Controller') {
                return array_slice($parts, $index + 1);
            }
        }

        return $parts;
    }

    /**
     * @return string[]
     */
    private function getNamespaceParts()
    {
        $parts = explode('\\', get_class($this));
        array_pop($parts);

        return $parts;
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        return $this->client->getContainer()->get('router');
    }

    /**
     * @return array
     */
    protected function getRouteParameters()
    {
        return [];
    }

    /**
     * @return JobFactory
     */
    protected function createJobFactory()
    {
        return new JobFactory(
            $this->container->get('simplytestable.services.jobtypeservice'),
            $this->container->get('simplytestable.services.websiteservice'),
            $this->container->get('simplytestable.services.tasktypeservice'),
            $this->container->get('simplytestable.services.job.startservice'),
            $this->container->get('simplytestable.services.jobwebsiteresolutionservice'),
            $this->container->get('simplytestable.services.jobpreparationservice'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.userservice'),
            $this->container->get('simplytestable.services.job.rejectionservice')
        );
    }


    /**
     * @return UserFactory
     */
    protected function createUserFactory()
    {
        return new UserFactory(
            $this->container->get('simplytestable.services.userservice'),
            $this->container->get('fos_user.util.user_manipulator'),
            $this->container->get('simplytestable.services.useraccountplanservice'),
            $this->container->get('simplytestable.services.accountplanservice')
        );
    }

    /**
     * @param Request $request
     *
     * @return TaskController
     */
    protected function createTaskController(Request $request)
    {
        $this->container->set('request', $request);
        $this->container->enterScope('request');

        $controller = new TaskController();
        $controller->setContainer($this->container);

        return $controller;
    }

    /**
     * @param Response $response
     *
     * @return Job
     */
    protected function getJobFromResponse(Response $response)
    {
        $locationHeader = $response->headers->get('location');
        $locationHeaderParts = explode('/', rtrim($locationHeader, '/'));

        return $this->getJobService()->getById(
            (int)$locationHeaderParts[count($locationHeaderParts) - 1]
        );
    }

    protected function queueStandardJobHttpFixtures()
    {
        $this->queueHttpFixtures([
            GuzzleResponse::fromMessage('HTTP/1.1 200 OK'),
            GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml"),
            GuzzleResponse::fromMessage(sprintf(
                "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
                SitemapFixtureFactory::load('example.com-three-urls')
            )),
        ]);
    }
}
