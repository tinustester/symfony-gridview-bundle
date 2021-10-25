<?php

namespace Tinustester\Bundle\GridviewBundle\Column;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tinustester\Bundle\GridviewBundle\Exception\ActionColumnException;
use Tinustester\Bundle\GridviewBundle\Helper\Html;

class ActionColumn extends BaseColumn
{
    /** @var string  */
    public const SHOW = 'read';

    /** @var string  */
    public const EDIT = 'update';

    /** @var string  */
    public const DELETE = 'delete';

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var string|null Default header label for action column.
     */
    protected ?string $label = 'Actions';

    /**
     * @var string Default column cell data format
     */
    protected string $format = ColumnFormat::RAW_FORMAT;

    /**
     * @var array List of buttons to show. If this parameter was not change then
     * default buttons with url based on current url will be used. Buttons links
     * can be specified as string or callback function.
     *
     * If we use callable function it will be called with two parameters:
     * 1. $entity object Entity used in current row.
     * 2. $url    string Default url for this action. Can be changed.
     * 3. $index  int    Index of current entity.
     * Example of callback:
     * 'buttons' => [
     *     ActionColumn::EDIT => function ($entity, $url) {
     *         // Return link depends on some entity condition
     *         return $entity->isHidden() ? $url : '';
     *     },
     * ]
     *
     * In case of string we simply specify new url.
     * Example of string:
     * 'buttons' => [
     *     ActionColumn::SHOW => 'your_custom_url'
     * ]
     */
    protected array $buttons = [];

    /**
     * @var array List of buttons that could be hidden. If this parameter was
     * not change then all buttons will be shown. Visibility condition can be
     * specified as boolean value or callback function.
     *
     * If we use callable function it will be called with two parameters:
     * 1. $entity object Entity used in current row.
     * 2. $url string Default url for this action. Can be changed.
     * Example of callback:
     * 'hiddenButtons' => [
     *     ActionColumn::EDIT => function ($entity, $url) {
     *         // If callback function returns true then button will be hidden
     *         return $entity->isActive();
     *     },
     * ]
     *
     * Example of boolean expression:
     * 'hiddenButtons' => [
     *     ActionColumn::SHOW => true
     * ]
     */
    protected array $hiddenButtons = [];

    /**
     * @var array List of buttons icons.
     */
    protected array $buttonsLabel = [
        self::SHOW => 'eye-open',
        self::EDIT => 'pencil',
        self::DELETE => 'cross',
    ];

    public function __construct(ColumnFormat $columnFormat, Html $html, RequestStack $requestStack)
    {
        parent::__construct($columnFormat, $html);
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Get action buttons html.
     *
     * @param $entityInstance
     * @param $index
     * @param $emptyCellContent
     *
     * @return string
     * @throws Exception
     */
    public function renderCellContent($entityInstance, $index, $emptyCellContent = null): string
    {
        $buttonsHtml = implode('', $this->renderButtons($entityInstance, $index));

        if (!$buttonsHtml) {
            $buttonsHtml = $emptyCellContent;
        }

        return '<td '.$this->html->prepareTagAttributes($this->contentOptions).'>'.$buttonsHtml.'</td>';
    }

    /**
     * Check if specified button should be hidden.
     *
     * @param string $buttonName
     * @param string|null $buttonUrl
     * @param object $entityInstance
     *
     * @return bool
     */
    protected function isButtonHidden(string $buttonName, ?string $buttonUrl, object $entityInstance): bool
    {
        if (empty($this->hiddenButtons[$buttonName])) {
            return false;
        }

        $isHiddenExpression = $this->hiddenButtons[$buttonName];

        if (is_callable($isHiddenExpression)) {

            $isHidden = call_user_func_array(
                $isHiddenExpression,
                [$entityInstance, $buttonUrl]
            );
        } else {
            $isHidden = (bool)$isHiddenExpression;
        }

        return $isHidden;
    }

    /**
     * Create url based on current requested uri. Will be used if custom url
     * was not specified.
     *
     * @param string $actionName
     * @param object $entityInstance
     *
     * @return string
     */
    public function createDefaultButtonUrl(string $actionName, object $entityInstance): string
    {
        if (!method_exists($entityInstance, 'getId')) {
            return '';
        }

        return $this->request->getBaseUrl().$this->request->getPathInfo().'/'.$entityInstance->getId().'/'.$actionName;
    }

    /**
     * Render all buttons.
     *
     * @param object $entityInstance
     * @param int $index
     *
     * @return array
     * @throws Exception
     */
    protected function renderButtons(object $entityInstance, int $index): array
    {
        $defaultButtons = [self::SHOW => '', self::EDIT => '', self::DELETE => ''];

        $this->buttons = array_merge($defaultButtons, $this->buttons);

        $buttons = [];

        foreach ($this->buttons as $buttonName => $buttonOptions) {

            $defaultButtonUrl = $this->createDefaultButtonUrl(
                $buttonName,
                $entityInstance
            );

            $this->checkButtonUrl($buttonOptions);

            $buttonContent = null;
            $buttonUrl = $buttonOptions;

            if(is_array($buttonOptions)){
                $buttonUrl = '';
                if(isset($buttonOptions['url'])) {
                    $this->checkButtonUrl($buttonUrl);
                    $buttonUrl = $buttonOptions['url'];
                }
                if(isset($buttonOptions['content'])) {
                    $buttonContent = $buttonOptions['content'];
                    if(is_callable($buttonContent)) {
                        $buttonContent = call_user_func_array(
                            $buttonContent,
                            [$entityInstance, $buttonUrl, $index]
                        );
                    }
                }
            }

            if (is_callable($buttonUrl)) {
                $buttonUrl = call_user_func_array(
                    $buttonOptions,
                    [$entityInstance, $defaultButtonUrl, $index]
                );
            }

            if (!$buttonUrl) {
                $buttonUrl = $defaultButtonUrl;
            }

            if (
                $this->isButtonHidden($buttonName, $buttonUrl, $entityInstance)
            ) {
                continue;
            }

            $buttons[] = $buttonContent ?? $this->renderButton($buttonName, $buttonUrl);
        }

        return $buttons;
    }

    /**
     * Validation of type of certain button url value.
     *
     * @param mixed $buttonUrlData
     *
     * @return bool
     * @throws Exception
     */
    protected function checkButtonUrl($buttonUrlData): bool
    {
        if (!is_callable($buttonUrlData) && !is_string($buttonUrlData) && !is_array($buttonUrlData)) {
            throw new ActionColumnException(
                'Action column button url can contain string value or callable. '
                .gettype($buttonUrlData).' given.'
            );
        }

        return true;
    }

    /**
     * Render single button.
     *
     * @param string $buttonName
     * @param string $buttonLink
     *
     * @return string
     */
    protected function renderButton(string $buttonName, string $buttonLink): string
    {
        return '<a href="'.$buttonLink.'">'
            . '<span class="glyphicon glyphicon-' . $this->buttonsLabel[$buttonName] . '" aria-hidden="true">'
            . '&nbsp;'
            . '</span>'
            . '</a>';
    }

    /**
     * @param array $buttons
     *
     * @return $this
     */
    public function setButtons(array $buttons): static
    {
        $this->buttons = $buttons;

        return $this;
    }

    /**
     * @param RequestStack $requestStack
     *
     * @return $this
     */
    public function setRequest(RequestStack $requestStack): static
    {
        $this->request = $requestStack->getCurrentRequest();

        return $this;
    }

    /**
     * @param array $hiddenButtons
     *
     * @return $this
     */
    public function setHiddenButtons(array $hiddenButtons): static
    {
        $this->hiddenButtons = $hiddenButtons;
        return $this;
    }
}