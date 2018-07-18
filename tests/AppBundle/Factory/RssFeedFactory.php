<?php

namespace Tests\AppBundle\Factory;

class RssFeedFactory
{
    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/RssFeeds/' . $name . '.xml');
    }
}
