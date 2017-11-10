<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManagerInterface;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class WorkerActivationRequestService
{
    const STARTING_STATE = 'worker-activation-request-awaiting-verification';
    const VERIFIED_STATE = 'worker-activation-request-verified';
    const FAILED_STATE = 'worker-activation-request-failed';

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
    private $httpClientService;

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
     * @param HttpClientService $httpClientService
     * @param UrlService $urlService
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService
    ) {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
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

        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'hostname' => $activationRequest->getWorker()->getHostname(),
            'token' => $activationRequest->getToken()
        ));

        $this->logger->info("WorkerActivationRequestService::verify: Requesting verification with " . $requestUrl);

        try {
            $response = $httpRequest->send();
        } catch (CurlException $curlException) {
            $this->logger->info(sprintf(
                'WorkerActivationRequestService::verify %s: %s %s',
                $requestUrl,
                $curlException->getErrorNo(),
                $curlException->getError()
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
        $state = $this->stateService->get(self::STARTING_STATE);

        $activationRequest = new WorkerActivationRequest();
        $activationRequest->setState($state);
        $activationRequest->setWorker($worker);
        $activationRequest->setToken($token);

        $this->entityManager->persist($activationRequest);
        $this->entityManager->flush();

        return $activationRequest;
    }
}
