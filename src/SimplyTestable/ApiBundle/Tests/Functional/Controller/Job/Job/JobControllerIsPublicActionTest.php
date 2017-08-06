<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class JobControllerIsPublicActionTest extends AbstractJobControllerTest
{
    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_ispublic', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @dataProvider isPublicActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param int $expectedResponseStatusCode
     */
    public function testIsPublicAction($owner, $requester, $callSetPublic, $expectedResponseStatusCode)
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->getUserService()->setUser($ownerUser);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $ownerUser,
        ]);

        if ($callSetPublic) {
            $this->jobController->setPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        }

        $this->getUserService()->setUser($requesterUser);
        $response = $this->jobController->isPublicAction($job->getWebsite()->getCanonicalUrl(), $job->getId());

        $this->assertEquals($expectedResponseStatusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function isPublicActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 200,
            ],
            'private owner, private requester, private test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedStatusCode' => 404,
            ],
            'private owner, private requester, public test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => true,
                'expectedStatusCode' => 200,
            ],
            'private owner, public requester, private test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedStatusCode' => 404,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedStatusCode' => 200,
            ],
        ];
    }
}
