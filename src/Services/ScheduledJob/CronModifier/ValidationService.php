<?php
namespace App\Services\ScheduledJob\CronModifier;

class ValidationService {

    /**
     * @param $cronModifier
     * @return bool
     */
    public function isValid($cronModifier) {
        if (is_null($cronModifier)) {
            return true;
        }

        $pattern = '/\[ `date \+\\\\%d` -le [0-9] \]/';
        return preg_match($pattern, $cronModifier) > 0;
    }

}