<?php
namespace App\Command\Worker;

use App\Services\Worker\TaskNotificationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskNotificationCommand extends Command
{
    const NAME = 'simplytestable:worker:tasknotification';

    const RETURN_CODE_OK = 0;

    /**
     * @var TaskNotificationService
     */
    private $workerTaskNotificationService;

    /**
     * @param TaskNotificationService $workerTaskNotificationService
     * @param string|null $name
     */
    public function __construct(TaskNotificationService $workerTaskNotificationService, $name = null)
    {
        parent::__construct($name);

        $this->workerTaskNotificationService = $workerTaskNotificationService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Notify all workers of tasks ready to be carried out')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->workerTaskNotificationService->notify();

        return self::RETURN_CODE_OK;
    }
}
