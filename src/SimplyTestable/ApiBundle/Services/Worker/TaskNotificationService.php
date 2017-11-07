<?php
namespace SimplyTestable\ApiBundle\Services\Worker;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlService;
use SimplyTestable\ApiBundle\Entity\Worker;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use \Psr\Log\LoggerInterface;

class TaskNotificationService
{
    /**
     * @var EntityManager
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
     * @param EntityManager $entityManager
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     * @param LoggerInterface $logger
     * @param EntityRepository $workerRepository
     */
    public function __construct(
        EntityManager $entityManager,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        LoggerInterface $logger,
        EntityRepository $workerRepository
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
        $this->logger = $logger;
        $this->workerRepository = $workerRepository;
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
                $request->send();
            } catch (ClientErrorResponseException $clientErrorResponseException) {
                $this->logger->error(sprintf(
                    'TaskNotificationService:notifyWorker:ClientErrorResponseException [%s] [%s]',
                    $clientErrorResponseException->getResponse()->getStatusCode(),
                    $worker->getHostname()
                ));
            } catch (ServerErrorResponseException $serverErrorResponseException) {
                $this->logger->error(sprintf(
                    'TaskNotificationService:notifyWorker:ServerErrorResponseException [%s] [%s]',
                    $serverErrorResponseException->getResponse()->getStatusCode(),
                    $worker->getHostname()

                ));
            } catch (CurlException $curlException) {
                $this->logger->error(sprintf(
                    'TaskNotificationService:notifyWorker:CurlException [%s] [%s]',
                    $curlException->getErrorNo(),
                    $worker->getHostname()
                ));
            }
        }
    }
}
