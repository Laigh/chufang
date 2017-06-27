<?php
namespace Home\Model;

use Think\Model;
class OrderModel extends Model{
    /*
     *获取地区
     */
    public function get_region($parent_id=1){
        /*查询数据*/
        $order_list = M('region')->where("parent_id = '$parent_id'")->field('region_id,region_name,parent_id')->select();

        return $order_list;
    }

    /*
     *获取地区名
     */
    public function get_region_name($region_id=1){
        /*查询数据*/
        $order_list = M('region')->where("region_id = '$region_id'")->field('region_id,region_name,parent_id')->find();

        return $order_list;
    }

    /*
     *返回患者区域
     */
    public function get_sufferer_region($province,$city,$district){
        if(!empty($province)){
            $province = self::get_region_name($province);
            $region['province'] = $province['region_id'];
            $region['province_name'] = $province['region_name'];
        }
        if(!empty($city)){
            $city = self::get_region_name($city);
            $region['city'] = $city['region_id'];
            $region['city_name'] = $city['region_name'];
        }
        if(!empty($district)){
            $district = self::get_region_name($district);
            $region['district'] = $district['region_id'];
            $region['district_name'] = $district['region_name'];
        }

        return $region;
    }

    /*
     *图片上传
     */
    public function upload_img($dir='Uploads/prescription/'){

        if(!is_dir('./'.$dir)){
            if(!mkdir('./'.$dir,0777,true)){
                $result['return'] = false;
                $result['error_desc'] = '创建目录失败';
                return $result;
            }
        }

        $upload = new \Think\Upload();
        $upload->maxSize   =     2097152 ;
        $upload->exts      =     array('jpg','gif','png','jpeg');
        $upload->rootPath  =     './'.$dir;
        $upload->savePath  =     '';

        $info = $upload->upload();

        if(!$info) {
            $result['return'] = false;
            $result['error_desc'] = $upload->getError();
            return $result;
        }else{
            foreach($info as $key=>$value){
                $url =  $value['savepath'].$value['savename'];
                $imgurl[] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/'.$dir.$url;
            }

            $result['return'] = true;
            $result['data'] = $imgurl;

            return $result;
        }
    }

    /**
     * 生成订单号
     */
    public function get_order_sn(){
        /* 选择一个随机的方案 */
        mt_srand((double) microtime() * 1000000);
        return 'B'.date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    public function add_order_goods($goods_list,$order_id){
        //先添加分类
        foreach($goods_list as $key=>$value){
            /*计算商品总价*/
            $count_price = 0;
            foreach($value['list'] as $val){
                $count_price = $count_price+($val['goods_number']*$val['goods_price']);
            }

            /*整理数据*/
            $order_type_info['order_id']        = $order_id;
            $order_type_info['count_price']     = $count_price;
            $order_type_info['number']          = $value['number'];
            $order_type_info['preparation']     = $value['preparation'];
            $order_type_info['goods_type']      = $value['goods_type'];
            $order_type_info['type_name']       = $value['type_name'];

            /*添加数据*/
            $goods_list[$key]['type_id'] = M('order_type')->add($order_type_info);
        }

        /*处理商品*/
        foreach($goods_list as $key=>$value){
            /*处理分类下的商品*/
            foreach($value['list'] as $k=>$val){
                $goods_info['type_id'] = $value['type_id'];
                $goods_info['goods_id'] = $val['goods_id'];
                $goods_info['goods_name'] = $val['goods_name'];
                $goods_info['goods_attr'] = $val['goods_attr'];
                $goods_info['goods_number'] = $val['goods_number'];
                $goods_info['goods_unit'] = $val['goods_unit'];
                $goods_info['goods_price'] = $val['goods_price'];

                /*添加数据*/
                $goods_list[$key]['list'][$k]['rec_id'] = $val['rec_id'] = M('order_goods')->add($goods_info);
            }
        }

        return $goods_list;
    }

    public function update_order_goods($goods_list,$order_id){
        /*处理分类*/
        foreach($goods_list as $key=>$value){
            /*计算商品总价*/
            $count_price = 0;
            foreach($value['list'] as $val){
                $count_price = $count_price+($val['goods_number']*$val['goods_price']);
            }

            /*整理数据*/
            $order_type_info['order_id']        = $order_id;
            $order_type_info['count_price']     = $count_price;
            $order_type_info['number']          = $value['number'];
            $order_type_info['preparation']     = $value['preparation'];
            $order_type_info['goods_type']      = $value['goods_type'];
            $order_type_info['type_name']       = $value['type_name'];

            /*检查是修改分类还是添加分类*/
            if(empty($value['type_id'])){
                /*添加数据*/
                $goods_list[$key]['type_id'] = $value['type_id'] = M('order_type')->add($order_type_info);
            }else{
                /*修改数据*/
                M('order_type')->where("type_id='".$value['type_id']."'")->save($order_type_info);
            }
            $new_type_id[] = $value['type_id'];
        }
        /*取得订单下的分类ID*/
        $old_type = M('order_type')->where("order_id='".$order_id."'")->field("type_id")->select();
        foreach($old_type as$value){
            $old_type_id[] = $value['type_id'];
        }
        $remove = array_diff($old_type_id,$new_type_id);            // 要删除的分类
        /*删除分类*/
        if(!empty($remove)){

            /*先删除分类下的商品*/
            M('order_goods')->where("type_id in(".implode(',',$remove).")")->delete();
            M('order_type')->where("type_id in(".implode(',',$remove).")")->delete();
        }

        /*处理商品*/
        foreach($goods_list as $key=>$value){
            /*处理分类下的商品*/
            foreach($value['list'] as $k=>$val){
                $goods_info['type_id'] = $value['type_id'];
                $goods_info['goods_id'] = $val['goods_id'];
                $goods_info['goods_name'] = $val['goods_name'];
                $goods_info['goods_attr'] = $val['goods_attr'];
                $goods_info['goods_number'] = $val['goods_number'];
                $goods_info['goods_unit'] = $val['goods_unit'];
                $goods_info['goods_price'] = $val['goods_price'];

                /*检查是修改还是添加*/
                if(empty($value['rec_id'])){
                    /*添加数据*/
                    $goods_list[$key]['list'][$k]['rec_id'] = $val['rec_id'] = M('order_goods')->add($goods_info);
                }else{
                    /*修改数据*/
                    M('order_goods')->where("rec_id='".$val['rec_id']."'")->save($goods_info);
                }
                $new_rec_id[] = $val['rec_id'];
            }

            /*取得分类下数据库商品ID*/
            $old_rec = M('order_goods')->where("type_id='".$value['type_id']."'")->field("rec_id")->select();
            foreach($old_rec as $value){
                $old_rec_id[] = $value['rec_id'];
            }
            $remove = array_diff($old_rec_id,$new_rec_id);            // 要删除的商品
            if(!empty($remove)){
                /*先删除分类下的商品*/
                M('order_goods')->where("rec_id in(".implode(',',$remove).")")->delete();
            }
        }

        return $goods_list;
    }

    public function add_order_info($order_info,$sufferer_id){
        /*直接添加*/
        $order_info['order_sn'] = self::get_order_sn();
        $order_info['status'] = 1;
        $order_info['user_id'] = $_SESSION['user_info']['user_id'];
        $order_info['sufferer_id'] = empty($sufferer_id) ? '': $sufferer_id;
        $order_info['is_prescription'] = 0;
        $order_info['add_time'] = date('Y-m-d H:i:s');

        /*添加数据*/
        $order_info['order_id'] = M('order_info')->add($order_info);

        return $order_info;
    }

    public function update_order_info($order_info,$sufferer_id){
        /*修改订单*/
        $order_info['status'] = 1;
        $order_info['user_id'] = $_SESSION['user_info']['user_id'];
        $order_info['sufferer_id'] = empty($sufferer_id) ? $sufferer_id: '';
        $order_info['is_prescription'] = 0;

        /*修改数据*/
        M('order_info')->where("order_id='".$order_info['order_id']."'")->save($order_info);

        return $order_info;
    }

    public function handle_sufferer_info($sufferer_info){
        /*保存患者信息*/
        $sufferer_info['user_id']       = $_SESSION['user_info']['user_id'];

        /*如果有患者ID就修改，没有就保存*/
        if(empty($sufferer_info['sufferer_id'])){
            $sufferer_info['add_time']      = date('Y-m-d H:i:s');
            $sufferer_info['sufferer_id']   = M('user_sufferer')->add($sufferer_info);
            unset($sufferer_info['add_time']);
        }else{
            M('user_sufferer')->where("sufferer_id='".$sufferer_info['sufferer_id']."'")->save($sufferer_info);
        }

        return $sufferer_info;
    }
}