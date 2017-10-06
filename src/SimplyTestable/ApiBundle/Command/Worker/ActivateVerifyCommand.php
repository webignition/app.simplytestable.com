<?php
namespace SimplyTestable\ApiBundle\Command\Worker;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\WorkerActivationRequest;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\WorkerActivationRequestService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ActivateVerifyCommand extends Command
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
     * @var WorkerActivationRequestService
     */
    private $workerActivationRequestService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        WorkerActivationRequestService $workerActivationRequestService,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->workerActivationRequestService = $workerActivationRequestService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:worker:activate:verify')
            ->setDescription('Verify the activation request of a worker')
            ->addArgument('id', InputArgument::REQUIRED, 'id of worker to verify')
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

        $workerRepository = $this->entityManager->getRepository(Worker::class);
        $workerActivationRequestRepository = $this->entityManager->getRepository(WorkerActivationRequest::class);

        $id = (int)$input->getArgument('id');

        $worker = $workerRepository->find($id);
        $activationRequest = $workerActivationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        return $this->workerActivationRequestService->verify($activationRequest);
    }
}