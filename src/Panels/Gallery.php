<?php

namespace Wizclumsy\CMS\Panels;

use Wizclumsy\CMS\Panels\Traits\Index;
use Wizclumsy\CMS\Panels\Traits\Gallery as GalleryTrait;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Gallery extends Panel
{
    use Index, GalleryTrait;

    protected $action = 'index';

    public $thumbnailSlot = null;
}
