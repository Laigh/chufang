<?php
namespace Home\Controller;

use Home\Common\Util\Common_func;
use Think\Controller;
class UserController extends Controller {

    public function signup(){
        $user_name = I('post.user_name');
        $password  = I('post.password');

        /*检验表单*/
        if(empty($user_name)){
            Common_func::outPut('用户名不能为空');
        }
        if(empty($password)){
            Common_func::outPut('密码不能为空');
        }

        /*检查用户名是否已经存在*/
        $model = M('users');
        $count = $model->where("user_name='$user_name'")->count();
        if($count > 0){
            Common_func::outPut('用户名已存在，无法完成注册');
        }

        /*整理用户数据*/
        $data['salt']           = rand(1,99999);
        $data['user_name']      = $user_name;
        $data['password']       = md5(md5($password).$data['salt']);
        $data['last_ip']        = $_SERVER['REMOTE_ADDR'];
        $data['visit_count']    = 1;
        $data['reg_time']       = date('Y-m-d H:i:s');
        $data['last_time']      = date('Y-m-d H:i:s');

        /*添加到数据库*/
        $user_id = $model->add($data);

        /*保存到session*/
        $arr['user_id']             = $user_id;
        $arr['user_name']           = $user_name;
        $arr['expires']             = time()+60*60*3;           //过期时间
        $_SESSION['user_info']      = $arr;

        /*返回数据*/
        Common_func::outPut($arr);

    }

    public function signin(){
        $user_name = I('post.user_name');
        $password  = I('post.password');

        /*检验表单*/
        if(empty($user_name)){
            Common_func::outPut('用户名不能为空');
        }
        if(empty($password)){
            Common_func::outPut('密码为空');
        }

        /*取得用户信息*/
        $model = M('users');
        $user_info = $model->where("user_name='$user_name'")->field('user_id,password,salt,visit_count')->find();

        /*检查用户是否存在*/
        if(!$user_info){
            Common_func::outPut('用户名不存在');
        }

        /*检查密码是否正确*/
        if(!($user_info['password'] == md5(md5($password).$user_info['salt']))){
            Common_func::outPut('密码不正确');
        }

        /*整理用户数据*/
        $data['salt']           = rand(1,99999);
        $data['user_name']      = $user_name;
        $data['password']       = md5(md5($password).$data['salt']);
        $data['last_ip']        = $_SERVER['REMOTE_ADDR'];
        $data['visit_count']    = $user_info['visit_count']+1;
        $data['last_time']      = date('Y-m-d H:i:s');

        /*更新数据*/
        $model->where("user_id='".$user_info['user_id']."'")->save($data);

        /*保存到session*/
        $arr['user_id']             = $user_info['user_id'];
        $arr['user_name']           = $user_name;
        $arr['expires']             = time()+60*60*3;           //过期时间
        $_SESSION['user_info']      = $arr;

        /*返回数据*/
        Common_func::outPut($_SESSION);
    }


}