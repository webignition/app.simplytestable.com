<?php

namespace App\Services\Worker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use App\Services\StateService;
use App\Entity\Worker;
use \Psr\Log\LoggerInterface;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class TaskNotificationService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param StateService $stateService
     * @param HttpClient $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService,
        HttpClient $httpClient,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->httpClient = $httpClient;
        $this->logger = $logger;

        $this->workerRepository = $entityManager->getRepository(Worker::class);
    }

    public function notify()
    {
        /* @var Worker[] $workers */
        $workers = $this->workerRepository->findBy([
            'state' => $this->stateService->get(Worker::STATE_ACTIVE),
        ]);

        foreach ($workers as $worker) {
            $request = new Request('POST', 'http://' . $worker->getHostname() . '/tasks/notify/');

            try {
                $this->httpClient->send($request);
            } catch (BadResponseException $badResponseException) {
                $this->logger->error(sprintf(
                    'TaskNotificationService:notifyWorker:ServerErrorResponseException [%s] [%s]',
                    $badResponseException->getResponse()->getStatusCode(),
                    $worker->getHostname()
                ));
            } catch (ConnectException $connectException) {
                $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

                $this->logger->error(sprintf(
                    'TaskNotificationService:notifyWorker:CurlException [%s] [%s]',
                    $curlException->getCurlCode(),
                    $worker->getHostname()
                ));
            }
        }
    }
}
