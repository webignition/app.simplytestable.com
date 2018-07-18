<?php
namespace App\Command\Worker;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\WorkerActivationRequest;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Worker;

class SetTokenFromActivationRequestCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 1;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            return self::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE;
        }

        $workerRepository = $this->entityManager->getRepository(Worker::class);
        $workerActivationRequestRepository = $this->entityManager->getRepository(WorkerActivationRequest::class);

        /* @var Worker[] $workers */
        $workers = $workerRepository->findAll();

        foreach ($workers as $worker) {
            $workerActivationRequest = $workerActivationRequestRepository->findOneBy([
                'worker' => $worker,
            ]);

            $workerToken = $worker->getToken();

            if (empty($workerToken) && !empty($workerActivationRequest)) {
                $worker->setToken($workerActivationRequest->getToken());

                $this->entityManager->persist($worker);
                $this->entityManager->flush();
            }
        }

        return self::RETURN_CODE_OK;
    }
}