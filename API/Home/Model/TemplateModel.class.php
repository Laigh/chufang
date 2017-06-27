<?php
namespace Home\Model;

use Think\Model;
class TemplateModel extends Model{
    public $template_id = '';

    public function __construct($template_id){
        $this->template_id = intval($template_id);
    }

    /*
     * 模板列表
     */
    public function template_list($data){
        foreach($data as $key=>$value){
            $goods_list = M('template_goods')->where("template_id='".$value['template_id']."'")->select();
            $template_list[$key]['template_id']         = $value['template_id'];
            $template_list[$key]['template_name']       = $value['template_name'];
            $template_list[$key]['remarks']             = $value['remarks'];
            $template_list[$key]['user_id']             = $value['user_id'];
            $template_list[$key]['goods_list']          = empty($goods_list) ? '':$goods_list;
        }

        return $template_list;
    }

    /*
     * 检查并整理商品数据
     */
    public function Handle_goods($goods_list){
        foreach($goods_list as $key=>$value){
            foreach($value as $k=>$v){
                /*检查是否有参数为空的情况*/
                if(empty($value[$k])){
                   return false;
                }else{
                    /*给商品添加模板ID*/
                    $arr[$key]['template_id'] = $this->template_id;
                    $arr[$key][$k] = trim($v);
                }
            }
        }

        return $arr;
    }

    /*
     * 检查模板是否已存在该商品
     */
    public function update_goods($goods_arr){
        /*取得模板原有商品*/
        foreach($goods_arr as $key=>$value){
            if(empty($value['rec_id'])){
                /*如果rec_id为空添加数据*/
                $goods_arr[$key]['rec_id'] = M('template_goods')->add($value);
            }else{
                /*如果rec_id不为空修改数据*/
                M('template_goods')->save($value);
            }
        }
        $goods = M('template_goods')->where("template_id='$this->template_id'")->field('rec_id')->select();
        foreach($goods as $value){
            $old_rec_id[] = $value['rec_id'];
        }
        foreach($goods_arr as $value){
            $new_rec_id[] = $value['rec_id'];
        }
        $remove = array_diff($old_rec_id,$new_rec_id);            // 要删除的商品
        if(!empty($remove)){
            /*先删除分类下的商品*/
            M('template_goods')->where("rec_id in(".implode(',',$remove).")")->delete();
        }

        return true;
    }

    public function get_template_detail($user_id){
        $model = M('template');
        $data = $model->where("template_id='$this->template_id' and user_id='$user_id'")->field('template_id,template_name,remarks')->find();
        if($data){
            $goods_list = M('template_goods')->where("template_id='$this->template_id'")->select();
            foreach($goods_list as $key=>$value){
                $result[$value['goods_type']][]    = $value;
            }

            $goods_list = '';
            foreach($result as $key=>$value){
                $goods_list[$key]['type_name']          = '类型名称';
                $goods_list[$key]['list']               = $value;
            }

            $data['goods_list'] = $goods_list;
        }else{
            $data = false;
        }

        return $data;
    }

}