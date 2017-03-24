<?php

namespace Wizclumsy\CMS\Models\Traits;

use Parsedown;

trait Markdown
{
    protected function parseMarkdown($value)
    {
        return Parsedown::instance()->text($value);
    }
}
