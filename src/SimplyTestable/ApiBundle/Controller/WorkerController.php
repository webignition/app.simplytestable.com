<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $workerRequestActivationService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );
        $stateService = $this->container->get('simplytestable.services.stateservice');

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

        $workerRepository = $entityManager->getRepository(Worker::class);
        $worker = $workerRepository->findOneBy([
            'hostname' => $hostname,
        ]);

        if (empty($worker)) {
            $worker = new Worker();
            $worker->setHostname($hostname);
            $worker->setState($stateService->fetch(Worker::STATE_UNACTIVATED));

            $entityManager->persist($worker);
            $entityManager->flush($worker);
        }

        if (Worker::STATE_UNACTIVATED !== $worker->getState()->getName()) {
            return new Response();
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

        return new Response();
    }
}
