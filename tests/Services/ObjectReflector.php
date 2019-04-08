<?php

namespace App\Tests\Services;

class ObjectReflector
{
    /**
     * @param object $object
     * @param string $objectClass
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public static function setProperty(
        $object,
        string $objectClass,
        string $propertyName,
        $propertyValue
    ) {
        try {
            $reflector = new \ReflectionClass($objectClass);
            $property = $reflector->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue($object, $propertyValue);
        } catch (\ReflectionException $exception) {
        }
    }

    /**
     * @param object $object
     * @param string $propertyName
     *
     * @return mixed
     */
    public static function getProperty($object, string $propertyName)
    {
        $value = null;

        try {
            $reflector = new \ReflectionObject($object);
            $property = $reflector->getProperty($propertyName);
            $property->setAccessible(true);

            $value =  $property->getValue($object);
        } catch (\ReflectionException $exception) {
        }

        return $value;
    }
}
