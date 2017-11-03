<?php

namespace Tests\ApiBundle\Functional\Resque\Job\Task;

use SimplyTestable\ApiBundle\Command\Task\Cancel\CollectionCommand;
use SimplyTestable\ApiBundle\Resque\Job\Task\CancelCollectionJob;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Functional\Resque\Job\AbstractJobTest;

class CancelCollectionJobTest extends AbstractJobTest
{
    const QUEUE = 'task-cancel-collection';

    public function testRunInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        $job = $this->createJob(['ids' => '1,2,3'], self::QUEUE);
        $this->assertInstanceOf(CancelCollectionJob::class, $job);

        $returnCode = $job->run([]);

        $this->assertEquals(CollectionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);

        $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
    }
}
