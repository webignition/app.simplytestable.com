<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueuePrepareAllCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var EnqueuePrepareAllCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get('simplytestable.command.job.enqueueprepareall');
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            EnqueuePrepareAllCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $maintenanceController->disableReadOnlyAction();
    }

    public function testRun()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

        $jobValuesCollection = [
            [
                JobFactory::KEY_DOMAIN => 'http://foo.example.com/',
            ],
            [
                JobFactory::KEY_DOMAIN => 'http://bar.example.com/',
            ],
        ];

        $jobFactory = new JobFactory($this->container);

        /* @var Job[] $jobs */
        $jobs = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobs[] = $jobFactory->create($jobValues);
        }

        $this->assertTrue($resqueQueueService->isEmpty('job-prepare'));

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(EnqueuePrepareAllCommand::RETURN_CODE_OK, $returnCode);

        foreach ($jobs as $job) {
            $this->assertTrue($resqueQueueService->contains(
                'job-prepare',
                ['id' => $job->getId()]
            ));
        }
    }
}
