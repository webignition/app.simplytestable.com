<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;

class WorkerService extends EntityService
{
    const STATE_ACTIVE = 'worker-active';
    const STATE_UNACTIVATED = 'worker-unactivated';
    const STATE_DELETED = 'worker-deleted';
    const STATE_OFFLINE = 'worker-offline';

    /**
     * @var WorkerActivationRequestService
     */
    private $workerActivationRequestService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     *
     * @param EntityManager $entityManager
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param StateService $stateService
     */
    public function __construct(
        EntityManager $entityManager,
        WorkerActivationRequestService $workerActivationRequestService,
        StateService $stateService
    ) {
        parent::__construct($entityManager);

        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->stateService = $stateService;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return Worker::class;
    }

    /**
     * @return Worker
     */
    public function fetch($hostname)
    {
        return $this->getEntityRepository()->findOneByHostname($hostname);
    }

    /**
     * @param string $hostname
     *
     * @return Worker
     */
    public function create($hostname)
    {
        $worker = new Worker();
        $worker->setHostname($hostname);

        return $this->persistAndFlush($worker);
    }

    /**
     * @param Worker $worker
     *
     * @return Worker
     */
    public function persistAndFlush(Worker $worker)
    {
        $this->getManager()->persist($worker);
        $this->getManager()->flush();

        return $worker;
    }


    /**
     * @return int
     */
    public function count()
    {
        $queryBuilder = $this->getEntityRepository()->createQueryBuilder('Worker');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Worker.id) as worker_total');

        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['worker_total']);
    }

    /**
     * @param Worker $worker
     *
     * @return bool
     */
    public function isActive(Worker $worker)
    {
        return $worker->getState()->equals($this->stateService->fetch('worker-active'));
    }

    /**
     * Get collection of active workers
     *
     * @return Worker[]
     */
    public function getActiveCollection()
    {
        $workers = $this->getEntityRepository()->findAll();
        $selectedWorkers = array();

        foreach ($workers as $worker) {
            if ($this->isActive($worker)) {
                $selectedWorkers[] = $worker;
            }
        }

        return $selectedWorkers;
    }
}
