<?php
namespace phpjs\urls;

abstract class Host
{
    protected $mHost;

    protected function __construct($aHost)
    {
        $this->mHost = $aHost;
    }

    abstract public function serialize();
}
