<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserTest extends WebsitesTest
{
    const JOB_TOTAL = 10;

    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'
    );

    private $users = array();
    private $lists = array();

    public function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        foreach ($this->userEmailAddresses as $emailAddress) {
            $this->users[] = $userFactory->createAndActivateUser($emailAddress, 'password');
        }

        $jobFactory = new JobFactory($this->container);

        foreach ($this->getCanonicalUrls() as $index => $canonicalUrl) {
            $user = $this->users[($index === 0 ? 0 : 1)];

            $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_USER => $user,
            ]);
        }

        foreach ($this->users as $user) {
            $this->getUserService()->setUser($user);

            $this->lists[$user->getEmail()] = json_decode(
                $this->getJobListController('WebsitesAction')->WebsitesAction()->getContent()
            );
        }
    }

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    public function testListZeroIsConstraintedToUserZero()
    {
        $this->assertEquals(array_slice($this->getCanonicalUrls(), 0, 1), $this->lists[$this->users[0]->getEmail()]);
    }

    public function testListOneIsConstraintedToUserOne()
    {
        $expectedUrlSet = array_slice($this->getCanonicalUrls(), 1);
        sort($expectedUrlSet);

        $this->assertEquals($expectedUrlSet, $this->lists[$this->users[1]->getEmail()]);
    }

    protected function getCanonicalUrls()
    {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }
}
