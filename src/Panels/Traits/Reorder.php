<?php

namespace Wizclumsy\CMS\Panels\Traits;

use Wizclumsy\Assets\Facade as Asset;

trait Reorder
{
    public function beforeRenderReorder()
    {
        $this->setData('title', trans('clumsy::titles.reorder', ['resources' => $this->getLabelPlural()]));

        Asset::load('jquery-ui', 30);
    }
}
