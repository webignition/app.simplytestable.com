<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Entity\State;

class WorkerActivationRequestService extends EntityService
{
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\WorkerActivationRequest';
    const STARTING_STATE = 'worker-activation-request-awaiting-verification';

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
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param StateService $stateService
     * @param HttpClientService $httpClientService
     * @param \SimplyTestable\ApiBundle\Services\UrlService $urlService
     */
    public function __construct(
        EntityManager $entityManager,
        LoggerInterface $logger,
        StateService $stateService,
        HttpClientService $httpClientService,
        UrlService $urlService
    ) {
        parent::__construct($entityManager);

        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return self::ENTITY_NAME;
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
        $this->persistAndFlush($activationRequest);

        $worker = $activationRequest->getWorker();

        $worker->setState($this->stateService->fetch('worker-active'));

        $this->getManager()->persist($worker);
        $this->getManager()->flush();

        return 0;
    }

    /**
     * @param Worker $worker
     *
     * @return boolean
     */
    public function has(Worker $worker)
    {
        return !is_null($this->fetch($worker));
    }

    /**
     * @param Worker $worker
     *
     * @return WorkerActivationRequest
     */
    public function fetch(Worker $worker)
    {
        return $this->getEntityRepository()->findOneBy(
            array('worker' => $worker)
        );
    }

    /**
     * @param Worker $worker
     * @param string $token
     *
     * @return WorkerActivationRequest
     */
    public function create(Worker $worker, $token)
    {
        $activationRequest = new WorkerActivationRequest();
        $activationRequest->setState($this->getStartingState());
        $activationRequest->setWorker($worker);
        $activationRequest->setToken($token);

        return $this->persistAndFlush($activationRequest);
    }

    /**
     * @param WorkerActivationRequest $workerActivationRequest
     *
     * @return WorkerActivationRequest
     */
    public function persistAndFlush(WorkerActivationRequest $workerActivationRequest)
    {
        $this->getManager()->persist($workerActivationRequest);
        $this->getManager()->flush();
        return $workerActivationRequest;
    }

    /**
     * @return State
     */
    public function getStartingState()
    {
        return $this->stateService->fetch(self::STARTING_STATE);
    }
}
