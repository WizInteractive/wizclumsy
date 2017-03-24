<?php

namespace Wizclumsy\CMS\Controllers;

use Wizclumsy\CMS\Auth\Overseer;
use Wizclumsy\CMS\Controllers\AdminController;
use Illuminate\Foundation\Application;

class UsersController extends AdminController
{
    protected $auth;

    public function __construct(Application $app, Overseer $auth)
    {
        $this->auth = $auth;
        parent::__construct($app);
    }

    public function modelClass($resource = null)
    {
        return $this->auth->getUserModel();
    }

    public function loadPanel($action = null)
    {
        parent::loadPanel($action);

        $this->panel
                ->columns([
                    'name'  => trans('clumsy::fields.name'),
                    'email' => trans('clumsy::fields.email'),
                ])
                ->setData([
                    'groups' => $this->auth->getAvailableGroups(),
                ]);
    }

    public function edit($id)
    {
        $this->loadPanel();

        if ($this->auth->user()->id == $id) {
            $this->panel->suppressDelete = true;
        }

        return parent::edit($id);
    }

    public function triggerUpdate($item, $data)
    {
        if (array_get($data, 'new_password')) {
            $data['password'] = $data['new_password'];
        }

        return parent::triggerUpdate($item, $data);
    }

    public function destroy($id)
    {
        if ($this->auth->user()->id == $id) {
            return back()->withAlert([
               'warning' => trans('clumsy::alerts.user.suicide'),
            ]);
        }

        return parent::destroy($id);
    }
}
