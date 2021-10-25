<?php

namespace Tinustester\Bundle\GridviewBundle\Component;

use Tinustester\Bundle\GridviewBundle\Helper\Html;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Tinustester\Bundle\GridviewBundle\Exception\PaginationException;

class PaginationView
{
    /**
     * @var Pagination
     */
    protected Pagination $pagination;

    /**
     * @var Router
     */
    protected Router $router;

    /**
     * @var ParameterBag|array
     */
    protected ParameterBag|array $queryParameters;

    /**
     * @var Html
     */
    protected Html $html;

    /**
     * @var array Parameter list for the pagination block.
     */
    protected array $options = ['class' => 'pagination'];

    /**
     * @var array Parameter list for each pagination link.
     */
    protected array $linkOptions = ['class' => 'page-link'];

    protected array $buttonOptions = ['class' => 'page-item'];

    /**
     * @var string CSS class name for first page button.
     */
    public string $firstPageCssClass = 'first';

    /**
     * @var string CSS class name for last page button.
     */
    public string $lastPageCssClass = 'last';

    /**
     * @var string CSS class name for previous page button.
     */
    public string $prevPageCssClass = 'prev';

    /**
     * @var string CSS class name for next page button.
     */
    public string $nextPageCssClass = 'next';

    /**
     * @var string CSS class name for active page button
     */
    public string $activePageCssClass = 'active';

    /**
     * @var string CSS class for disabled page button.
     */
    public string $disabledPageCssClass = 'disabled';

    /**
     * @var int Max number of buttons to show.
     */
    public int $maxButtonCount = 10;

    /**
     * @var bool Whether to show a link to the first page.
     */
    public bool $showFirstPageLink = false;

    /**
     * @var bool Whether to show a link to the last page
     */
    public bool $showLastPageLink = false;

    /**
     * @var bool Whether to show a link to the previous page.
     */
    public bool $showPrevPageLink = true;

    /**
     * @var bool Whether to show a link to the next page
     */
    public bool $showNextPageLink = true;

    /**
     * @var string Next page default label. Will be used if [[showNextPageLink]]
     * set to true.
     */
    public string $nextPageLabel = '&rsaquo;';

    /**
     * @var string Precious page default label. Will be used if
     * [[showPrevPageLink]] set to true.
     */
    public string $prevPageLabel = '&lsaquo;';

    /**
     * @var string First page default label. Will be used if
     * [[showFirstPageLink]] set to true.
     */
    public string $firstPageLabel = '&laquo;';

    /**
     * @var string Last page default label. Will be used if
     * [[showLastPageLink]] set to true.
     */
    public string $lastPageLabel = '&raquo;';

    /**
     * PaginationView constructor.
     *
     * @param RequestStack $request
     * @param Router $router
     */
    public function __construct(RequestStack $request, Router $router, Html $html)
    {
        $currentRequest = $request->getCurrentRequest();

        if ($currentRequest) {
            $this->queryParameters = $currentRequest->query->all();
        }

        $this->router = $router;
        $this->html = $html;
    }

    /**
     * Render pagination block.
     *
     * @return string
     * @throws PaginationException
     */
    public function renderPageButtons(): string
    {
        if (!$this->pagination) {
            throw new PaginationException(
                'Instance of ' . Pagination::class . ' should be specified.'
            );
        }

        $pageCount = $this->pagination->getPageCount();

        if ($pageCount < 2) {
            return '';
        }

        $buttons = [];

        $currentPage = $this->pagination->getCurrentPage();

        if ($this->showFirstPageLink) {
            $buttons[] = $this->createPageButton(
                $this->firstPageLabel,
                0,
                $this->firstPageCssClass,
                $currentPage <= 0,
                false
            );
        }

        if ($this->showPrevPageLink) {
            $buttons[] = $this->createPageButton(
                $this->prevPageLabel,
                max(0, $currentPage - 1),
                $this->prevPageCssClass,
                $currentPage <= 0,
                false
            );
        }

        [$startPage, $endPage] = $this->getPageRange();

        for ($i = $startPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->createPageButton(
                $i + 1,
                $i,
                null,
                false,
                $i == $currentPage
            );
        }

        if ($this->showNextPageLink) {
            $buttons[] = $this->createPageButton(
                $this->nextPageLabel,
                min($currentPage + 1, $pageCount - 1),
                $this->nextPageCssClass,
                $currentPage >= $pageCount - 1,
                false
            );
        }

        if ($this->showLastPageLink) {
            $buttons[] = $this->createPageButton(
                $this->lastPageLabel,
                $pageCount - 1,
                $this->lastPageCssClass,
                $currentPage >= $pageCount - 1,
                false
            );
        }

        return '<ul ' . $this->html->prepareTagAttributes($this->options) . '>'
            . implode("\n", $buttons)
            . '</ul>';
    }

    /**
     * Render single pagination block button.
     *
     * @param string $label
     * @param int $page
     * @param string|null $class
     * @param bool $disabled
     * @param bool $active
     *
     * @return string
     * @throws PaginationException
     */
    protected function createPageButton(string $label, int $page, ?string $class, bool $disabled, bool $active): string
    {
        $buttonClassList = [$class];

        if ($active) {
            array_push($buttonClassList, $this->activePageCssClass);
        }

        if ($disabled) {
            array_push($buttonClassList, $this->disabledPageCssClass);
        }

        $buttonOptions = array_merge_recursive($this->buttonOptions, ['class' => $buttonClassList]);

        $linkOptions = $this->linkOptions;

        if ($active) {
            $linkOptions = array_merge(
                $linkOptions,
                ['data-page' => $page]
            );
        }

        $link = '<a '.$this->html->prepareTagAttributes($linkOptions).' href="'
            .$this->createButtonLink($page, $this->pagination->getPageSize())
            .'">';
        $link .= $label;
        $link .= '</a>';

        return '<li '.$this->html->prepareTagAttributes($buttonOptions).'>' . $link . '</li>';
    }

    /**
     * Create link for pagination button.
     *
     * @param int $pageIndex
     * @param int $pageSize
     * @param bool $absoluteUrl
     *
     * @return string
     * @throws PaginationException
     */
    public function createButtonLink(int $pageIndex, int $pageSize, bool $absoluteUrl = true): string
    {
        $pageParamName = $this->pagination->getPageParamName();
        $pageSizeParamName = $this->pagination->getPageSizeParam();

        if ($pageIndex > 0) {
            $this->queryParameters[$pageParamName] = $pageIndex + 1;
        } else {
            unset($this->queryParameters[$pageParamName]);
        }

        if ($pageSize != $this->pagination->getDefaultPageSize()) {
            $this->queryParameters[$pageSizeParamName] = $pageSize;
        } else {
            unset($this->queryParameters[$pageSizeParamName]);
        }

        return $this->router->generate(
            $this->pagination->getRoute(),
            $this->queryParameters,
            $absoluteUrl ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH
        );
    }

    /**
     * Calculate current pagination pages range.
     *
     * @return array
     */
    protected function getPageRange(): array
    {
        $pageCount = $this->pagination->getPageCount();

        $beginPage = max(
            0,
            $this->pagination->getCurrentPage() - floor(
                $this->maxButtonCount / 2
            )
        );

        $endPage = $beginPage + $this->maxButtonCount - 1;

        if ($endPage >= $pageCount) {
            $endPage = $pageCount - 1;

            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }

    /**
     * @param Pagination $pagination
     *
     * @return $this
     */
    public function setPagination(Pagination $pagination): static
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * Set pagination block options.
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set parameters for each pagination link.
     *
     * @param array $linkOptions
     *
     * @return $this
     */
    public function setLinkOptions(array $linkOptions): static
    {
        $this->linkOptions = array_merge($this->linkOptions, $linkOptions);
        return $this;
    }

    /**
     * @param Html $html
     *
     * @return $this
     */
    public function setHtml(Html $html): static
    {
        $this->html = $html;

        return $this;
    }
}