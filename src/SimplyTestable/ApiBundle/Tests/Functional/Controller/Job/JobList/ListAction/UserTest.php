<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\ListAction;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class UserTest extends ListTest
{
    const JOB_TOTAL = 10;

    private $userEmailAddresses = array(
        'user1@example.com',
        'user2@example.com'
    );

    private $users = array();
    private $lists = array();

    /**
     * @var Job[]
     */
    protected $jobs = array();

    protected function setUp()
    {
        parent::setUp();

        $userFactory = new UserFactory($this->container);

        foreach ($this->userEmailAddresses as $emailAddress) {
            $this->users[] = $userFactory->createAndActivateUser($emailAddress);
            $this->jobs[$emailAddress] = array();
        }

        $jobFactory = new JobFactory($this->container);

        foreach ($this->getCanonicalUrlCollection(10) as $index => $canonicalUrl) {
            $this->jobs[$this->users[$index % 2]->getEmail()][] = $jobFactory->create([
                JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
                JobFactory::KEY_USER => $this->users[$index % 2],
            ]);
        }

        foreach ($this->users as $user) {
            $this->getUserService()->setUser($user);

            $this->lists[$user->getEmail()] = json_decode(
                $this->getJobListController('listAction')
                    ->listAction(count($this->jobs[$user->getEmail()]))
                    ->getContent()
            );
        }
    }

    protected function getRequestingUser()
    {
        return $this->getUserService()->getPublicUser();
    }

    public function testListZeroIsConstraintedToUserZero()
    {
        $list = $this->lists[$this->users[0]->getEmail()];
        foreach ($list->jobs as $job) {
            $this->assertEquals($this->users[0]->getEmail(), $job->user);
        }
    }

    public function testListOneIsConstraintedToUserOne()
    {
        $list = $this->lists[$this->users[1]->getEmail()];
        foreach ($list->jobs as $job) {
            $this->assertEquals($this->users[1]->getEmail(), $job->user);
        }
    }

    protected function getCanonicalUrls()
    {
        return $this->getCanonicalUrlCollection(self::JOB_TOTAL);
    }
}
