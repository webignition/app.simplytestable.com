<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Adapter\Job\Configuration\Start\RequestAdapter;
use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Message\Response as GuzzleResponse;

class JobFactory
{
    const DEFAULT_TYPE = JobTypeService::FULL_SITE_NAME;
    const DEFAULT_SITE_ROOT_URL = 'http://example.com';
    const DEFAULT_DOMAIN = 'example.com';

    const KEY_TYPE = 'type';
    const KEY_SITE_ROOT_URL = 'siteRootUrl';
    const KEY_TEST_TYPES = 'testTypes';
    const KEY_TEST_TYPE_OPTIONS = 'testTypeOptions';
    const KEY_PARAMETERS = 'parameters';
    const KEY_USER = 'user';
    const KEY_STATE = 'state';
    const KEY_TASK_STATES = 'task-states';
    const KEY_TIME_PERIOD_START = 'time-period-start';
    const KEY_TIME_PERIOD_END = 'time-period-end';
    const KEY_DOMAIN = 'domain';

    /**
     * @var array
     */
    private $defaultJobValues = [
        self::KEY_TYPE => self::DEFAULT_TYPE,
        self::KEY_SITE_ROOT_URL => self::DEFAULT_SITE_ROOT_URL,
        self::KEY_TEST_TYPES => ['html validation'],
        self::KEY_TEST_TYPE_OPTIONS => [],
        self::KEY_PARAMETERS => [],
        self::KEY_USER => null,
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $userService = $container->get('simplytestable.services.userservice');
        $this->defaultJobValues[self::KEY_USER] = $userService->getPublicUser();
    }

    /**
     * @param array $jobValues
     * @param array $httpFixtures
     * @param string|null $domain
     *
     * @return Job
     */
    public function createResolveAndPrepare($jobValues = [], $httpFixtures = [], $domain = self::DEFAULT_DOMAIN)
    {
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $ignoreState = true;

        $job = $this->create($jobValues, $ignoreState);

        $this->resolve($job, (isset($httpFixtures['resolve']) ? $httpFixtures['resolve'] : null));
        $this->prepare($job, (isset($httpFixtures['prepare']) ? $httpFixtures['prepare'] : null), $domain);

        $httpClientService->getMockPlugin()->clearQueue();

        if (isset($jobValues[self::KEY_STATE])) {
            $job->setState($stateService->fetch($jobValues[self::KEY_STATE]));
            $jobService->persistAndFlush($job);
        }

        if (isset($jobValues[self::KEY_TASK_STATES])) {
            $stateNames = $jobValues[self::KEY_TASK_STATES];

            /* @var Task[] $tasks */
            $tasks = $job->getTasks();

            foreach ($tasks as $taskIndex => $task) {
                if (isset($stateNames[$taskIndex])) {
                    $stateName = $stateNames[$taskIndex];
                    $task->setState($stateService->fetch($stateName));
                    $taskService->persistAndFlush($task);
                }
            }
        }

        return $job;
    }

    /**
     * @param array $jobValuesCollection
     * @param array $httpFixturesCollection
     *
     * @return Job[]
     */
    public function createResolveAndPrepareCollection($jobValuesCollection, $httpFixturesCollection = [])
    {
        $jobs = [];

        foreach ($jobValuesCollection as $jobIndex => $jobValues) {
            $httpFixtures = isset($httpFixturesCollection[$jobIndex])
                ? $httpFixturesCollection[$jobIndex]
                : [];

            $domain = isset($jobValues[self::KEY_DOMAIN])
                ? $jobValues[self::KEY_DOMAIN]
                : self::DEFAULT_DOMAIN;

            $jobs[] = $this->createResolveAndPrepare($jobValues, $httpFixtures, $domain);
        }

        return $jobs;
    }

    /**
     * @param array $jobValues
     *
     * @return Job
     */
    public function createResolveAndPrepareStandardCrawlJob($jobValues = [])
    {
        return $this->createResolveAndPrepare($jobValues, [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);
    }

    /**
     * @param array $jobValues
     * @param bool $ignoreState
     *
     * @return Job
     */
    public function create($jobValues = [], $ignoreState = false)
    {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $jobStartService = $this->container->get('simplytestable.services.job.startservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');

        foreach ($this->defaultJobValues as $key => $value) {
            if (!isset($jobValues[$key])) {
                $jobValues[$key] = $value;
            }
        }

        $request = new Request([], [
            'type' => $jobValues[self::KEY_TYPE],
            'test-types' => $jobValues[self::KEY_TEST_TYPES],
            'test-type-options' => $jobValues[self::KEY_TEST_TYPE_OPTIONS],
            'parameters' => $jobValues[self::KEY_PARAMETERS],
        ], [
            'site_root_url' => $jobValues[self::KEY_SITE_ROOT_URL],
        ]);

        $requestAdapter = new RequestAdapter(
            $request,
            $websiteService,
            $jobTypeService,
            $taskTypeService
        );

        $jobConfiguration = $requestAdapter->getJobConfiguration();
        $jobConfiguration->setUser($jobValues[self::KEY_USER]);

        $job = $jobStartService->start($jobConfiguration);

        if (isset($jobValues[self::KEY_STATE]) && !$ignoreState) {
            $state = $stateService->fetch($jobValues[self::KEY_STATE]);
            $job->setState($state);
            $jobService->persistAndFlush($job);
        }

        if (isset($jobValues[self::KEY_TIME_PERIOD_START]) && isset($jobValues[self::KEY_TIME_PERIOD_END])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_START]));
            $timePeriod->setEndDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_END]));

            $job->setTimePeriod($timePeriod);
            $jobService->persistAndFlush($job);
        }

        return $job;
    }

    /**
     * @param Job $job
     * @param array $httpFixtures
     */
    public function resolve(Job $job, $httpFixtures = [])
    {
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $websiteResolutionService = $this->container->get('simplytestable.services.jobwebsiteresolutionservice');

        if (empty($httpFixtures)) {
            $httpFixtures = [
                GuzzleResponse::fromMessage('HTTP/1.1 200 OK'),
            ];
        }

        foreach ($httpFixtures as $fixture) {
            $httpClientService->queueFixture($fixture);
        }

        $websiteResolutionService->resolve($job);
    }

    /**
     * @param Job $job
     * @param array $httpFixtures
     * @param string|null $domain
     */
    public function prepare(Job $job, $httpFixtures = [], $domain = null)
    {
        $httpClientService = $this->container->get('simplytestable.services.httpclientservice');
        $jobPreparationService = $this->container->get('simplytestable.services.jobpreparationservice');

        if (empty($httpFixtures)) {
            $httpFixtures = [
                GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml"),
                GuzzleResponse::fromMessage(sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
                    SitemapFixtureFactory::load('example.com-three-urls', $domain)
                )),
            ];
        }

        foreach ($httpFixtures as $fixture) {
            $httpClientService->queueFixture($fixture);
        }

        $jobPreparationService->prepare($job);
    }

    /**
     * @param Job $job
     * @param State $state
     */
    public function setTaskStates(Job $job, State $state)
    {
        $taskService = $this->container->get('simplytestable.services.taskservice');

        foreach ($job->getTasks() as $task) {
            $task->setState($state);
            $taskService->persistAndFlush($task);
        }
    }

    /**
     * @param Job $job
     * @param string $reason
     * @param Constraint $constraint
     */
    public function reject(Job $job, $reason, Constraint $constraint)
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');

        $jobService->reject($job, $reason, $constraint);
    }

    /**
     * @param Job $job
     */
    public function save(Job $job)
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $jobService->persistAndFlush($job);
    }

    /**
     * @param Job $job
     */
    public function cancel(Job $job)
    {
        $jobController = new JobController();
        $jobController->setContainer($this->container);
        $jobController->cancelAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
    }
}
