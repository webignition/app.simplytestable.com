<?php
namespace App\Command\Stripe\Event;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Stripe\Event as StripeEvent;
use App\Entity\Stripe\Event;
use App\Event\Stripe\DispatchableEvent;
use App\Services\ApplicationStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessCommand extends Command
{
    const NAME = 'simplytestable:stripe:event:process';

    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;
    const RETURN_CODE_EVENT_HAS_NO_USER = 3;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var EntityManagerInterface
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
     * @param ApplicationStateService $applicationStateService
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Process and respond to received stripe event')
            ->addArgument('stripeId', InputArgument::REQUIRED, 'stripe id of event to process')
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

        $stripeEventRepository = $this->entityManager->getRepository(Event::class);

        /* @var StripeEvent $eventEntity */
        $eventEntity = $stripeEventRepository->findOneBy([
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
