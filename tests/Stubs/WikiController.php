<?php

namespace Tests\Stubs;

class WikiController
{
    public function getIndex()
    {
        return 'wiki-index';
    }

    public function getCreate()
    {
        return 'wiki-create';
    }

    public function postCreate()
    {
        return 'wiki-create-post';
    }

    public function missingMethod($parameters = [])
    {
        return 'wiki-missing';
    }
}
