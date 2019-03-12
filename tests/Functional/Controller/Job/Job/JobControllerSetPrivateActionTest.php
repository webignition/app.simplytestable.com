<?php

namespace App\Tests\Functional\Controller\Job\Job;

use App\Services\UserService;
use App\Tests\Services\JobFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @group Controller/Job/JobController
 */
class JobControllerSetPrivateActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => self::$container->get('router')->generate('job_job_setprivate', [
                'test_id' => $job->getId(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * @dataProvider setPrivateActionDataProvider
     *
     * @param string $owner
     * @param string $requester
     * @param bool $callSetPublic
     * @param bool $expectedIsPublic
     */
    public function testSetPrivateAction(
        $owner,
        $requester,
        $callSetPublic,
        $expectedIsPublic
    ) {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];
        $requesterUser = $users[$requester];

        $this->setUser($ownerUser);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $ownerUser,
        ]);

        if ($callSetPublic) {
            $this->callSetPublicAction($ownerUser, $job);
        }

        $this->setUser($requesterUser);
        $response = $this->jobController->setPrivateAction(
            self::$container->get(UserService::class),
            $requesterUser,
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
    public function setPrivateActionDataProvider()
    {
        return [
            'public owner, public requester' => [
                'owner' => 'public',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedIsPublic' => true,
            ],
            'public owner, private requester' => [
                'owner' => 'public',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedIsPublic' => true,
            ],
            'private owner, private requester, private test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => false,
                'expectedIsPublic' => false,
            ],
            'private owner, private requester, public test' => [
                'owner' => 'private',
                'requester' => 'private',
                'callSetPublic' => true,
                'expectedIsPublic' => false,
            ],
            'private owner, public requester, private test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => false,
                'expectedIsPublic' => false,
            ],
            'private owner, public requester, public test' => [
                'owner' => 'private',
                'requester' => 'public',
                'callSetPublic' => true,
                'expectedIsPublic' => true,
            ],
        ];
    }
}
