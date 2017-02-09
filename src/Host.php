<?php
namespace phpjs\urls;

class Host
{
    const DOMAIN      = 1;
    const IPV4        = 2;
    const IPV6        = 3;
    const OPAQUE_HOST = 4;

    private $host;
    private $type;

    protected function __construct($host)
    {
        $this->host = $host;
    }

    /**
     * Returns whether or not a Host is a particlar type.
     *
     * @param  int  $type A Host type.
     *
     * @return bool
     */
    public function isType($type)
    {
        return $this->type == $type;
    }
}
