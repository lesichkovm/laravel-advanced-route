<?php

namespace Tests\Stubs;

class ParameterController
{
    public function getIndex()
    {
        return 'param-index';
    }

    public function getAbout()
    {
        return 'param-about';
    }

    public function anyCatchAll($path)
    {
        return "param-catchall-{$path}";
    }

    public function getOptional($id = null)
    {
        return "param-optional-{$id}";
    }
}
