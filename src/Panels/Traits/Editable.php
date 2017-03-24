<?php

namespace Wizclumsy\CMS\Panels\Traits;

use UnexpectedValueException;
use Collective\Html\FormFacade as Form;
use Wizclumsy\CMS\Panels\Traits\Translatable;
use Wizclumsy\CMS\Panels\Traits\Location;
use Wizclumsy\Utils\Facades\Field;

trait Editable
{
    use Translatable, Location;

    protected $item;

    public function prepareEditable()
    {
        $title = $this->action === 'create' ? 'clumsy::titles.new_item' : 'clumsy::titles.edit_item';
        $this->setData([
            'title'          => trans($title, ['resource' => $this->getLabel()]),
            'id'             => request()->route()->parameter($this->resourceParameter()),
            'backLink'       => route("{$this->routePrefix}.index"),
            'showResource'   => request('show', request('reorder')),
            'suppressDelete' => $this->suppressDelete(),
            'fields'         => $this->fields(),
            'buttons'        => $this->buttons(),
        ]);
    }

    public function loadItems()
    {
        if (!$this->itemsLoaded()) {
            $this->loadItemById();
        }

        foreach ($this->item->requiredBy() as $required) {
            if (!method_exists($this->item, $required)) {
                throw new UnexpectedValueException('The model\'s required resources must be defined by a dynamic property with queryable Eloquent relations');
            }
        }

        if ($this->model->isNested()) {
            $backLink = $this->persistResourceOn($this->getParentModelEditUrl());
            $fields = $this->getData('fields');
            array_push($fields, Form::hidden($this->model->parentIdColumn(), $this->getParentModelId()));
            $this->setData(compact('backLink', 'fields'));
        }
    }

    public function loadItemById($id = null)
    {
        $item = is_null($id) ? $this->model : $this->query->findOrFail($id);
        $this->setItem($item);
        $this->setData('item', $item);
    }

    public function setItem($item)
    {
        $this->item = $item;
        $this->itemsLoaded = true;

        return $this;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function suppressDelete()
    {
        return $this->getOptionalProperty('suppressDelete', $this->isExternalResource());
    }

    public function deleteButton()
    {
        $parameters = [
            'method' => "DELETE",
            'url'    => route("{$this->routePrefix}.destroy", $this->item->id),
            'class'  => "delete-form btn-outside pull-left ".$this->resourceName(),
        ];

        return Form::open($parameters).Form::close();
    }

    public function isExternalResource()
    {
        return $this->model->importable;
    }

    public function fields()
    {
        return [];
    }

    public function buttons()
    {
        return [];
    }

    public function pivotField($resource, $label, $availableOptions, $settings = '')
    {
        $output = Field::dropdown("{$resource}Ids[]", $label, $settings)
                       ->options($availableOptions)
                       ->multiple()
                       ->addClass('pivot-field')
                       ->data('placeholder', ' ')
                       ->data('no_results_text', trans('clumsy::fields.filter-no-results'));

        $output .= Field::hidden("{$resource}IdsFlag", true);
        return $output;
    }
}
