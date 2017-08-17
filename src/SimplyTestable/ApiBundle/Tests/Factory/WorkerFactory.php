<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use SimplyTestable\ApiBundle\Entity\Worker;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WorkerFactory
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
     * @param array $workerValues
     * @return Worker
     */
    public function create($workerValues = [])
    {
        if (!isset($workerValues[self::KEY_HOSTNAME])) {
            $workerValues[self::KEY_HOSTNAME] = md5(time()) . '.worker.simplytestable.com';
        }

        if (!isset($workerValues[self::KEY_TOKEN])) {
            $workerValues[self::KEY_TOKEN] = null;
        }

        $workerService = $this->container->get('simplytestable.services.workerservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');

        $worker = $workerService->get($workerValues[self::KEY_HOSTNAME]);
        $worker->setToken($workerValues[self::KEY_TOKEN]);
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
            $workers[] = $this->create([
                self::KEY_HOSTNAME => 'worker'.$workerIndex.'.worker.simplytestable.com',
            ]);
        }

        return $workers;
    }
}
