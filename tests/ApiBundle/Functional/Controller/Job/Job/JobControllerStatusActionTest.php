<?php

namespace Tests\ApiBundle\Functional\Controller\Job\Job;

use Tests\ApiBundle\Factory\JobFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class JobControllerStatusActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testStatusActionGetRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_status', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testStatusActionSuccess()
    {
        $job = $this->jobFactory->create();

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $response = $this->jobController->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(JsonResponse::class, $response);

        $responseData = json_decode($response->getContent(), true);

        $this->assertInternalType('array', $responseData);
        $this->assertNotEmpty($responseData);
    }

    public function testStatusActionAccessDenied()
    {
        $this->expectException(AccessDeniedHttpException::class);

        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $users['private'],
        ]);

        $this->setUser($users['public']);

        $this->jobController->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
    }
}