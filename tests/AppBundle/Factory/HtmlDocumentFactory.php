<?php

namespace Tests\AppBundle\Factory;

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

    /**
     * @param string $location
     *
     * @return string
     */
    public static function createMetaRedirectDocument($location)
    {
        $content = str_replace('{{ url }}', $location, static::load('meta-redirect'));

        if (empty($location)) {
            $content = str_replace(' url=', '', $content);
        }

        return $content;
    }
}
