<?php

namespace Tests\ApiBundle\Factory;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
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
        $workerActivationRequestService = $this->container->get(
            'simplytestable.services.workeractivationrequestservice'
        );
        $workerRepository = $this->container->get('simplytestable.repository.worker');

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