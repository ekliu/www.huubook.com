<?php

namespace Huubook\Modules\Frontend\Controllers;


use Huubook\Models\BaseModel;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
        echo 'fontend index';
    }

    public function testAction()
    {
        $result = false;
        if (!$result) {
            $data = json_encode([
                'code' => 200,
                'massages' => 'false',
                'data' => [1, 2, 34]
            ]);
            throw new \Exception($data);
        }


        //db test
        $model = new BaseModel();
        $data = $model->table('member')->wyFirst();
        print_r($data);
        echo "DB  OK  <br>";

        //redis
        $cache_key = md5('redistest');
        $res = $this->cache->get($cache_key);
        if (empty($res)) {
            $this->cache->save($cache_key, time(), 5); //秒
        } else {
            print_r($res);
            echo "   cache OK  <br>";
        }

    }

    public function infoAction()
    {
        phpinfo();
    }

}

