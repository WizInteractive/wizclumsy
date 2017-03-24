<?php

namespace Wizclumsy\CMS\Facades;

use Wizclumsy\CMS\Support\ResourceNameResolver;

class Labeler extends \Illuminate\Support\Facades\Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return new ResourceNameResolver;
    }
}
