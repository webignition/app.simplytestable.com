<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use SimplyTestable\ApiBundle\Services\WorkerService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WorkerController extends ApiController
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAction(Request $request)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $workerService = $this->container->get('simplytestable.services.workerservice');
        $workerRequestActivationService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
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

        $worker = $workerService->fetch($hostname);

        if (empty($worker)) {
            throw new BadRequestHttpException('Invalid worker hostname "' . $hostname . '"');
        }

        if (WorkerService::STATE_UNACTIVATED !== $worker->getState()->getName()) {
            return $this->sendSuccessResponse();
        }

        $activationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);
        $activationRequest = $activationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        if (empty($activationRequest)) {
            $activationRequest = $workerRequestActivationService->create($worker, $token);
        }

        $activationRequestStartingState = $stateService->fetch(WorkerActivationRequestService::STARTING_STATE);
        $activationRequest->setState($activationRequestStartingState);

        $workerRequestActivationService->persistAndFlush($activationRequest);

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'worker-activate-verify',
                ['id' => $activationRequest->getWorker()->getId()]
            )
        );

        return $this->sendSuccessResponse();
    }
}
