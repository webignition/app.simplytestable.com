<?php

namespace App\Tests\Services;

use App\Entity\Worker;
use App\Entity\WorkerActivationRequest;
use App\Services\WorkerActivationRequestService;
use Doctrine\ORM\EntityManagerInterface;

class WorkerActivationRequestFactory
{
    const KEY_HOSTNAME = 'hostname';
    const KEY_TOKEN = 'token';

    private $workerActivationRequestService;
    private $entityManager;

    public function __construct(
        WorkerActivationRequestService $workerActivationRequestService,
        EntityManagerInterface $entityManager
    ) {
        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $workerActivationRequestValues
     *
     * @return WorkerActivationRequest
     */
    public function create($workerActivationRequestValues)
    {
        $workerRepository = $this->entityManager->getRepository(Worker::class);

        /* @var Worker $worker */
        $worker = $workerRepository->findOneBy([
            'hostname' => $workerActivationRequestValues[self::KEY_HOSTNAME],
        ]);

        $workerActivationRequest = $this->workerActivationRequestService->create(
            $worker,
            $workerActivationRequestValues[self::KEY_TOKEN]
        );

        return $workerActivationRequest;
    }
}
