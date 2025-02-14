<?php

namespace App\Http\Controllers;

use App\CustomParsedown;

abstract class Controller
{
    protected static function parseMarkdown(string $markdown): string
    {
        $html = (new CustomParsedown())->setSafeMode(true)->text($markdown);
        $purifier = new \HTMLPurifier(['HTML.TargetNoreferrer' => true, 'HTML.TargetNoopener' => true, 'Attr.AllowedFrameTargets' => ['_blank']]);

        return $purifier->purify($html);
    }
}
