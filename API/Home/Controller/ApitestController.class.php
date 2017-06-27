<?php
namespace Home\Controller;

use Home\Common\Util\TpApi;
use Think\Controller;
class ApitestController extends Controller {

    public function index(){
        $file = 'TpApi.json';
        $data = TpApi::GetData($file);

        $this->assign('data',$data);
        $this->display();
    }

    public function test($test = null){

    }
}