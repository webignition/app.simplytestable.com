<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerTaskIdsActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_taskids', [
                'test_id' => $job->getId(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }
}
