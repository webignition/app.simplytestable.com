<?php

namespace App\Tests\Services;

use App\Entity\Worker;
use App\Services\StateService;
use Doctrine\ORM\EntityManagerInterface;

class WorkerFactory
{
    const KEY_HOSTNAME = 'hostname';
    const KEY_TOKEN = 'token';
    const KEY_STATE = 'state';

    private $entityManager;
    private $stateService;

    public function __construct(EntityManagerInterface $entityManager, StateService $stateService)
    {
        $this->entityManager = $entityManager;
        $this->stateService = $stateService;
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

        if (!array_key_exists(self::KEY_TOKEN, $workerValues)) {
            $workerValues[self::KEY_TOKEN] = md5(microtime(true));
        }

        if (!array_key_exists(self::KEY_STATE, $workerValues)) {
            $workerValues[self::KEY_STATE] = 'worker-active';
        }

        $workerRepository = $this->entityManager->getRepository(Worker::class);

        /* @var Worker $worker */
        $worker = $workerRepository->findOneBy([
            'hostname' => $workerValues[self::KEY_HOSTNAME],
        ]);

        if (empty($worker)) {
            $worker = new Worker();
            $worker->setHostname($workerValues[self::KEY_HOSTNAME]);
        }

        $worker->setToken($workerValues[self::KEY_TOKEN]);
        $worker->setState($this->stateService->get($workerValues[self::KEY_STATE]));

        $this->entityManager->persist($worker);
        $this->entityManager->flush();

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
