<?php

namespace Wizclumsy\CMS\Facades;

class Overseer extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'clumsy.auth';
    }
}
