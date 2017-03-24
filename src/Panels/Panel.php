<?php

namespace Wizclumsy\CMS\Panels;

use Illuminate\Session\Store as Session;
use Wizclumsy\CMS\Support\Bakery;
use Wizclumsy\CMS\Support\ResourceNameResolver;
use Wizclumsy\CMS\Support\ViewResolver;
use Wizclumsy\CMS\Facades\Clumsy;
use Wizclumsy\Assets\Facade as Asset;

abstract class Panel
{
    public $rendered;

    protected $data = [];

    protected $session;
    protected $bakery;
    protected $labeler;
    protected $view;

    protected $prefix;

    protected $hierarchy;
    protected $model;
    protected $routePrefix;

    protected $columns;

    protected $query;
    protected $itemsLoaded = false;

    protected $template;

    protected $label;
    protected $labelPlural;

    protected $parent;
    protected $children = [];

    protected $format = 'html';

    public function __construct(
        Session $session,
        Bakery $bakery,
        ResourceNameResolver $labeler,
        ViewResolver $view
    ) {
        $this->session = $session;

        $this->bakery = $bakery;
        $this->labeler = $labeler;

        $this->view = clone $view;

        $this->bakery->setPrefix(Clumsy::prefix());
    }

    protected function getOptionalProperty($key, $default = null)
    {
        if (property_exists($this, $key)) {
            return $this->{$key};
        }

        return $this->getData($key, $default);
    }

    public function getBakery()
    {
        return $this->bakery;
    }

    public function hierarchy($hierarchy)
    {
        $this->hierarchy = $hierarchy;

        $this->model = $this->hierarchy['current'];

        $this->routePrefix = $this->resourceName();

        $this->view->clearLevels()->setDomain($this->resourceName())->pushLevel($this->action);

        return $this;
    }

    public function getHierarchy()
    {
        return $this->hierarchy;
    }

    public function getParentModel()
    {
        return last($this->hierarchy['parents']);
    }

    public function getParentModelId()
    {
        $parentModel = $this->getParentModel();
        if (is_object($parentModel)) {
            return $parentModel->getKey();
        }
    }

    public function getParentModelUrl($action = 'index', $params = null)
    {
        $parentModel = $this->getParentModel();
        $parentResourceName = $parentModel->resourceName();
        $routePrefix = $this->prefix ? "{$this->prefix}.{$parentResourceName}" : $parentResourceName;
        return route("{$routePrefix}.{$action}", $params);
    }

    public function getParentModelEditUrl()
    {
        return $this->getParentModelUrl('edit', $this->getParentModelId());
    }

    public function nest(Panel $panel)
    {
        $panel->parent($this);
        $this->children[] = $panel;

        return $this;
    }

    public function parent(&$panel)
    {
        $this->parent = $panel;

        return $this;
    }

    public function children(array $children = [])
    {
        $this->children = $children;

        return $this;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function isChild()
    {
        return (bool)$this->parent;
    }

    public function isInheritable()
    {
        return $this->getOptionalProperty('inheritable', false);
    }

    public function prepare()
    {
        if ($this->isChild()) {
            $this->setData($this->parent->prepare()->getData());
        }

        $this->addContext('view', $this->action);

        $this->setData([
            'panel'       => $this,
            'view'        => $this->view,
            'action'      => $this->action,
            'type'        => $this->getType(),
            'model'       => $this->model,
            'resource'    => $this->resourceName(),
            'routePrefix' => $this->routePrefix,
            'columns'     => $this->getColumns(),
            'breadcrumb'  => $this->bakery->breadcrumb($this->hierarchy, $this->action),
            'isChild'     => $this->isChild(),
        ]);

        if (!$this->isChild()) {
            Asset::json('admin', [
                'resource' => $this->resourceName(),
            ]);
        }

        $this->prepareTraits();

        return $this;
    }

    protected function prepareTraits()
    {
        foreach (class_uses_recursive(get_called_class()) as $trait) {
            if (method_exists(get_called_class(), $method = 'prepare'.class_basename($trait))) {
                $this->$method();
            }
        }
    }

    protected function beforeRenderTraits()
    {
        foreach (class_uses_recursive(get_called_class()) as $trait) {
            if (method_exists(get_called_class(), $method = 'beforeRender'.class_basename($trait))) {
                $this->$method();
            }
        }
    }

    public function getType()
    {
        return property_exists($this, 'type') ? $this->type : str_slug(class_basename($this));
    }

    public function is($type)
    {
        return $type === $this->getType();
    }

    public function getModel()
    {
        return $this->model;
    }

    public function columns(array $columns = [])
    {
        $this->columns = $columns;

        return $this;
    }

    public function getBaseColumns()
    {
        return $this->columns ?: ['title' => trans('clumsy::fields.title')];
    }

    public function getColumns($columns = null)
    {
        if (is_null($columns)) {
            $columns = $this->getBaseColumns();
        }

        if (method_exists($this, 'prepareColumns')) {
            $columns = $this->prepareColumns($columns);
        }

        return $columns;
    }

    public function columnEquivalence()
    {
        return $this->getOptionalProperty('columnEquivalence', []);
    }

    public function allColumns()
    {
        return $this->getOptionalProperty('allColumns', []);
    }

    public function columnNames()
    {
        $columnNames = [];
        $equivalences = array_flip($this->columnEquivalence());
        $columns = $this->allColumns() + $this->getColumns();

        foreach (array_merge(array_keys($equivalences), array_keys($columns)) as $column) {
            $original = $column;

            // If we can't find the column name, look for an equivalence
            if (!isset($columns[$column])) {
                $column = $equivalences[$column];
            }

            // If we still can't find it, it could have been added dynamically, so ignore
            if (!isset($columns[$column])) {
                continue;
            }

            $columnNames[$original] = $columns[$column];
        }

        return $columnNames;
    }

    public function columnName($column)
    {
        return array_get($this->columnNames(), $column, $column);
    }

    public function query($query)
    {
        $this->query = $query;
        $this->itemsLoaded = false;

        return $this;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function itemsLoaded()
    {
        return $this->itemsLoaded;
    }

    public function addContext($key, $value)
    {
        $this->query->withAdminContext($key, $value);
        $this->model->setAdminContext($key, $value);

        return $this;
    }

    public function action($action)
    {
        $this->action = $action;

        return $this;
    }

    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    public function setData($values, $value = null)
    {
        if (!is_array($values)) {
            $values = [$values => $value];
        }

        foreach ($values as $key => $value) {
            array_set($this->data, $key, $value);
        }

        return $this;
    }

    public function getData($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->data;
        }

        return array_get($this->data, $key, $default);
    }

    public function template($template)
    {
        $this->template = $template;

        return $this;
    }

    public function inner()
    {
        return $this->template("inner-{$this->action}");
    }

    public function getTemplate()
    {
        return $this->template ?: $this->action;
    }

    public function persistResourceOn($url, $key = 'show')
    {
        return app('clumsy.http')->queryStringAdd($url, $key, $this->resourceName());
    }

    public function prepareAndRender($eager = false)
    {
        $this->prepare();

        if (method_exists($this, 'beforeRender')) {
            $this->beforeRender();
        }

        $this->beforeRenderTraits();

        if (method_exists($this, 'loadItems')) {
            $this->loadItems();
        }

        if (count($this->children)) {
            $this->renderChildren();
        }

        $this->rendered = view($this->view->resolve($this->getTemplate()), $this->getData());

        if ($eager) {
            // If eager-rendering, return rendered HTML instead of View object
            $this->rendered = (string)$this->rendered;
        }

        return $this->rendered;
    }

    public function render($eager = false)
    {
        if ($this->rendered) {
            return $this->rendered;
        }

        return $this->prepareAndRender($eager);
    }

    public function renderChildren()
    {
        array_walk($this->children, function (Panel &$child) {
            $child->render(true);
        });
    }

    public function resolve()
    {
        return response($this->render());
    }

    public function resourceParameter()
    {
        return $this->model->resourceParameter();
    }

    public function resourceName()
    {
        return $this->model->resourceName();
    }

    public function label($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel()
    {
        return $this->label ?: $this->labeler->displayName($this->model);
    }

    public function labelPlural($labelPlural)
    {
        $this->labelPlural = $labelPlural;

        return $this;
    }

    public function getLabelPlural()
    {
        return $this->labelPlural ?: $this->labeler->displayNamePlural($this->model);
    }

    public function labels($singular, $plural)
    {
        $this->label($singular);
        $this->labelPlural($plural);

        return $this;
    }

    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function useHTML()
    {
        return $this->format === 'html';
    }

    public function avoidHTML()
    {
        return !$this->useHTML();
    }

    /**
     * Convert the panel instance to JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the panel instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getData();
    }

    /**
     * Dynamically retrieve data on the panel.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getData($key);
    }

    /**
     * Dynamically set data on the panel.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setData($key, $value);
    }

    /**
     * Determine if an attribute exists on the panel.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Unset an attribute on the panel.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->data[$key]);
    }

    public function __toString()
    {
        return $this->render();
    }
}
