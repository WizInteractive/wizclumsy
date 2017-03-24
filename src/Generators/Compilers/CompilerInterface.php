<?php

namespace Wizclumsy\CMS\Generators\Compilers;

interface CompilerInterface
{
    /**
     * Compile the template using
     * the given data
     *
     * @param $template
     * @param $data
     */
    public function compile($template, $data);
}
