<?php

namespace App;

class CustomParsedown extends \Parsedown
{
    protected function inlineLink($excerpt)
    {
        $result = parent::inlineLink($excerpt);

        $result['element']['attributes']['ref'] = 'noopener noreferrer';
        $result['element']['attributes']['target'] = '_blank';

        return $result;
    }
}
