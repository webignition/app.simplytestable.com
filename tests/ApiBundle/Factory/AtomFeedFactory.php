<?php

namespace Tests\ApiBundle\Factory;

class AtomFeedFactory
{
    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/AtomFeeds/' . $name . '.xml');
    }
}
