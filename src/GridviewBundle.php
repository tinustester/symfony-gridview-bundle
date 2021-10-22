<?php
namespace Tinustester\Bundle\GridviewBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GridviewBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}