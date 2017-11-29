<?php
namespace SimplyTestable\ApiBundle\Services\Worker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlService;
use SimplyTestable\ApiBundle\Entity\Worker;
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
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var UrlService
     */
    protected $urlService;

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
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
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
            $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/tasks/notify/');
            $request = $this->httpClientService->postRequest($requestUrl);

            try {
                $this->httpClientService->get()->send($request);
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
