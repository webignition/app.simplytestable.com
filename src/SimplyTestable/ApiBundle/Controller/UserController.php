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
    
    
    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function getTokenAction($email_canonical)            
    {        
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        $token = $this->getUserService()->getConfirmationToken($user);
        
        return $this->sendResponse($token);
    }   
    

    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function isEnabledAction($email_canonical) {
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        if ($user->isEnabled() === false) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }    
    
    
    /**
     * 
     * @param string $email_canonical
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function existsAction($email_canonical) {
        $user = $this->getUserService()->findUserByEmail($email_canonical);
        
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(404);
        }
        
        return new \Symfony\Component\HttpFoundation\Response('', 200);
    }
}
