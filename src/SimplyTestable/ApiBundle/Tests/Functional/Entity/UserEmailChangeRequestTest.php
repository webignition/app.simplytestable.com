<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest;

class UserEmailChangeRequestTest extends BaseSimplyTestableTestCase
{
    public function testUtf8NewEmail()
    {
        $newEmail = 'foo-ɸ@example.com';
        $userEmailChangeRequest = new UserEmailChangeRequest();

        $userEmailChangeRequest->setUser($this->getUserService()->create('user@example.com', 'password'));
        $userEmailChangeRequest->setNewEmail($newEmail);
        $userEmailChangeRequest->setToken('foo-token');

        $this->getManager()->persist($userEmailChangeRequest);
        $this->getManager()->flush();

        $userEmailChangeRequestId = $userEmailChangeRequest->getId();

        $this->getManager()->clear();

        $this->assertEquals($newEmail, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\UserEmailChangeRequest')->find($userEmailChangeRequestId)->getNewEmail());
    }
}
