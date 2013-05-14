<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;

class TestUserService extends UserService {
    
    /**
     *
     * @var User
     */
    private $user;
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
    }
    
    
    /**
     * 
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

}