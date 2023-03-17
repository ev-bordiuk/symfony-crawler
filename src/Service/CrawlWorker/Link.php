<?php

namespace App\Service\CrawlWorker;

class Link
{
    public function __construct(public $url, public $depth)
    {
    }
}
