<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Invoice extends Object {
    
    
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
        
        foreach ($this->getDataProperty('lines')->data as $line) {
            $plan = new Plan($line->plan);
            
            $linesSummary[] = array(
                'proration' => (int)$line->proration,
                'plan_name' => $plan->getName(),
                'period_start' => $line->period->start,
                'period_end' => $line->period->end,
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
    
}