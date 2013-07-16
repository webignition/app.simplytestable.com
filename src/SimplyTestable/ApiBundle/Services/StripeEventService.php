<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;
use SimplyTestable\ApiBundle\Entity\User;

class StripeEventService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Stripe\Event';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    /**
     * 
     * @param string $stripeId
     * @return StripeEvent
     */
    public function getByStripeId($stripeId) {
        return $this->getEntityRepository()->findOneBy(array(
            'stripeId' => $stripeId
        ));
    }
    
    
    /**
     * 
     * @param string $stripeId
     * @param string $type
     * @param boolean $isLiveMode
     * @param string $data
     * @param User $user
     * @return StripeEvent
     */
    public function create($stripeId, $type, $isLiveMode, $data, $user = null) {
        if ($this->has($stripeId)) {
            return $this->find($stripeId);
        }
        
        $stripeEvent = new StripeEvent();
        
        $stripeEvent->setStripeId($stripeId);
        $stripeEvent->setType($type); 
        $stripeEvent->setIsLive($isLiveMode);
        $stripeEvent->setData($data);
        
        if (!is_null($user)) {
            $stripeEvent->setUser($user);
        }
        
        return $this->persistAndFlush($stripeEvent);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Services\User $user
     * @param string $type
     * @return array
     */
    public function getForUserAndType(User $user, $type = null) {        
        $criteria = array(
            'user' => $user
        );
        
        $type = trim($type);
        if ($type != '') {
            $criteria['type'] = $type;
        }
        
        return $this->getEntityRepository()->findBy($criteria, array(
            'id' => 'DESC'
        ));
    }
    
    
    /**
     * 
     * @param string $stripeId
     * @return StripeEvent
     */
    public function find($stripeId) {
        return $this->getEntityRepository()->findOneByStripeId($stripeId);
    }
    
    
    /**
     * 
     * @param string $stripeId
     * @return boolean
     */
    public function has($stripeId) {
        return !is_null($this->find($stripeId));
    }
    
    
    /**
     *
     * @param StripeEvent $job
     * @return StripeEvent
     */
    public function persistAndFlush(StripeEvent $stripeEvent) {
        $this->getEntityManager()->persist($stripeEvent);
        $this->getEntityManager()->flush();
        return $stripeEvent;
    }    
}