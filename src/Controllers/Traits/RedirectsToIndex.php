<?php

namespace Wizclumsy\CMS\Controllers\Traits;

trait RedirectsToIndex
{
    protected function redirectAfter($action, $id = null)
    {
        return route("{$this->routePrefix}.index");
    }
}
