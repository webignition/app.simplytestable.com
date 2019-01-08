<?php

namespace App\Tests\Factory;

class HtmlDocumentFactory
{
    public static function load(string $name): string
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/HtmlDocuments/' . $name . '.html');
    }

    public static function createMetaRedirectDocument(string $location, string $fixtureName = 'meta-redirect'): string
    {
        $content = str_replace('{{ url }}', $location, static::load($fixtureName));

        if (empty($location)) {
            $content = str_replace(' url=', '', $content);
        }

        return $content;
    }
}
