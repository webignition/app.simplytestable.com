<?php

namespace App\Tests\Services;

use App\Repository\JobRepository;
use App\Services\Resque\QueueService;
use App\Tests\Factory\HttpFixtureFactory;
use App\Tests\Factory\SitemapFixtureFactory;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mockery\Mock;
use App\Controller\Job\JobController;
use App\Entity\Account\Plan\Constraint;
use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Entity\Worker;
use App\Services\ApplicationStateService;
use App\Services\CrawlJobContainerService;
use App\Services\HttpClientService;
use App\Services\Job\StartService;
use App\Services\JobConfigurationFactory;
use App\Services\JobPreparationService;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\Request\Factory\Job\StartRequestFactory;
use App\Services\StateService;
use App\Services\TaskTypeDomainsToIgnoreService;
use App\Services\UserService;
use App\Services\WebSiteService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class JobFactory
{
    const DEFAULT_TYPE = JobTypeService::FULL_SITE_NAME;
    const DEFAULT_SITE_ROOT_URL = 'http://example.com';
    const DEFAULT_DOMAIN = 'example.com';

    const KEY_TYPE = 'type';
    const KEY_URL = 'url';
    const KEY_TEST_TYPES = 'testTypes';
    const KEY_TEST_TYPE_OPTIONS = 'testTypeOptions';
    const KEY_PARAMETERS = 'parameters';
    const KEY_USER = 'user';
    const KEY_STATE = 'state';
    const KEY_TIME_PERIOD_START = 'time-period-start';
    const KEY_TIME_PERIOD_END = 'time-period-end';
    const KEY_DOMAIN = 'domain';
    const KEY_AMMENDMENTS = 'ammendments';
    const KEY_TASKS = 'tasks';
    const KEY_TASK_STATE = 'state';
    const KEY_TASK_WORKER_HOSTNAME = 'worker-hostname';
    const KEY_SET_PUBLIC = 'set-public';
    const KEY_TASK_REMOTE_ID = 'remote-id';

    /**
     * @var array
     */
    private $defaultJobValues = [
        self::KEY_TYPE => self::DEFAULT_TYPE,
        self::KEY_URL => self::DEFAULT_SITE_ROOT_URL,
        self::KEY_TEST_TYPES => ['html validation'],
        self::KEY_TEST_TYPE_OPTIONS => [],
        self::KEY_PARAMETERS => [],
        self::KEY_USER => null,
    ];

    private $stateService;
    private $entityManager;
    private $websiteService;
    private $jobStartService;
    private $tokenStorage;
    private $jobConfigurationFactory;
    private $jobTypeService;
    private $testJobAmmendmentFactory;
    private $jobPreparationService;
    private $jobService;
    private $jobController;
    private $applicationStateService;
    private $crawlJobContainerService;
    private $resqueQueueService;
    private $taskTypeDomainsToIgnoreService;
    private $jobRepository;

    /* @var TestHttpClientService */
    private $httpClientService;

    public function __construct(
        UserService $userService,
        StateService $stateService,
        EntityManagerInterface $entityManager,
        WebSiteService $webSiteService,
        StartService $jobStartService,
        TokenStorageInterface $tokenStorage,
        JobConfigurationFactory $jobConfigurationFactory,
        JobTypeService $jobTypeService,
        JobAmmendmentFactory $testJobAmmendmentFactory,
        HttpClientService $httpClientService,
        JobPreparationService $jobPreparationService,
        JobService $jobService,
        JobController $jobController,
        ApplicationStateService $applicationStateService,
        CrawlJobContainerService $crawlJobContainerService,
        QueueService $resqueQueueService,
        TaskTypeDomainsToIgnoreService $taskTypeDomainsToIgnoreService,
        JobRepository $jobRepository
    ) {
        $this->defaultJobValues[self::KEY_USER] = $userService->getPublicUser();
        $this->stateService = $stateService;
        $this->entityManager = $entityManager;
        $this->websiteService = $webSiteService;
        $this->jobStartService = $jobStartService;
        $this->tokenStorage = $tokenStorage;
        $this->jobConfigurationFactory = $jobConfigurationFactory;
        $this->jobTypeService = $jobTypeService;
        $this->testJobAmmendmentFactory = $testJobAmmendmentFactory;
        $this->httpClientService = $httpClientService;
        $this->jobPreparationService = $jobPreparationService;
        $this->jobService = $jobService;
        $this->jobController = $jobController;
        $this->applicationStateService = $applicationStateService;
        $this->crawlJobContainerService = $crawlJobContainerService;
        $this->resqueQueueService = $resqueQueueService;
        $this->taskTypeDomainsToIgnoreService = $taskTypeDomainsToIgnoreService;
        $this->jobRepository = $jobRepository;
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
        $workerRepository = $this->entityManager->getRepository(Worker::class);

        $ignoreState = true;

        $job = $this->create($jobValues, $ignoreState);

        $this->resolve($job);
        $this->prepare($job, (isset($httpFixtures['prepare']) ? $httpFixtures['prepare'] : null), $domain);

        if (isset($jobValues[self::KEY_STATE])) {
            $job->setState($this->stateService->get($jobValues[self::KEY_STATE]));

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        if (isset($jobValues[self::KEY_TASKS])) {
            $taskValuesCollection = $jobValues[self::KEY_TASKS];

            /* @var Task[] $tasks */
            $tasks = $job->getTasks();

            foreach ($tasks as $taskIndex => $task) {
                $taskIsUpdated = false;

                if (isset($taskValuesCollection[$taskIndex])) {
                    $taskValues = $taskValuesCollection[$taskIndex];

                    if (isset($taskValues[self::KEY_TASK_STATE])) {
                        $stateName = $taskValues[self::KEY_TASK_STATE];
                        $task->setState($this->stateService->get($stateName));
                        $taskIsUpdated = true;
                    }

                    if (isset($taskValues[self::KEY_TASK_WORKER_HOSTNAME])) {
                        $workerHostname = $taskValues[self::KEY_TASK_WORKER_HOSTNAME];
                        $worker = $workerRepository->findOneBy([
                            'hostname' => $workerHostname,
                        ]);

                        $task->setWorker($worker);
                        $taskIsUpdated = true;
                    }

                    if (isset($taskValues[self::KEY_TASK_REMOTE_ID])) {
                        $remoteId = $taskValues[self::KEY_TASK_REMOTE_ID];

                        $task->setRemoteId($remoteId);
                        $taskIsUpdated = true;
                    }

                    if ($taskIsUpdated) {
                        $this->entityManager->persist($task);
                        $this->entityManager->flush();
                    }
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
        foreach ($this->defaultJobValues as $key => $value) {
            if (!isset($jobValues[$key])) {
                $jobValues[$key] = $value;
            }
        }

        /* @var Mock|TokenInterface $token */
        $token = \Mockery::mock(TokenInterface::class);
        $token
            ->shouldReceive('getUser')
            ->andReturn($jobValues[self::KEY_USER]);

        $this->tokenStorage->setToken($token);

        $request = new Request([], [
            'url' => $jobValues[self::KEY_URL],
            'type' => $jobValues[self::KEY_TYPE],
            'test-types' => $jobValues[self::KEY_TEST_TYPES],
            'test-type-options' => $jobValues[self::KEY_TEST_TYPE_OPTIONS],
            'parameters' => $jobValues[self::KEY_PARAMETERS],
        ]);

        $jobStartRequestFactory = new StartRequestFactory(
            $this->tokenStorage,
            $this->entityManager,
            $this->websiteService,
            $this->jobTypeService
        );

        $jobStartRequest = $jobStartRequestFactory->create($request);
        $jobConfiguration = $this->jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        $job = $this->jobStartService->start($jobConfiguration);

        if (isset($jobValues[self::KEY_STATE]) && !$ignoreState) {
            $state = $this->stateService->get($jobValues[self::KEY_STATE]);
            $job->setState($state);

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        if (isset($jobValues[self::KEY_TIME_PERIOD_START]) && isset($jobValues[self::KEY_TIME_PERIOD_END])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_START]));
            $timePeriod->setEndDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_END]));

            $job->setTimePeriod($timePeriod);

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        if (isset($jobValues[self::KEY_AMMENDMENTS])) {
            $ammendmentValuesCollection = $jobValues[self::KEY_AMMENDMENTS];

            foreach ($ammendmentValuesCollection as $ammendmentValues) {
                $ammendmentValues[JobAmmendmentFactory::KEY_JOB] = $job;

                $this->testJobAmmendmentFactory->create($ammendmentValues);
            }
        }

        if (isset($jobValues[self::KEY_SET_PUBLIC]) && $jobValues[self::KEY_SET_PUBLIC]) {
            $job->setIsPublic(true);

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }

        return $job;
    }

    /**
     * @param Job $job
     */
    public function resolve(Job $job)
    {
        $jobResolvedState = $this->stateService->get(Job::STATE_RESOLVED);

        $job->setState($jobResolvedState);

        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @param Job $job
     * @param array $httpFixtures
     * @param string|null $domain
     */
    public function prepare(Job $job, $httpFixtures = [], $domain = null)
    {
        if (empty($httpFixtures)) {
            $httpFixtures = [
                new GuzzleResponse(200, ['content-type' => 'text/plain'], 'sitemap: sitemap.xml'),
                new GuzzleResponse(
                    200,
                    ['content-type' => 'text/plain'],
                    SitemapFixtureFactory::load('example.com-three-urls', $domain)
                ),
            ];
        }

        $this->httpClientService->appendFixtures($httpFixtures);
        $this->jobPreparationService->prepare($job);
    }

    /**
     * @param Job $job
     * @param State $state
     */
    public function setTaskStates(Job $job, State $state)
    {
        foreach ($job->getTasks() as $task) {
            $task->setState($state);

            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Job $job
     * @param string $reason
     * @param Constraint|null $constraint
     */
    public function reject(Job $job, $reason, $constraint = null)
    {
        $this->jobService->reject($job, $reason, $constraint);
    }

    /**
     * @param Job $job
     */
    public function save(Job $job)
    {
        $this->entityManager->persist($job);
        $this->entityManager->flush();
    }

    /**
     * @param Job $job
     */
    public function cancel(Job $job)
    {
        $this->jobController->cancelAction(
            $this->applicationStateService,
            $this->jobService,
            $this->crawlJobContainerService,
            $this->jobPreparationService,
            $this->resqueQueueService,
            $this->stateService,
            $this->taskTypeDomainsToIgnoreService,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
    }

    /**
     * @param Response $response
     *
     * @return Job
     */
    public function getFromResponse(Response $response)
    {
        $locationHeader = $response->headers->get('location');
        $locationHeaderParts = explode('/', rtrim($locationHeader, '/'));

        /* @var Job $job */
        $job = $this->jobRepository->find((int)$locationHeaderParts[count($locationHeaderParts) - 1]);

        return $job;
    }
}
