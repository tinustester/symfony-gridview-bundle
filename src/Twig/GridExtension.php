<?php

namespace Tinustester\Bundle\GridviewBundle\Twig;

use Twig\Environment;
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

    public function getFunctions()
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
    public function prepareGridView(GridView $gridView)
    {
        try {
            return $this->twig->createTemplate($gridView->renderGrid())->render();
        }catch (\Exception $exception){
            return 'OEPS:<pre>'.print_r($exception->getTraceAsString(), 1).'</pre>';
        }

        $renderParams = [];

        $filterForm = $gridView->getFormBuilder();

        if ($filterForm) {
            $renderParams[$gridView->getId()]
                = $gridView->getFormBuilder()->getForm()->createView();
        }

        return $this->twig->createTemplate($gridView->renderGrid())->render(
            $renderParams
        );
    }

    public function getName()
    {
        return get_class($this);
    }
}