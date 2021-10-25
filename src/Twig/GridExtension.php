<?php

namespace Tinustester\Bundle\GridviewBundle\Twig;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Tinustester\Bundle\GridviewBundle\Gridview;

class GridExtension extends AbstractExtension
{
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gridView', [$this, 'prepareGridView'], ['is_safe' => ['html']])
        ];
    }

    /**
     * @param GridView $gridView
     *
     * @return string
     */
    public function prepareGridView(GridView $gridView): string
    {
        $renderParams = [];
        $filterForm = $gridView->getFormBuilder();

        if ($filterForm) {
            $renderParams[$gridView->getId()] = $filterForm->getForm()->createView();
        }

        try {
            return $this->twig->createTemplate($gridView->renderGrid())->render(
                $renderParams
            );
        } catch (LoaderError | SyntaxError $e) {
            return 'Render problem';
        }
    }

    public function getName(): string
    {
        return get_class($this);
    }
}