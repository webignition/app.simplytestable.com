<?php

namespace Tests\ApiBundle\Factory;

use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\TimePeriod;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Job\StartRequestFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Guzzle\Http\Message\Response as GuzzleResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

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
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $workerRepository = $this->container->get('simplytestable.repository.worker');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $ignoreState = true;

        $job = $this->create($jobValues, $ignoreState);

        $this->resolve($job, (isset($httpFixtures['resolve']) ? $httpFixtures['resolve'] : null));
        $this->prepare($job, (isset($httpFixtures['prepare']) ? $httpFixtures['prepare'] : null), $domain);

        $httpClientService->getMockPlugin()->clearQueue();

        if (isset($jobValues[self::KEY_STATE])) {
            $job->setState($stateService->fetch($jobValues[self::KEY_STATE]));

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
                        $task->setState($stateService->fetch($stateName));
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

                    if ($taskIsUpdated) {
                        $taskService->persistAndFlush($task);
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
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $jobStartService = $this->container->get('simplytestable.services.job.startservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $tokenStorage = $this->container->get('security.token_storage');
        $jobConfigurationFactory = $this->container->get('simplytestable.services.jobconfiguration.factory');
        $taskTypeRepository = $this->container->get('simplytestable.repository.tasktype');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

        foreach ($this->defaultJobValues as $key => $value) {
            if (!isset($jobValues[$key])) {
                $jobValues[$key] = $value;
            }
        }

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

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $jobStartRequestFactory = new StartRequestFactory(
            $requestStack,
            $this->container->get('security.token_storage'),
            $this->container->get('doctrine.orm.entity_manager'),
            $websiteService,
            $taskTypeRepository,
            $jobTypeService
        );

        $jobStartRequest = $jobStartRequestFactory->create();
        $jobConfiguration = $jobConfigurationFactory->createFromJobStartRequest($jobStartRequest);

        $job = $jobStartService->start($jobConfiguration);

        if (isset($jobValues[self::KEY_STATE]) && !$ignoreState) {
            $state = $stateService->fetch($jobValues[self::KEY_STATE]);
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
     * @param Constraint|null $constraint
     */
    public function reject(Job $job, $reason, $constraint = null)
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');

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
        $jobController = new JobController();
        $jobController->setContainer($this->container);
        $jobController->cancelAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
    }

    /**
     * @param Response $response
     *
     * @return Job
     */
    public function getFromResponse(Response $response)
    {
        $jobRepository = $this->container->get('simplytestable.repository.job');

        $locationHeader = $response->headers->get('location');
        $locationHeaderParts = explode('/', rtrim($locationHeader, '/'));

        return $jobRepository->find((int)$locationHeaderParts[count($locationHeaderParts) - 1]);
    }
}
