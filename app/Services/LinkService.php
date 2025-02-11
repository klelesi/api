<?php

namespace App\Services;


use Spekulatius\PHPScraper\PHPScraper;

class LinkService
{
    public static function parse(string $link): LinkData
    {
        $parser = new PHPScraper;
        $parser->go($link);
        return new LinkData($parser->title, $parser->metaTags, $parser->openGraph, $parser->twitterCard);
    }
}
