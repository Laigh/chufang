<?php
namespace Home\Controller;

use Home\Common\Util\Common_func;
use Think\Controller;
class BaseController extends Controller{
     /*检查用户登录*/
    public function _initialize(){
        if(isset($_SESSION['user_info']['user_id'])){
            $is_expires = time() > $_SESSION['user_info']['expires'] ? false : true;
            if(!$is_expires){
                $status = array(
                    'status' => array(
                        'succeed' => 10,
                        'error_code' => 110,
                        'error_desc' => '登录已过期，请重新登录'
                    )
                );
                $this->ajaxReturn($status);
            }else{
                /*设置session过期时间*/
                $expire = ini_get('session.gc_maxlifetime');
                if($expire != 10800){
                    $this->start_session('10800');
                }

                $_SESSION['user_info']             = $_SESSION['user_info'];
                $_SESSION['user_info']['expires']  = time()+60*60*3;           //修改过期时间
            }
        }else{
            $status = array(
                'status' => array(
                    'succeed' => 10,
                    'error_code' => 110,
                    'error_desc' => '您还没有登录，请先完成登录'
                ),
            );
            $this->ajaxReturn($status);
        }
    }

    /*
     * 设置session过期时间
     */
    function start_session($expire = 0){
        if($expire == 0){
            $expire = ini_get('session.gc_maxlifetime');
        }else{
            ini_set('session.gc_maxlifetime', $expire);
        }
        if(empty($_COOKIE['PHPSESSID'])){
            session_set_cookie_params($expire);
            session_start();
        }else{
            session_start();
            setcookie('PHPSESSID', session_id(), time() + $expire);
        }
    }
}