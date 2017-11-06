<?php
namespace SimplyTestable\ApiBundle\Command\Worker;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;

class SetTokenFromActivationRequestCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param EntityRepository $workerRepository
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        EntityRepository $workerRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->workerRepository = $workerRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:settokenfromactivationrequest')
            ->setDescription('Set all unset worker tokens from related activation requests')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->applicationStateService->isInMaintenanceReadOnlyState()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $workerActivationRequestRepository = $this->entityManager->getRepository(WorkerActivationRequest::class);

        /* @var Worker[] $workers */
        $workers = $this->workerRepository->findAll();

        foreach ($workers as $worker) {
            $workerActivationRequest = $workerActivationRequestRepository->findOneBy([
                'worker' => $worker,
            ]);

            $workerToken = $worker->getToken();

            if (empty($workerToken) && !empty($workerActivationRequest)) {
                $worker->setToken($workerActivationRequest->getToken());

                $this->entityManager->persist($worker);
                $this->entityManager->flush($worker);
            }
        }

        return self::RETURN_CODE_OK;
    }
}