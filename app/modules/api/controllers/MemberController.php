<?php

namespace Huubook\Modules\Api\Controllers;

class MemberController extends ControllerBase
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function indexAction()
    {
        print_r($this->request->get());
        echo 'indexAction';
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function createAction()
    {
        print_r($this->request->get());
        echo 'createAction';
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function storeAction()
    {
        print_r($this->request->getPost());
        echo 'storeAction';
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function showAction($id)
    {
        print_r($this->request->get());
        echo 'showAction' . $id;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function editAction($id)
    {
        print_r($this->request->get());
        echo 'editAction' . $id;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function updateAction($id)
    {
        print_r($this->request->getPut());
        echo 'updateAction ' . $id;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroyAction($id)
    {
        print_r($this->request->getPut());
        echo 'destroyAction ' . $id;
    }

}
