<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Worker;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkerFactory
{
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
     * @param string|null $hostname
     * @param string|null $token
     *
     * @return Worker
     */
    public function create($hostname = null, $token = null)
    {
        if (is_null($hostname)) {
            $hostname = md5(time()) . '.worker.simplytestable.com';
        }

        $workerService = $this->container->get('simplytestable.services.workerservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $worker = $workerService->get($hostname);
        $worker->setToken($token);
        $workerService->persistAndFlush($worker);

        $worker->setState($stateService->fetch('worker-active'));

        $workerService->persistAndFlush($worker);

        return $worker;
    }

    /**
     * @param int $requestedWorkerCount
     *
     * @return Worker[]
     */
    public function createCollection($requestedWorkerCount)
    {
        $workers = array();

        for ($workerIndex = 0; $workerIndex < $requestedWorkerCount; $workerIndex++) {
            $workers[] = $this->create('worker'.$workerIndex.'.worker.simplytestable.com');
        }

        return $workers;
    }
}
