<?php

namespace SimplyTestable\ApiBundle\Tests\Functional;

use Guzzle\Http\Exception\CurlException;
use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Controller\Job\JobListController;
use SimplyTestable\ApiBundle\Controller\Job\StartController as JobStartController;
use SimplyTestable\ApiBundle\Controller\JobConfiguration\CreateController as JobConfigurationCreateController;
use SimplyTestable\ApiBundle\Controller\Stripe\WebHookController as StripeWebHookController;
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
use SimplyTestable\ApiBundle\Tests\Factory\ControllerFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

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

    const DEFAULT_CANONICAL_URL = 'http://example.com/';
    const DEFAULT_REQUIRED_SITEMAP_XML_URL_COUNT = 3;

    private $requiredSitemapXmlUrlCount = null;

    protected function setUp()
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
     * @param Job $job
     */
    protected function completeJob(Job $job)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobInProgressState = $stateService->fetch(JobService::IN_PROGRESS_STATE);

        $this->setJobTasksCompleted($job);
        $job->setState($jobInProgressState);
        $job->setTimePeriod(new TimePeriod());
        $this->getJobService()->complete($job);
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

            if ($part != 'Tests' && $part != 'Functional') {
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
     * @return ControllerFactory
     */
    protected function createControllerFactory()
    {
        return new ControllerFactory($this->container);
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

    /**
     * @param array $options
     *
     * @return Crawler
     */
    protected function getCrawler($options)
    {
        if (!isset($options['url'])) {
            $options['url'] = '';
        }

        if (!isset($options['method'])) {
            $options['method'] = 'GET';
        }

        if (!isset($options['parameters'])) {
            $options['parameters'] = [];
        }

        if (!isset($options['files'])) {
            $options['files'] = [];
        }

        if (!isset($options['server'])) {
            $options['server'] = [];
        }

        if (!isset($options['user'])) {
            $options['user']  = $this->getUserService()->getPublicUser();
        }

        $this->setRequestUserInSession($options['user']);

        $crawler = $this->client->request(
            $options['method'],
            $options['url'],
            $options['parameters'],
            $options['files'],
            $options['server']
        );

        return $crawler;
    }

    /**
     * @return Response
     */
    protected function getClientResponse()
    {
        /* @var Response $response */
        $response = $this->client->getResponse();

        return $response;
    }

    /**
     * @param User $user
     */
    private function setRequestUserInSession(User $user)
    {
        $session = $this->container->get('session');
        $loginManager = $this->container->get('fos_user.security.login_manager');
        $firewallName = $this->container->getParameter('fos_user.firewall_name');

        $loginManager->loginUser($firewallName, $user);

        $this->container->get('session')->set(
            '_security_' . $firewallName,
            serialize($this->container->get('security.context')->getToken())
        );
        $this->container->get('session')->save();
        $this->client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));
    }
}
