<?php
namespace SimplyTestable\ApiBundle\Command\Stripe\Event;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Stripe\Event;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\StripeEventService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class UpdateDataCommand extends Command
{
    const RETURN_CODE_OK = 0;
    const RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE = 2;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var StripeEventService
     */
    private $stripeEventService;

    /**
     * @var string
     */
    private $stripeKey;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param StripeEventService $stripeEventService
     * @param EntityManagerInterface $entityManager
     * @param string $stripeKey
     * @param string|null $name
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        StripeEventService $stripeEventService,
        EntityManagerInterface $entityManager,
        $stripeKey,
        $name = null
    ) {
        parent::__construct($name);

        $this->applicationStateService = $applicationStateService;
        $this->stripeEventService = $stripeEventService;
        $this->stripeKey = $stripeKey;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:stripe:event:updatedata')
            ->setDescription('Retrieve all stripe event data from stripe and refresh local cache')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run through the process without writing any data'
            )
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

        $isDryRun = $input->getOption('dry-run') == 'true';

        if ($isDryRun) {
            $output->writeln('<comment>This is a DRY RUN, no data will be written</comment>');
        }

        $events = $this->stripeEventService->getEntityRepository()->findAll();

        foreach ($events as $event) {
            /* @var Event $event */
            $output->write('Retrieving ' . $event->getStripeId() . ' ... ');

            $cliCommand = sprintf(
                'curl https://api.stripe.com/v1/events/%s -u %s: 2>/dev/null',
                $event->getStripeId(),
                $this->stripeKey
            );

            $response = json_decode(shell_exec($cliCommand));

            if (isset($response->error)) {
                $output->writeln('<error>'.$response->error->message.'</error>');
                continue;
            }

            if (is_null($response)) {
                $output->writeln('<error>NULL</error>');
                continue;
            }

            $output->write('<info>ok</info>');

            $output->write(' ... updating local copy  ... ');

            $event->setStripeEventData(json_encode($response));

            if (!$isDryRun) {
                $this->entityManager->persist($event);
                $this->entityManager->flush();
            }

            $output->writeln('<info>done</info>');
        }

        return self::RETURN_CODE_OK;
    }
}
