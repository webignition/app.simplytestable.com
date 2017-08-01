<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job\IsPublicAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class IsPublicActionTest extends BaseControllerJsonTestCase
{
    const CANONICAL_URL = 'http://example.com';

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    public function testFalseForNonNumericJobId()
    {
        $this->assertEquals(
            404,
            $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, 'foo')->getStatusCode()
        );
    }

    public function testFalseForInvalidJobId()
    {
        $this->assertEquals(
            404,
            $this->getJobController('isPublicAction')->isPublicAction(self::CANONICAL_URL, 0)->getStatusCode()
        );
    }

    public function testTrueForJobOwnedByPublicUserAccessedByPublicUser()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
        ]);

        $this->assertEquals(
            200,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByPublicUser()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(
            200,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByNonPublicUser()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());

        $this->assertEquals(
            200,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testTrueForPublicJobOwnedByNonPublicUserAccessedByDifferentNonPublicUser()
    {
        $user1 = $this->userFactory->createAndActivateUser('user1@example.com');
        $user2 = $this->userFactory->createAndActivateUser('user2@example.com');

        $this->getUserService()->setUser($user1);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user1,
        ]);

        $this->getJobController('setPublicAction')->setPublicAction(self::CANONICAL_URL, $job->getId());

        $this->getUserService()->setUser($user2);
        $this->assertEquals(
            200,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByPublicUser()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $this->assertEquals(
            404,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByNonPublicUser()
    {
        $user = $this->userFactory->createAndActivateUser();

        $this->getUserService()->setUser($user);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user,
        ]);

        $this->assertEquals(
            404,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }

    public function testFalseForPrivateJobOwnedByNonPublicUserAccessedByDifferentNonPublicUser()
    {
        $user1 = $this->userFactory->createAndActivateUser('user1@example.com');
        $user2 = $this->userFactory->createAndActivateUser('user2@example.com');

        $this->getUserService()->setUser($user1);
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => self::CANONICAL_URL,
            JobFactory::KEY_USER => $user1,
        ]);

        $this->getUserService()->setUser($user2);
        $this->assertEquals(
            404,
            $this->getJobController('isPublicAction')
                ->isPublicAction(self::CANONICAL_URL, $job->getId())
                ->getStatusCode()
        );
    }
}
