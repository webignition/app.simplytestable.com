<?php

namespace SimplyTestable\ApiBundle\Model\Stripe\Invoice;

use SimplyTestable\ApiBundle\Model\Stripe\Object;

class Invoice extends Object {
    
    public function __construct(\stdClass $data) {
        parent::__construct($data);
        
        if ($this->hasDataProperty('lines')) {
            $lineItems = array();
            
            foreach ($this->getDataProperty('lines')->data as $line) {
                $lineItems[] = new LineItem($line);
            }
            
            $this->setDataProperty('lines', $lineItems);
        }
    }    
    
    
    /**
     * 
     * @return string
     */
    public function getId() {
        return $this->getDataProperty('id');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getTotal() {
        return $this->getDataProperty('total');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getAmountDue() {
        return $this->getDataProperty('amount_due');
    }
    
    
    /**
     * 
     * @return array
     */
    public function getLinesSummary() {
        $linesSummary = array();
        
        foreach ($this->getDataProperty('lines') as $line) {
            /* @var $line \SimplyTestable\ApiBundle\Model\Stripe\Invoice\LineItem */            
            $linesSummary[] = array(
                'proration' => (int)$line->getIsProrated(),
                'plan_name' => $line->getPlan()->getName(),
                'period_start' => $line->getPeriodStart(),
                'period_end' => $line->getPeriodEnd(),
                'amount' => $line->getAmount()
            );
        }
        
        return $linesSummary;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getNextPaymentAttempt() {
        return $this->getDataProperty('next_payment_attempt');
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasNextPaymentAttempt() {
        return !is_null($this->getNextPaymentAttempt());
    }
    
    
    /**
     * 
     * @return int
     */
    public function getAttemptCount() {
        return $this->getDataProperty('attempt_count');
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Invoice\LineItem
     */
    public function getLines() {
        return $this->getDataProperty('lines');
    }
    
    
    /**
     * 
     * @return string
     */
    public function getSubscriptionId() {
        return $this->getDataProperty('subscription');
    }
    
    
    /**
     * 
     * @param string $subscriptionId
     * @return boolean
     */
    public function isForSubscription($subscriptionId) {
        return $this->getSubscriptionId() == $subscriptionId;
    }
    
}