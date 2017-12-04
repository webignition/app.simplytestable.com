<?php

namespace SimplyTestable\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WorkerController
{
    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var ResqueJobFactory
     */
    private $resqueJobFactory;

    /**
     * @var WorkerActivationRequestService
     */
    private $workerActivationRequestService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param StateService $stateService
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory,
        WorkerActivationRequestService $workerActivationRequestService,
        StateService $stateService
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->resqueQueueService = $resqueQueueService;
        $this->resqueJobFactory = $resqueJobFactory;
        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->stateService = $stateService;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAction(Request $request)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $requestData = $request->request;

        $hostname = trim($requestData->get('hostname'));

        if (empty($hostname)) {
            throw new BadRequestHttpException('"hostname" missing');
        }

        $token = trim($requestData->get('token'));

        if (empty($token)) {
            throw new BadRequestHttpException('"token" missing');
        }

        $workerRepository = $this->entityManager->getRepository(Worker::class);
        $activationRequestRepository = $this->entityManager->getRepository(WorkerActivationRequest::class);

        $worker = $workerRepository->findOneBy([
            'hostname' => $hostname,
        ]);

        if (empty($worker)) {
            $worker = new Worker();
            $worker->setHostname($hostname);
            $worker->setState($this->stateService->get(Worker::STATE_UNACTIVATED));

            $this->entityManager->persist($worker);
            $this->entityManager->flush();
        }

        if (Worker::STATE_UNACTIVATED !== $worker->getState()->getName()) {
            return new Response();
        }

        $activationRequest = $activationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        if (empty($activationRequest)) {
            $activationRequest = $this->workerActivationRequestService->create($worker, $token);
        }

        $activationRequestStartingState = $this->stateService->get(WorkerActivationRequest::STATE_STARTING);
        $activationRequest->setState($activationRequestStartingState);

        $this->entityManager->persist($activationRequest);
        $this->entityManager->flush();

        $this->resqueQueueService->enqueue(
            $this->resqueJobFactory->create(
                'worker-activate-verify',
                ['id' => $activationRequest->getWorker()->getId()]
            )
        );

        return new Response();
    }
}
