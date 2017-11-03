<?php

namespace Tests\ApiBundle\Factory;

use webignition\InternetMediaType\InternetMediaType;

class InternetMediaTypeFactory
{
    /**
     * @param string $type
     * @param string $subtype
     *
     * @return InternetMediaType
     */
    public static function create($type, $subtype)
    {
        $internetMediaType = new InternetMediaType();
        $internetMediaType->setType($type);
        $internetMediaType->setSubtype($subtype);

        return $internetMediaType;
    }
}
