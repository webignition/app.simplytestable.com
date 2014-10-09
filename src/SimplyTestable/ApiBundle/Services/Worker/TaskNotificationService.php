<?php
namespace SimplyTestable\ApiBundle\Services\Worker;

use SimplyTestable\ApiBundle\Services\WorkerService;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlService;
use SimplyTestable\ApiBundle\Entity\Worker;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\ServerErrorResponseException;
use \Psr\Log\LoggerInterface as Logger;

class TaskNotificationService {

    /**
     *
     * @var WorkerService
     */
    private $workerService;


    /**
     * @var HttpClientService
     */
    private $httpClientService;


    /**
     *
     * @var UrlService
     */
    protected $urlService;


    /**
     * @var Logger
     */
    private $logger;


    public function __construct(
        WorkerService $workerService,
        HttpClientService $httpClientService,
        UrlService $urlService,
        Logger $logger
    )
    {
        $this->workerService = $workerService;
        $this->httpClientService = $httpClientService;
        $this->urlService = $urlService;
    }


    public function notify() {
        $workers = $this->workerService->getActiveCollection();

        foreach ($workers as $worker) {
            $this->notifyWorker($worker);
        }

        return true;
    }


    private function notifyWorker(Worker $worker) {
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