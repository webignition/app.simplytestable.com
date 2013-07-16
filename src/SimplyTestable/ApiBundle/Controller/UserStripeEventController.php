<?php

namespace SimplyTestable\ApiBundle\Controller;

class UserStripeEventController extends AbstractUserController
{
    
    public function listAction($email_canonical, $type = null)            
    {
        if ($this->getUserService()->isPublicUser($this->getUser())) {
            return $this->sendFailureResponse();
        }
        
        if ($email_canonical !== $this->getUser()->getEmail()) {
            return $this->sendFailureResponse();
        }
        
        return $this->sendResponse($this->getStripeEventService()->getForUserAndType($this->getUser(), $type));
    }
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StripeEventService 
     */
    private function getStripeEventService() {
        return $this->get('simplytestable.services.stripeeventservice');
    } 

}
