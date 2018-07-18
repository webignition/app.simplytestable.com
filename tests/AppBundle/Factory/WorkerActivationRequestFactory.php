<?php

namespace Tests\AppBundle\Factory;

use AppBundle\Entity\Worker;
use AppBundle\Entity\WorkerActivationRequest;
use AppBundle\Services\WorkerActivationRequestService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkerActivationRequestFactory
{
    const KEY_HOSTNAME = 'hostname';
    const KEY_TOKEN = 'token';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $workerActivationRequestValues
     *
     * @return WorkerActivationRequest
     */
    public function create($workerActivationRequestValues)
    {
        $workerActivationRequestService = $this->container->get(WorkerActivationRequestService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $workerRepository = $entityManager->getRepository(Worker::class);

        /* @var Worker $worker */
        $worker = $workerRepository->findOneBy([
            'hostname' => $workerActivationRequestValues[self::KEY_HOSTNAME],
        ]);

        $workerActivationRequest = $workerActivationRequestService->create(
            $worker,
            $workerActivationRequestValues[self::KEY_TOKEN]
        );

        return $workerActivationRequest;
    }
}
