<?php

namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use Psr\Log\LoggerInterface;
use SimplyTestable\ApiBundle\Resque\Job\ScheduledJob\ExecuteJob;
use SimplyTestable\ApiBundle\Services\Resque\QueueService as ResqueQueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueCommand extends Command
{
    /**
     * @var ResqueQueueService
     */
    private $resqueQueueService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ResqueQueueService $resqueQueueService
     * @param LoggerInterface $logger
     * @param string|null $name
     */
    public function __construct(
        ResqueQueueService $resqueQueueService,
        LoggerInterface $logger,
        $name = null
    ) {
        parent::__construct($name);

        $this->resqueQueueService = $resqueQueueService;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:enqueue')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'id of scheduled job to execute'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scheduleJobId = (int)$input->getArgument('id');

        $startNotice = 'simplytestable:scheduledjob:enqueue [' . $scheduleJobId . '] start';

        $output->write($startNotice);
        $this->logger->notice($startNotice);

        if ($this->resqueQueueService->contains('scheduledjob-execute', ['id' => $scheduleJobId])) {
            $this->logger->notice(
                'simplytestable:scheduledjob:enqueue [' . $scheduleJobId. '] already in execute queue'
            );
        } else {
            $this->resqueQueueService->enqueue(new ExecuteJob(['id' => $scheduleJobId]));
            $this->logger->notice('simplytestable:scheduledjob:enqueue [' . $scheduleJobId . '] enqueuing');
        }

        $endNotice = 'simplytestable:scheduledjob:enqueue [' . $scheduleJobId . '] done';

        $output->writeln($endNotice);
        $this->logger->notice($endNotice);
    }
}
