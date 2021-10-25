<?php
namespace Tinustester\Bundle\GridviewBundle;

use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Tinustester\Bundle\GridviewBundle\Column\BaseColumn;
use Tinustester\Bundle\GridviewBundle\DataSource\BaseDataSource;
use Tinustester\Bundle\GridviewBundle\DataSource\QueryDataSource;
use Tinustester\Bundle\GridviewBundle\Exception\GridException;
use Tinustester\Bundle\GridviewBundle\Helper\Html;

class Gridview
{
    /**
     * @var array List of html attributes that will be applied to grid container.
     */
    protected array $containerOptions = ['class' => 'grid-view'];

    /**
     * @var array List of html attributes that will be applied to grid table.
     */
    protected array $tableOptions = ['class' => 'table table-bordered table-striped'];

    /**
     * @var string Grid table caption.
     */
    protected string $tableCaption;

    /**
     * @var bool Whether the table header row will be shown.
     */
    protected bool $showHeader = true;

    /**
     * @var array List of html attributes that will be applied to grid table
     * header row.
     */
    protected array $headerRowOptions = [];

    /**
     * @var array Options for grid table rows.
     */
    protected array $rowOptions = [];

    /**
     * @var array List of html attributes that will be applied row that contains
     * filters.
     */
    public array $filterRowOptions = ['class' => 'filters'];

    /**
     * @var string Value that will be used for empty table cell.
     */
    protected string $emptyCell = '&nbsp;';

    /**
     * @var array
     */
    protected array $columns = [];

    /**
     * @var object|null Instance of target entity that will be used for creating
     * filter fields.
     */
    protected ?object $filterEntity;

    /**
     * @var string Target url which will accept filter data. Current route will
     * be used by default.
     */
    protected string $filterUrl = '';

    /**
     * @var string
     */
    protected string $gridIdPrefix = 'grid_';

    /**
     * @var string Current unique grid id.
     */
    protected string $gridId;

    /**
     * @var int Unique grid id
     */
    protected static int $gridCounter = 0;

    /**
     * @var QueryDataSource
     */
    protected QueryDataSource $dataSource;

    /**
     * @var FormBuilder
     */
    protected FormBuilder $formBuilder;

    /**
     * @var Html
     */
    protected Html $html;

    /**
     * Get grid id. If value was not set yet method generates new id based on
     * static counter so id will be unique for each new grid instance.
     *
     * @return string
     */
    public function getId(): string
    {
        if (!isset($this->gridId)) {
            $this->gridId = $this->gridIdPrefix . static::$gridCounter++;
        }

        return $this->gridId;
    }

    /**
     * @param BaseColumn $column
     *
     * @return $this
     */
    public function addColumn(BaseColumn $column): static
    {
        $this->columns[] = $column;

        return $this;
    }

    /**
     * Renders full grid content.
     *
     * @return string
     */
    public function renderGrid(): string
    {
        $this->containerOptions['id'] = $this->containerOptions['id'] ?? $this->getId();
        $gridContainerOptions = $this->html->prepareTagAttributes($this->containerOptions);

        return '<div ' . $gridContainerOptions . '>' . $this->renderTable() . '</div>';
    }

    /**
     * Renders grid table.
     *
     * @return string
     */
    protected function renderTable(): string
    {
        $tableOptions = $this->html->prepareTagAttributes($this->tableOptions);

        $tableHtml = '<table ' . $tableOptions . '>';
        $tableHtml .= $this->renderCaption();
        $tableHtml .= $this->renderTableHeader();
        $tableHtml .= $this->renderTableFilter();
        $tableHtml .= $this->renderTableBody();
        $tableHtml .= '</table>';

        return $tableHtml;
    }

    /**
     * Renders table body.
     *
     * @return string
     * @throws Exception\PaginationException
     */
    protected function renderTableBody(): string
    {
        $tableBody = '<tbody>';

        $dataEntities = $this->dataSource->fetchEntities();

        foreach ($dataEntities as $index => $entity) {
            $tableBody .= $this->renderTableRow($entity, $index);
        }

        $tableBody .= '</tbody>';

        return $tableBody;
    }

    /**
     * Renders table caption. Caption value will not be encoded.
     *
     * @return string
     */
    protected function renderCaption(): string
    {
        return $this->tableCaption ? '<caption>' . $this->tableCaption . '</caption>' : '';
    }

    /**
     * Renders table header row.
     *
     * @return string
     */
    public function renderTableHeader(): string
    {
        if (!$this->showHeader) {
            return '';
        }

        $tableHeader = '<thead><tr ' . $this->html->prepareTagAttributes($this->headerRowOptions) . ' >';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {
            $tableHeader .= $column->renderHeaderCellContent();
        }

        $tableHeader .= "</tr></thead>";

        return $tableHeader;
    }

    /**
     * @return string
     */
    public function renderTableFilter(): string
    {
        if (!$this->filterEntity) {
            return '';
        }

        $this->filterRowOptions['id'] = $this->getId() . '_filters';

        $tableHeader = '<tr ' . $this->html->prepareTagAttributes($this->filterRowOptions) . '>';
        $tableHeader .= '{{ form_start(' . $this->getId() . ') }}';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {
            $tableHeader .= $column->renderFilterCellContent();
        }

        $tableHeader .= '{{ form_end(' . $this->getId() . ') }}';
        $tableHeader .= "</tr>";

        return $tableHeader;
    }

    /**
     * Renders table body row.
     *
     * @param $entity
     * @param $index
     *
     * @return string
     */
    public function renderTableRow($entity, $index): string
    {
        $tableRaw = '<tr ' . $this->html->prepareTagAttributes($this->rowOptions) . ' >';

        /** @var BaseColumn $column */
        foreach ($this->columns as $column) {
            $tableRaw .= $column->renderCellContent(
                $entity,
                $index,
                $this->emptyCell
            );
        }

        $tableRaw .= '</tr>';

        return $tableRaw;
    }

    /**
     * Set grid table caption.
     *
     * @param string $tableCaption
     *
     * @return $this
     * @throws GridException
     */
    public function setTableCaption(string $tableCaption): static
    {
        if (!is_string($tableCaption)) {
            throw new GridException(
                'The expected type of the ' . self::class
                . ' table caption is string . ' . gettype($tableCaption) . ' given.'
            );
        }

        $this->tableCaption = trim($tableCaption);

        return $this;
    }

    /**
     * @param array $rowOptions
     *
     * @return $this
     */
    public function setRowOptions(array $rowOptions): static
    {
        $this->rowOptions = $rowOptions;

        return $this;
    }

    /**
     * @param boolean $showHeader
     *
     * @return $this
     */
    public function setShowHeader(bool $showHeader): static
    {
        $this->showHeader = $showHeader;

        return $this;
    }

    /**
     * @param array $tableOptions
     *
     * @return $this
     */
    public function setTableOptions(array $tableOptions): static
    {
        $this->tableOptions = array_merge($this->tableOptions, $tableOptions);

        return $this;
    }

    /**
     * @return QueryDataSource
     */
    public function getDataSource(): QueryDataSource
    {
        return $this->dataSource;
    }

    /**
     * @param FormBuilderInterface $formBuilder
     *
     * @return $this
     */
    public function setFormBuilder(FormBuilderInterface $formBuilder): static
    {
        $this->formBuilder = $formBuilder;

        return $this;
    }

    /**
     * @return FormBuilder
     */
    public function getFormBuilder(): FormBuilder
    {
        return $this->formBuilder;
    }

    /**
     * @return string
     */
    public function getEmptyCell(): string
    {
        return $this->emptyCell;
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param BaseDataSource $dataSource
     *
     * @return $this
     */
    public function setDataSource(BaseDataSource $dataSource): static
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * @param object $filterEntity
     *
     * @return Gridview
     * @throws GridException
     */
    public function setFilterEntity(object $filterEntity): static
    {
        if (!is_object($filterEntity)) {
            throw new GridException(
                'The expected type of the ' . self::class
                . ' filter entity is object . ' . gettype($filterEntity) . ' given.'
            );
        }

        $this->filterEntity = $filterEntity;

        return $this;
    }

    /**
     * @return object|null
     */
    public function getFilterEntity(): ?object
    {
        return $this->filterEntity;
    }

    /**
     * @param array $containerOptions
     *
     * @return $this
     */
    public function setContainerOptions(array $containerOptions): static
    {
        $this->containerOptions = array_merge(
            $this->containerOptions,
            $containerOptions
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getFilterUrl(): string
    {
        return $this->filterUrl;
    }

    /**
     * @param string $filterUrl
     *
     * @return $this
     */
    public function setFilterUrl(string $filterUrl): static
    {
        $this->filterUrl = $filterUrl;

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