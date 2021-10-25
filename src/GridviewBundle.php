<?php
namespace Tinustester\Bundle\GridviewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GridviewBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}