<?php

namespace Wizclumsy\CMS\Panels\Traits;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Pagination\LengthAwarePaginator;

trait Index
{
    use Sortable, Reorderable, Filterable, Toggable, Exportable;

    protected $items;

    public function prepareIndex()
    {
        $updateUrl = route("{$this->routePrefix}.update", ':id');

        if ($this->model->importable) {
            $addResourceUrl = route("{$this->routePrefix}.import");
            $addResourceLabel = trans('clumsy::buttons.import');
        } else {
            $addResourceUrl = route("{$this->routePrefix}.create");
            $addResourceLabel = trans('clumsy::buttons.add');
        }

        if ($this->isChild()) {
            $addResourceUrl = app('clumsy.http')->queryStringAdd($addResourceUrl, 'parent', $this->getParentModelId());
        }

        $this->setData([
            'title'               => $this->getLabelPlural(),
            'updateUrl'           => $updateUrl,
            'suppressAddResource' => $this->suppressAddResource(),
            'addResourceUrl'      => $addResourceUrl,
            'addResourceLabel'    => $addResourceLabel,
        ]);
    }

    public function loadItems()
    {
        if (!$this->itemsLoaded()) {
            $items = $this->query->getPaged($this->itemsPerPage());
            $this->setItems($items);
            $this->setData('items', $items);
        }

        $pagination = $items instanceof LengthAwarePaginator ? ($items->hasPages() ? $items : null) : null;
        if (!is_null($pagination)) {
            if ($this->isChild()) {
                $pagination = $pagination->appends(['show' => $this->resourceName()]);
            }
            $pagination = $pagination->render();
        }
        $this->setData('pagination', $pagination);
    }

    public function setItems($items)
    {
        $this->items = $items;
        $this->itemsLoaded = true;

        return $this;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function itemsPerPage()
    {
        $perPage = $this->getOptionalProperty('itemsPerPage', config('clumsy.cms.per-page'));

        if ($perPage === 'inherit') {
            return $this->model->getPerPage();
        }

        return $perPage;
    }

    public function rowClass($item)
    {
        return '';
    }

    public function cellClass(Eloquent $item, $column)
    {
        $classes[] = 'cell-'.$column;

        if ($this->isEditableInline($column)) {
            $classes[] = 'cell-editable-inline';
        }

        if ($item->isCasted($column)) {
            $classes[] = 'cell-'.$item->castAs($column);
        }

        return implode(' ', $classes);
    }

    public function editableInline()
    {
        return $this->getOptionalProperty('editableInline', []);
    }

    public function isEditableInline($column)
    {
        return in_array($column, $this->editableInline());
    }

    public function columnTitle($resource, $column, $name)
    {
        if ($this->avoidHTML()) {
            return $name;
        }

        $url = route("{$this->routePrefix}.sort");
        $class = '';
        $html = '';

        if (session()->has("clumsy.order.$resource")) {
            if ($column === head(session("clumsy.order.$resource"))) {
                $direction = last(session("clumsy.order.$resource"));

                $class = "active $direction";
                $html = '<span class="caret"></span>';

                if ('desc' === $direction) {
                    $url = app('clumsy.http')->queryStringAdd($url, 'reset');
                } else {
                    $url = app('clumsy.http')->queryStringAdd($url, 'column', $column);
                    $url = app('clumsy.http')->queryStringAdd($url, 'direction', 'desc');
                }
            } else {
                $url = app('clumsy.http')->queryStringAdd($url, 'column', $column);
                $url = app('clumsy.http')->queryStringAdd($url, 'direction', 'asc');
            }
        } else {
            $url = app('clumsy.http')->queryStringAdd($url, 'column', $column);
            $url = app('clumsy.http')->queryStringAdd($url, 'direction', 'asc');
        }

        $html = "<a href=\"{$url}\" class=\"{$class}\">{$name}</a>{$html}";

        return $html;
    }

    public function columnValue(Eloquent $item, $column)
    {
        $value = $item->$column;

        if (!$item->hasGetMutator($column)) {
            if ($this->isEditableInline($column) && $this->useHTML()) {
                if ($item->isCasted($column)) {
                    switch ($item->castAs($column)) {
                        case 'bool':
                        case 'boolean':
                            return $this->inlineBooleanColumnValue($item, $column);
                    }
                }
            }

            if ($item->isCasted($column) && in_array($item->castAs($column), ['bool', 'boolean'])) {
                return $this->booleanColumnValue($item, $column);
            }
        }

        if ($value === false || $value === null) {
            $value = $this->columnEmptyValuePlaceholder();
        }

        $url = route("{$this->routePrefix}.edit", $item->id);

        $value = $this->sanitizeColumnValue($value);

        return $this->is('gallery') || $this->avoidHTML() ? $value : "<a href=\"{$url}\">{$value}</a>";
    }

    public function columnEmptyValuePlaceholder()
    {
        return $this->avoidHTML() ? '' : '&nbsp;';
    }

    public function sanitizeColumnValue($value)
    {
        return e($value);
    }

    public function inlineBooleanColumnValue(Eloquent $item, $column)
    {
        $method = $this->is('gallery') ? 'booleanCaption' : 'booleanCell';

        return $this->{$method}($column, $item->$column, [
            'id'         => "ei-{$column}-{$item->id}",
            'setClass'   => 'editable-inline',
            'dataId'     => "{$item->id}",
            'dataColumn' => "{$column}",
        ], $this->columnName($column));
    }

    public function booleanCell($name, $isChecked, $options = '')
    {
        return app('clumsy.field')
                ->checkbox($name, null, $options)
                ->checked($isChecked)
                ->setGroupClass(null)
                ->noLabel();
    }

    public function booleanCaption($name, $isChecked, $options = '', $label = null)
    {
        return app('clumsy.field')
                ->checkbox($name, $label, $options)
                ->checked($isChecked);
    }

    public function booleanColumnValue(Eloquent $item, $column)
    {
        return $item->$column == 1 ? trans('clumsy::fields.yes') : trans('clumsy::fields.no');
    }

    public function suppressAddResource()
    {
        return $this->getOptionalProperty('suppressAddResource', false);
    }
}
