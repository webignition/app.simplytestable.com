<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\CountAction;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class UserTest extends CountTest
{
    const JOB_TOTAL = 10;

    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'
    );

    private $users = array();
    private $counts = array();

    public function setUp()
    {
        parent::setUp();

        foreach ($this->userEmailAddresses as $emailAddress) {
            $this->users[] = $this->createAndActivateUser($emailAddress, 'password');
        }

        $jobFactory = new JobFactory($this->container);

        foreach ($this->getCanonicalUrls() as $index => $canonicalUrl) {
            $user = $this->users[($index === 0) ? 0 : 1];

            $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_USER => $user,
            ]);
        }

        foreach ($this->users as $user) {
            $this->getUserService()->setUser($user);

            $this->counts[$user->getEmail()] = json_decode($this->getJobListController('countAction', array(
                'user' => $user->getEmail()
            ))->countAction()->getContent());
        }
    }

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    public function testCountZeroIsConstraintedToUserZero()
    {
        $this->assertEquals(1, $this->counts[$this->users[0]->getEmail()]);
    }

    public function testCountOneIsConstraintedToUserOne()
    {
        $this->assertEquals(9, $this->counts[$this->users[1]->getEmail()]);
    }


    protected function getCanonicalUrls()
    {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }
}
