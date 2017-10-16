<?php

namespace Huubook\Modules\Api\Controllers;


use Phalcon\Version;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        echo 'version : ' . Version::get();
    }

}

