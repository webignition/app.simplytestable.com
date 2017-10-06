<?php
namespace SimplyTestable\ApiBundle\Services\Worker;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlService;
use SimplyTestable\ApiBundle\Entity\Worker;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use \Psr\Log\LoggerInterface as Logger;
use SimplyTestable\ApiBundle\Services\WorkerService;

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
     * @var Logger
     */
    private $logger;

    /**
     * @param EntityManager $entityManager
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     * @param Logger $logger
     */
    public function __construct(
        EntityManager $entityManager,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        Logger $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
    }

    public function notify()
    {
        $workerRepository = $this->entityManager->getRepository(Worker::class);
        $workers = $workerRepository->findBy([
            'state' => $this->stateService->fetch(WorkerService::STATE_ACTIVE),
        ]);

        foreach ($workers as $worker) {
            $this->notifyWorker($worker);
        }

        return true;
    }

    /**
     * @param Worker $worker
     *
     * @return bool
     */
    private function notifyWorker(Worker $worker)
    {
        $requestUrl = $this->urlService->prepare('http://' . $worker->getHostname() . '/tasks/notify/');
        $request = $this->httpClientService->postRequest($requestUrl);

        try {
            $response = $request->send();

            if ($response->getStatusCode() !== 200) {
                if ($response->isClientError()) {
                    throw ClientErrorResponseException::factory($request, $response);
                } elseif ($response->isServerError()) {
                    throw ServerErrorResponseException::factory($request, $response);
                }
            }

            return true;
        } catch (ClientErrorResponseException $clientErrorResponseException) {
            $this->logger->error('TaskNotificationService:notifyWorker:ClientErrorResponseException [' . $clientErrorResponseException->getResponse()->getStatusCode() . '] [' . $worker->getHostname() . ']');
        } catch (ServerErrorResponseException $serverErrorResponseException) {
            $this->logger->error('TaskNotificationService:notifyWorker:ServerErrorResponseException [' . $serverErrorResponseException->getResponse()->getStatusCode() . '] [' . $worker->getHostname() . ']');
        } catch (CurlException $curlException) {
            $this->logger->error('TaskNotificationService:notifyWorker:CurlException [' . $curlException->getErrorNo() . '] [' . $worker->getHostname() . ']');
        }

        return true;
    }
}
