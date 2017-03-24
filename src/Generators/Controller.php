<?php

namespace Wizclumsy\CMS\Generators;

class Controller extends Generator
{
    public function targetName()
    {
        return array_get($this->templateData, 'objectNamePlural').'Controller';
    }
}
