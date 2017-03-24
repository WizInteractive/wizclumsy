<?php

namespace Wizclumsy\CMS\Contracts;

interface ShortcodeInterface
{
    public function regex($string);

    public function add($key, $description);

    public function addMany($codes);

    public function remove($key);

    public function setCodes(array $shortcodes);

    public function availableCodes();

    public function parse($content);
}
