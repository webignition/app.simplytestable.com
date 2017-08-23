<?php
namespace SimplyTestable\ApiBundle\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Command\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EnqueueCommand extends BaseCommand {

    protected function configure()
    {
        $this
            ->setName('simplytestable:scheduledjob:enqueue')
            ->setDescription('Start a new job from a scheduled job')
            ->addArgument('id', InputArgument::REQUIRED, 'id of scheduled job to execute')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resqueQueueService = $this->getContainer()->get('simplytestable.services.resque.queueservice');
        $resqueJobFactory = $this->getContainer()->get('simplytestable.services.resque.jobfactory');

        $startNotice = 'simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] start';

        $output->write($startNotice);
        $this->getLogger()->notice($startNotice);

        if ($resqueQueueService->contains('scheduledjob-execute', ['id' => (int)$input->getArgument('id')])) {
            $this->getLogger()->notice('simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] already in execute queue');
        } else {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'scheduledjob-execute',
                    ['id' => (int)$input->getArgument('id')]
                )
            );

            $this->getLogger()->notice('simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] enqueuing');
        }

        $endNotice = 'simplytestable:scheduledjob:enqueue [' . $input->getArgument('id') . '] done';

        $output->writeln($endNotice);
        $this->getLogger()->notice($endNotice);
    }
}
