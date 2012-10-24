<?php

namespace SimplyTestable\ApiBundle\Controller;

class UserController extends ApiController
{       
    public function getAction() {
        return $this->sendResponse();
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserService 
     */
    protected function getUserService() {
        return $this->get('simplytestable.services.userservice');
    }
    
    
    /**
     * 
     * @return \FOS\UserBundle\Util\UserManipulator 
     */
    protected function getUserManipulator() {        
        return $this->get('fos_user.util.user_manipulator');
    }  
}
