<?php

namespace App\Tests\Factory;

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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Tests\Services\TestHttpClientService;
use App\Services\Resque\QueueService as ResqueQueueService;

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

        $userService = $container->get(UserService::class);
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
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $workerRepository = $entityManager->getRepository(Worker::class);

        $ignoreState = true;

        $job = $this->create($jobValues, $ignoreState);

        $this->resolve($job);
        $this->prepare($job, (isset($httpFixtures['prepare']) ? $httpFixtures['prepare'] : null), $domain);

        if (isset($jobValues[self::KEY_STATE])) {
            $job->setState($stateService->get($jobValues[self::KEY_STATE]));

            $entityManager->persist($job);
            $entityManager->flush();
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
                        $task->setState($stateService->get($stateName));
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
                        $entityManager->persist($task);
                        $entityManager->flush();
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
        $websiteService = $this->container->get(WebSiteService::class);
        $jobStartService = $this->container->get(StartService::class);
        $stateService = $this->container->get(StateService::class);
        $tokenStorage = $this->container->get('security.token_storage');
        $jobConfigurationFactory = $this->container->get(JobConfigurationFactory::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobTypeService = $this->container->get(JobTypeService::class);

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

        $tokenStorage->setToken($token);

        $request = new Request([], [
            'type' => $jobValues[self::KEY_TYPE],
            'test-types' => $jobValues[self::KEY_TEST_TYPES],
            'test-type-options' => $jobValues[self::KEY_TEST_TYPE_OPTIONS],
            'parameters' => $jobValues[self::KEY_PARAMETERS],
        ], [
            'site_root_url' => $jobValues[self::KEY_SITE_ROOT_URL],
        ]);

        $jobStartRequestFactory = new StartRequestFactory(
            $this->container->get('security.token_storage'),
            $this->container->get('doctrine.orm.entity_manager'),
            $websiteService,
            $jobTypeService
        );

        $jobStartRequest = $jobStartRequestFactory->create($request);
        $jobConfiguration = $jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        $job = $jobStartService->start($jobConfiguration);

        if (isset($jobValues[self::KEY_STATE]) && !$ignoreState) {
            $state = $stateService->get($jobValues[self::KEY_STATE]);
            $job->setState($state);

            $entityManager->persist($job);
            $entityManager->flush();
        }

        if (isset($jobValues[self::KEY_TIME_PERIOD_START]) && isset($jobValues[self::KEY_TIME_PERIOD_END])) {
            $timePeriod = new TimePeriod();
            $timePeriod->setStartDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_START]));
            $timePeriod->setEndDateTime(new \DateTime($jobValues[self::KEY_TIME_PERIOD_END]));

            $job->setTimePeriod($timePeriod);

            $entityManager->persist($job);
            $entityManager->flush();
        }

        if (isset($jobValues[self::KEY_AMMENDMENTS])) {
            $ammendmentFactory = new JobAmmendmentFactory($this->container);

            $ammendmentValuesCollection = $jobValues[self::KEY_AMMENDMENTS];

            foreach ($ammendmentValuesCollection as $ammendmentValues) {
                $ammendmentValues[JobAmmendmentFactory::KEY_JOB] = $job;

                $ammendmentFactory->create($ammendmentValues);
            }
        }

        if (isset($jobValues[self::KEY_SET_PUBLIC]) && $jobValues[self::KEY_SET_PUBLIC]) {
            $job->setIsPublic(true);

            $entityManager->persist($job);
            $entityManager->flush();
        }

        return $job;
    }

    /**
     * @param Job $job
     */
    public function resolve(Job $job)
    {
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $jobResolvedState = $stateService->get(Job::STATE_RESOLVED);

        $job->setState($jobResolvedState);

        $entityManager->persist($job);
        $entityManager->flush();
    }

    /**
     * @param Job $job
     * @param array $httpFixtures
     * @param string|null $domain
     */
    public function prepare(Job $job, $httpFixtures = [], $domain = null)
    {
        /* @var TestHttpClientService $httpClientService */
        $httpClientService = $this->container->get(HttpClientService::class);
        $jobPreparationService = $this->container->get(JobPreparationService::class);

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

        $httpClientService->appendFixtures($httpFixtures);
        $jobPreparationService->prepare($job);
    }

    /**
     * @param Job $job
     * @param State $state
     */
    public function setTaskStates(Job $job, State $state)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        foreach ($job->getTasks() as $task) {
            $task->setState($state);

            $entityManager->persist($task);
            $entityManager->flush();
        }
    }

    /**
     * @param Job $job
     * @param string $reason
     * @param Constraint|null $constraint
     */
    public function reject(Job $job, $reason, $constraint = null)
    {
        $jobService = $this->container->get(JobService::class);

        $jobService->reject($job, $reason, $constraint);
    }

    /**
     * @param Job $job
     */
    public function save(Job $job)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $entityManager->persist($job);
        $entityManager->flush();
    }

    /**
     * @param Job $job
     */
    public function cancel(Job $job)
    {
        $jobController = $this->container->get(JobController::class);

        $jobController->cancelAction(
            $this->container->get(ApplicationStateService::class),
            $this->container->get(JobService::class),
            $this->container->get(CrawlJobContainerService::class),
            $this->container->get(JobPreparationService::class),
            $this->container->get(ResqueQueueService::class),
            $this->container->get(StateService::class),
            $this->container->get(TaskTypeDomainsToIgnoreService::class),
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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobRepository = $entityManager->getRepository(Job::class);

        $locationHeader = $response->headers->get('location');
        $locationHeaderParts = explode('/', rtrim($locationHeader, '/'));

        return $jobRepository->find((int)$locationHeaderParts[count($locationHeaderParts) - 1]);
    }
}
