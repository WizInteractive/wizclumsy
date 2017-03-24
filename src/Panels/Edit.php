<?php

namespace Wizclumsy\CMS\Panels;

use Wizclumsy\CMS\Panels\Traits\Editable;

class Edit extends Panel
{
    use Editable;

    protected $action = 'edit';

    protected $inheritable = true;
}
