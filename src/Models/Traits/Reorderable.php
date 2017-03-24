<?php

namespace Wizclumsy\CMS\Models\Traits;

trait Reorderable
{
    public function activeReorder()
    {
        return property_exists($this, 'activeReorder') ? $this->activeReorder : false;
    }
}
