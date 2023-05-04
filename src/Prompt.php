<?php

namespace MaximeRenou\PiChat;

class Prompt
{
    public $text;
    
    public function __construct($text)
    {
        $this->text = $text;
    }
}