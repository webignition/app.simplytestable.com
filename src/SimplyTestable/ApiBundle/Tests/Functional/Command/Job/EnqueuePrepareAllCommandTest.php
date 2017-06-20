<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Functional\ConsoleCommandTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class EnqueuePrepareAllCommandTest extends ConsoleCommandTestCase
{
    /**
      * @return string
     */
    protected function getCommandName()
    {
        return 'simplytestable:job:enqueue-prepare-all';
    }

    /**
     * @return ContainerAwareCommand[]
     */
    protected function getAdditionalCommands()
    {
        return array(
            new EnqueuePrepareAllCommand()
        );
    }

    public function testJobsAreEnqueued()
    {
        $canonicalUrls = array(
            'http://one.example.com/',
            'http://two.example.com/'
        );

        $jobFactory = $this->createJobFactory();

        /* @var Job[] $jobs */
        $jobs = array();
        foreach ($canonicalUrls as $canonicalUrl) {
            $jobs[] = $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            ]);
        }

        $this->assertReturnCode(0);

        foreach ($jobs as $job) {
            $this->assertTrue($this->getResqueQueueService()->contains(
                'job-prepare',
                ['id' => $job->getId()]
            ));
        }
    }

    public function testExecuteInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(1);
    }
}
