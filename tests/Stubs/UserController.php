<?php

namespace Tests\Stubs;

class UserController
{
    public function anyIndex()
    {
        return 'any-index';
    }

    public function getShow($id)
    {
        return "get-show-{$id}";
    }

    public function getAdminProfile()
    {
        return 'get-admin-profile';
    }

    public function postProfile()
    {
        return 'post-profile';
    }

    public function putUpdate($id)
    {
        return "put-update-{$id}";
    }

    public function deleteDestroy($id)
    {
        return "delete-destroy-{$id}";
    }

    public function getMiddleware()
    {
        return [];
    }

    public function nonRouteMethod()
    {
        return 'not-a-route';
    }
}
