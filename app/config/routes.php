<?php

$router = $di->getRouter();

foreach ($application->getModules() as $key => $module) {
    $namespace = preg_replace('/Module$/', 'Controllers', $module["className"]);
    $router->add('/' . $key . '/:params', [
        'namespace' => $namespace,
        'module' => $key,
        'controller' => 'index',
        'action' => 'index',
        'params' => 1
    ])->setName($key);
    $router->add('/' . $key . '/:controller/:params', [
        'namespace' => $namespace,
        'module' => $key,
        'controller' => 1,
        'action' => 'index',
        'params' => 2
    ]);
    $router->add('/' . $key . '/:controller/:action/:params', [
        'namespace' => $namespace,
        'module' => $key,
        'controller' => 1,
        'action' => 2,
        'params' => 3
    ]);


    //API 模块重新定义 RestFull 规则

    //GET 	/resource 	index 	resource.index
    //GET 	/resource/create 	create 	resource.create
    //POST 	/resource 	store 	resource.store
    //GET 	/resource/{resource} 	show 	resource.show
    //GET 	/resource/{resource}/edit 	edit 	resource.edit
    //PUT/PATCH 	/resource/{resource} 	update 	resource.update
    //DELETE 	/resource/{resource} 	destroy 	resource.destroy



    if ($key == 'api') {

        //show
        $router->addGet('/' . $key . '/:controller/{id}', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'show',
            'params' => 2
        ]);

        //create
        $router->addGet('/' . $key . '/:controller/create/:params', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'create',
            'params' => 2
        ]);


        //store
        $router->addPost('/' . $key . '/:controller/:params', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'store',
            'params' => 3
        ]);

        //edit
        $router->addGet('/' . $key . '/:controller/{id}/edit/:params', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'edit',
            'params' => 2
        ]);

        //update
        $router->addPut('/' . $key . '/:controller/{id}/:params', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'update',
            'params' => 2
        ]);

        //delete
        $router->addPut('/' . $key . '/:controller/{id}/delete', [
            'namespace' => $namespace,
            'module' => $key,
            'controller' => 1,
            'action' => 'destroy',
            'params' => 2
        ]);
    }
}


