<?php

namespace Wizclumsy\CMS\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Wizclumsy\CMS\Models\Traits\AdminResource;

class BaseModel extends Eloquent
{
    use AdminResource;

    protected $guarded = ['id'];
}
