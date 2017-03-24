<?php

namespace Wizclumsy\CMS\Generators;

class PivotTrait extends Generator
{
    public function targetName()
    {
        return $this->getData('objectName').'Pivot';
    }
}
