<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Doctrine\UserManager;

class UserService extends UserManager {
    
    const PUBLIC_USER_EMAIL_ADDRESS = 'public@simplytestable.com';
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Entity\User
     */
    public function getPublicUser() {
        return $this->findUserByEmail(self::PUBLIC_USER_EMAIL_ADDRESS);
    }
    
}