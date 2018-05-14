<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use webignition\GuzzleHttp\Exception\CurlException\Factory as GuzzleCurlExceptionFactory;

class WorkerActivationRequestService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var HttpClientService
     */
    private $fooHttpClientService;

    /**
     * @var UrlService $urlService
     */
    private $urlService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param HttpClientService $fooHttpClientService
     * @param UrlService $urlService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        StateService $stateService,
        HttpClientService $fooHttpClientService,
        UrlService $urlService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->fooHttpClientService = $fooHttpClientService;
        $this->urlService = $urlService;
    }

    /**
     * @param WorkerActivationRequest $activationRequest
     *
     * @return boolean
     */
    public function verify(WorkerActivationRequest $activationRequest)
    {
        $this->logger->info("WorkerActivationRequestService::verify: Initialising");

        $requestUrl = $this->urlService->prepare(
            'http://' . $activationRequest->getWorker()->getHostname() . '/verify/'
        );

        $httpRequest = $this->fooHttpClientService->postRequest($requestUrl, [
            'body' => [
                'hostname' => $activationRequest->getWorker()->getHostname(),
                'token' => $activationRequest->getToken()
            ],
        ]);

        $this->logger->info("WorkerActivationRequestService::verify: Requesting verification with " . $requestUrl);

        try {
            $response = $this->fooHttpClientService->get()->send($httpRequest);
        } catch (ConnectException $connectException) {
            $curlException = GuzzleCurlExceptionFactory::fromConnectException($connectException);

            $this->logger->info(sprintf(
                'WorkerActivationRequestService::verify %s: %s %s',
                $requestUrl,
                $curlException->getCurlCode(),
                $curlException->getMessage()
            ));

            return false;
        } catch (BadResponseException $badResponseException) {
            $response = $badResponseException->getResponse();

            $this->logger->info(sprintf(
                'WorkerActivationRequestService::verify %s: %s %s',
                $requestUrl,
                $badResponseException->getResponse()->getStatusCode(),
                $badResponseException->getResponse()->getReasonPhrase()
            ));
        }

        $this->logger->info(sprintf(
            "WorkerActivationRequestService::verify: %s: %s %s",
            $requestUrl,
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));

        if ($response->getStatusCode() !== 200) {
            if ($response->getStatusCode() === 503) {
                $this->logger->info(sprintf(
                    'WorkerActivationRequestService::verify: Worker at %s is in read-only mode',
                    $activationRequest->getWorker()->getHostname()
                ));
            }

            $this->logger->error("WorkerActivationRequestService::verify: Activation request failed");

            return $response->getStatusCode();
        }

        $activationRequest->setNextState();

        $this->entityManager->persist($activationRequest);
        $this->entityManager->flush();

        $worker = $activationRequest->getWorker();

        $worker->setState($this->stateService->get('worker-active'));

        $this->entityManager->persist($worker);
        $this->entityManager->flush();

        return 0;
    }

    /**
     * @param Worker $worker
     * @param string $token
     *
     * @return WorkerActivationRequest
     */
    public function create(Worker $worker, $token)
    {
        $state = $this->stateService->get(WorkerActivationRequest::STATE_STARTING);

        $activationRequest = new WorkerActivationRequest();
        $activationRequest->setState($state);
        $activationRequest->setWorker($worker);
        $activationRequest->setToken($token);

        $this->entityManager->persist($activationRequest);
        $this->entityManager->flush();

        return $activationRequest;
    }
}
