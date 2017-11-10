<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class WorkerController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAction(Request $request)
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueJobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
        $workerRequestActivationService = $this->container->get(WorkerActivationRequestService::class);
        $stateService = $this->container->get(StateService::class);
        $workerRepository = $entityManager->getRepository(Worker::class);
        $activationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);

        if ($applicationStateService->isInReadOnlyMode()) {
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

        $worker = $workerRepository->findOneBy([
            'hostname' => $hostname,
        ]);

        if (empty($worker)) {
            $worker = new Worker();
            $worker->setHostname($hostname);
            $worker->setState($stateService->get(Worker::STATE_UNACTIVATED));

            $entityManager->persist($worker);
            $entityManager->flush($worker);
        }

        if (Worker::STATE_UNACTIVATED !== $worker->getState()->getName()) {
            return new Response();
        }

        $activationRequest = $activationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        if (empty($activationRequest)) {
            $activationRequest = $workerRequestActivationService->create($worker, $token);
        }

        $activationRequestStartingState = $stateService->get(WorkerActivationRequestService::STARTING_STATE);
        $activationRequest->setState($activationRequestStartingState);

        $entityManager->persist($activationRequest);
        $entityManager->flush();

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'worker-activate-verify',
                ['id' => $activationRequest->getWorker()->getId()]
            )
        );

        return new Response();
    }
}
