<?php
namespace SimplyTestable\ApiBundle\Command\Worker;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
     * @var WorkerActivationRequestService
     */
    private $workerActivationRequestService;

    /**
     * @var EntityRepository
     */
    private $workerRepository;

    /**
     * @var EntityRepository
     */
    private $workerActivationRequestRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param WorkerActivationRequestService $workerActivationRequestService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        WorkerActivationRequestService $workerActivationRequestService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->workerActivationRequestService = $workerActivationRequestService;

        $this->workerRepository = $entityManager->getRepository(Worker::class);
        $this->workerActivationRequestRepository = $entityManager->getRepository(WorkerActivationRequest::class);
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

        $id = (int)$input->getArgument('id');

        $worker = $this->workerRepository->find($id);

        /* @var WorkerActivationRequest $activationRequest */
        $activationRequest = $this->workerActivationRequestRepository->findOneBy([
            'worker' => $worker,
        ]);

        return $this->workerActivationRequestService->verify($activationRequest);
    }
}
