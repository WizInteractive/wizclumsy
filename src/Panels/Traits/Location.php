<?php

namespace Wizclumsy\CMS\Panels\Traits;

use Wizclumsy\Assets\Facade as Asset;

trait Location
{
    public function location($lat, $lng, $address = null)
    {
        Asset::load('google-maps-admin');

        return view($this->view->resolve('macros.location'), compact('lat', 'lng', 'address'))->render();
    }
}
