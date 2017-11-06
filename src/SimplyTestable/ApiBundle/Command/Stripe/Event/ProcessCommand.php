<?php
namespace SimplyTestable\ApiBundle\Command\Stripe\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Event\Stripe\DispatchableEvent;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_EVENT_HAS_NO_USER = 3;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var EntityRepository
     */
    private $stripeEventRepository;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param EntityManager $entityManager
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     * @param EntityRepository $stripeEventRepository
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManager $entityManager,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        EntityRepository $stripeEventRepository,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->stripeEventRepository = $stripeEventRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:stripe:event:process')
            ->setDescription('Process and respond to received stripe event')
            ->addArgument('stripeId', InputArgument::REQUIRED, 'stripe id of event to process')
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

        /* @var StripeEvent $eventEntity */
        $eventEntity = $this->stripeEventRepository->findOneBy([
            'stripeId' => $input->getArgument('stripeId'),
        ]);

        $stripeEventUser = $eventEntity->getUser();

        if (empty($stripeEventUser)) {
            $this->logger->error('Stripe\Event\ProcessCommand: event has no user');

            return self::RETURN_CODE_EVENT_HAS_NO_USER;
        }

        $this->eventDispatcher->dispatch(
            'stripe_process.' . $eventEntity->getType(),
            new DispatchableEvent($eventEntity)
        );

        return self::RETURN_CODE_OK;
    }
}
