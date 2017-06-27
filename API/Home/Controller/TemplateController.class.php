<?php
namespace Home\Controller;

use Home\Common\Util\Common_func;
use Home\Model\TemplateModel;
class TemplateController extends BaseController {
    public function _initialize() {
        parent::_initialize();
    }

    /*
     * 模板列表
     */
    public function template_list(){
        $user_id = intval(I('post.user_id'));

        if(empty($user_id)){
            Common_func::outPut('请输入用户ID');
        }elseif($user_id != $_SESSION['user_info']['user_id']){
            Common_func::outPut('请输入登录用户ID');
        }

        $model = M('template');
        $data = $model->where("user_id='$user_id'")->field('template_id,template_name,remarks,user_id')->order('template_id desc')->select();
        /*如果用户还没有模板数据返回空*/
        if(empty($data)){
            Common_func::outPut(array());
        }

        $template_list = TemplateModel::template_list($data);

        /*返回数据*/
        Common_func::outPut($template_list);
    }

    /*
     * 创建/修改 模板
     */
    public function create_template(){
        /*获取参数*/
        $template_id    = empty(I('post.template_id')) ? '': intval(I('post.template_id'));          //如果是创建模板，为空值
        $template_name  = trim(I('post.template_name'));
        $remarks        = trim(I('post.remarks'));
        $goods_list     = json_decode(htmlspecialchars_decode(I('post.goods_list')),true);
        $user_id        = $_SESSION['user_info']['user_id'];


        /*检验数据*/
        if(empty($template_name)){
            Common_func::outPut('模板名为空');
        }
        if(empty($remarks)){
            Common_func::outPut('备注为空');
        }
        if(empty($goods_list)){
            Common_func::outPut('商品为空');
        }

        $is_add = empty($template_id) ? true: false;

        /*整理模板数据*/
        $data['template_name']  = $template_name;
        $data['remarks']        = $remarks;
        $data['user_id']        = $user_id;

        if($is_add){
            /*新建模板*/
            $data['add_time']           = date('Y-m-d H:i:s');
            $template_id = M('template')->add($data);
        }else{
            /*修改模板*/
            $data['update_time']        = date('Y-m-d H:i:s');
            M('template')->where("template_id='".$template_id."'")->save($data);
        }

        /*检查并整理商品数据*/
        $TemplateModel = new TemplateModel($template_id);
        $goods_arr = $TemplateModel->Handle_goods($goods_list);
        if(!$goods_arr){
            Common_func::outPut('商品参数存在空值，无法提交数据');
        }

        if(!$is_add){
            /*检查模板商品是否和提交商品一样 不一样进行相应处理*/
           $TemplateModel->update_goods($goods_arr);
        }else{
            /*保存数据*/
            M('template_goods')->addAll($goods_arr);
        }

        /*返回数据*/
        if($template_id){
            $return['template_id'] = $template_id;
            Common_func::outPut($return);
        }else{
            Common_func::outPut('添加模板失败');
        }
    }

    /*模块数据*/
    public function template_detail(){
        /*获取参数*/
        $template_id    = I('post.template_id');
        $user_id        = $_SESSION['user_info']['user_id'];

        if(empty($template_id)){
            Common_func::outPut('模板ID不能为空');
        }

        /*查询数据*/
        $TemplateModel = new TemplateModel($template_id);
        $data = $TemplateModel->get_template_detail($user_id);
        if(!$data){
            Common_func::outPut('用户不存在该模板');
        }

        Common_func::outPut($data);
    }

    /*
     * 删除模板
     */
    public function remove_template(){
        $template_id = intval(I('post.template_id'));
        $user_id = $_SESSION['user_info']['user_id'];

        /*检查数据是否为空*/
        if(empty($template_id)){
            Common_func::outPut('模板ID不能为空');
        }

        $model = M('template');

        /*检查模板是否属于该用户*/
        $count = $model->where("user_id='$user_id' and template_id='$template_id'")->count();
        if(empty($count)){
            Common_func::outPut('用户不存在该模板');
        }

        /*先删除商品*/
        M('template_goods')->where("template_id='$template_id'")->delete();

        /*删除模板*/
        $model->where("template_id='$template_id'")->delete();

        Common_func::outPut(array());
    }
}