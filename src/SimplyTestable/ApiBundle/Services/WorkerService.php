<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use Guzzle\Http\Message\Request;
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
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     *
     * @param EntityManager $entityManager
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param HttpClientService $httpClientService
     */
    public function __construct(
        EntityManager $entityManager,
        WorkerActivationRequestService $workerActivationRequestService,
        HttpClientService $httpClientService,
        StateService $stateService
    ) {
        parent::__construct($entityManager);

        $this->workerActivationRequestService = $workerActivationRequestService;
        $this->httpClientService = $httpClientService;
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
     * @param string $hostname
     *
     * @return Worker
     */
    public function get($hostname)
    {
        if (!$this->has($hostname)) {
            $this->create($hostname);
        }

        return $this->fetch($hostname);
    }

    /**
     * @return bool
     */
    public function has($hostname)
    {
        return !is_null($this->fetch($hostname));
    }

    /**
     * @return Worker
     */
    public function fetch($hostname)
    {
        return $this->getEntityRepository()->findOneByHostname($hostname);
    }

    /**
     * @return Worker
     */
    private function create($hostname)
    {
        $worker = new Worker();
        $worker->setHostname($hostname);
        return $this->persistAndFlush($worker);
    }

    /**
     * @param string $hostname
     * @param string $token
     *
     * @return bool
     */
    public function verify($hostname, $token)
    {
        $activationVerificationUrl = 'http://' . $hostname . '/activate/verify/';

        $request = new \HttpRequest($activationVerificationUrl, Request::POST);
        $request->setPostFields(array('token' => $token));

        return $this->httpClient->getResponse($request)->getResponseCode() == 200;
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
