<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

class HtmlDocumentFactory
{
    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/HtmlDocuments/' . $name . '.html');
    }
}
