<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Stripe\Event as StripeEvent;

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
     * @param string $name
     * @return \SimplyTestable\ApiBundle\Entity\Stripe\Event
     */
    public function create($stripeId, $type, $isLiveMode) {
        if ($this->has($stripeId)) {
            return $this->find($stripeId);
        }
        
        $stripeEvent = new StripeEvent();
        
        $stripeEvent->setStripeId($stripeId);
        $stripeEvent->setType($type); 
        $stripeEvent->setIsLive($isLiveMode);
        
        return $this->persistAndFlush($stripeEvent);
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