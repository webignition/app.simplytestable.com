<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Services\UserService;

use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerSetPublicActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_setpublic', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @dataProvider setPublicActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $expectedIsPublic
     */
    public function testSetPublicAction(
        $owner,
        $requester,
        $expectedIsPublic
    ) {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $ownerUser,
        ]);

        $this->setUser($requesterUser);
        $response = $this->jobController->setPublicAction(
            self::$container->get(UserService::class),
            $requesterUser,
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $this->assertEquals(302, $response->getStatusCode());

        $jobFromResponse = $this->jobFactory->getFromResponse($response);
        $this->assertEquals($job->getId(), $jobFromResponse->getId());

        $this->assertEquals($expectedIsPublic, $job->getIsPublic());
    }

    /**
     * @return array
     */
    public function setPublicActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'expectedIsPublic' => true,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'expectedIsPublic' => true,
            ],
            'private owner, private requester' => [
                'owner' => 'private',
                'requester' => 'private',
                'expectedIsPublic' => true,
            ],
            'private owner, public requester' => [
                'owner' => 'private',
                'requester' => 'public',
                'expectedIsPublic' => false,
            ],
            'leader owner, member1 requester' => [
                'owner' => 'leader',
                'requester' => 'member1',
                'expectedIsPublic' => true,
            ],
            'member1 owner, leader requester' => [
                'owner' => 'member1',
                'requester' => 'leader',
                'expectedIsPublic' => true,
            ],
            'member1 owner, member2 requester' => [
                'owner' => 'member1',
                'requester' => 'member2',
                'expectedStatusCode' => 302,
                'expectedIsPublic' => true,
            ],
        ];
    }
}
