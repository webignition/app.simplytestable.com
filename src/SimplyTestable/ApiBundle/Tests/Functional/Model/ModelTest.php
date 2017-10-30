<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Model;

use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

abstract class ModelTest extends AbstractBaseTestCase {

    protected function getInstance() {
        $modelClassName = $this->getModelClassNameFromTestClassName();
        return new $modelClassName;
    }

    /**
     * @return string
     */
    private function getModelClassNameFromTestClassName() {
        $modelClassNameParts = [];

        $classNameParts = explode(
            '\\',
            str_replace('\ApiBundle\Tests\Functional\\', '\ApiBundle\\', get_class($this))
        );

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
