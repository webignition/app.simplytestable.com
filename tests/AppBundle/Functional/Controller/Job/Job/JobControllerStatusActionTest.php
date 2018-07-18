<?php

namespace Tests\AppBundle\Functional\Controller\Job\Job;

use Tests\AppBundle\Factory\JobFactory;

/**
 * @group Controller/Job/JobController
 */
class JobControllerStatusActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testStatusActionGetRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_status', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }
}
