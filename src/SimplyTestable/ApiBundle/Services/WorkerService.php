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
}
