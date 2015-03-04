<?php

namespace SimplyTestable\ApiBundle\Tests\Model;

use SimplyTestable\ApiBundle\Tests\BaseTestCase;

abstract class ModelTest extends BaseTestCase {

    protected function getInstance() {
        $modelClassName = $this->getModelClassNameFromTestClassName();
        return new $modelClassName;
    }

    /**
     * @return string
     */
    private function getModelClassNameFromTestClassName() {
        $modelClassNameParts = [];

        $classNameParts = explode('\\', str_replace('\ApiBundle\Tests\\', '\ApiBundle\\', get_class($this)));

        foreach ($classNameParts as $classNamePart) {
            if (preg_match('/.+Model$/', $classNamePart)) {
                $modelClassNameParts[] = preg_replace('/Model$/', '', $classNamePart);
                return '\\' . implode('\\', $modelClassNameParts);
            } else {
                $modelClassNameParts[] = $classNamePart;
            }
        }
    }




}
