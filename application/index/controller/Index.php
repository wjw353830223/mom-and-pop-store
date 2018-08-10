<?php
namespace app\index\controller;
use think\Controller;
class Index extends Controller
{
    public function index()
    {
        //$this->redirect(url('Admin/index/index'));
        return $this->fetch();
    }
    public function order()
    {
        return $this->fetch();
    }
}
