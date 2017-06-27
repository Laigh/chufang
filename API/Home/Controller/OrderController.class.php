<?php
namespace Home\Controller;

use Home\Common\Util\Common_func;
use Think\Page;
use Home\Model\OrderModel;
class OrderController extends BaseController {
    public function _initialize() {
        parent::_initialize();
    }

    /*
     *电话关联患者信息
     */
    public function relation_tel(){
        $tel      = trim(I('post.tel'));
        $user_id  = $_SESSION['user_info']['user_id'];

        /*检查参数*/
        if(empty($tel)){
            Common_func::outPut('关联电话不能为空');
        }elseif(!Common_func::is_mobile($tel)){
            Common_func::outPut('电话格式错误');
        }

        /*取得关联信息*/
        $info = M('user_sufferer')->where("tel='$tel' and user_id='$user_id'")->field('sufferer_id,sufferer_name,sex,age,tel,province,city,district,address')->find();
        if(empty($info)){
            $info = M('user_sufferer')->where("tel='$tel'")->field('sufferer_name,sex,age,tel,address')->find();
        }

        /*如果为空返回数据*/
        if(empty($info)){
            Common_func::outPut(array());
        }

        /*取得患者所在区域*/
        $region = OrderModel::get_sufferer_region($info['province'],$info['city'],$info['district']);
        $info['region_info'] = $region;

        unset($info['province']);
        unset($info['city']);
        unset($info['district']);

        Common_func::outPut($info);
    }

    public function order_list(){
        //all 全部, pricing 核价中, confirmed 待确认, await_ship 待发货, shipped 待收货, received 已收货
        $type       = empty(I('post.type')) ? 'all': trim(I('post.type'));
        $page_site      = empty(intval(I('post.page_site'))) ? 20: intval(I('post.page_site'));
        $user_id    = $_SESSION['user_info']['user_id'];

        /*处理查询订单状态*/
        $where = "oi.user_id='$user_id'";
        if($type == 'all'){
            $where .= "";
        }elseif($type == 'pricing'){
            $where .= " and oi.status='1'";
        }elseif($type == 'confirmed'){
            $where .= " and oi.status='2'";
        }elseif($type == 'await_ship'){
            $where .= " and oi.status='3'";
        }elseif($type == 'shipped'){
            $where .= " and oi.status='4'";
        }elseif($type == 'received'){
            $where .= " and oi.status='5'";
        }

        $page_count = M('order_info as oi')->join('cf_user_sufferer as us on(oi.sufferer_id=us.sufferer_id)')->where($where)->count();
        $page = new Page($page_count,$page_site);

        $data = M('order_info as oi')->join('cf_user_sufferer as us on(oi.sufferer_id=us.sufferer_id)')->limit($page->firstRow.','.$page->listRows)->where($where)->order('order_id desc')->select();

        foreach($data as $key=>$value){
            $order_list[$key]['order_id']       = $value['order_id'];
            $order_list[$key]['order_sn']       = $value['order_sn'];
            $order_list[$key]['add_time']       = $value['add_time'];
            $order_list[$key]['tel']            = $value['tel'];
            $order_list[$key]['address']        = $value['address'];
            $order_list[$key]['region_info']    = OrderModel::get_sufferer_region($value['province'],$value['city'],$value['district']);
        }
        Common_func::outPut($order_list);
    }

    /*
     *拍照上传
     */
    public function photo_order(){
        $consulting_fee     = intval(I('post.consulting_fee'));
        $shipping_id        = intval(I('post.shipping_id'));
        $remark             = trim(I('post.remark'));

        /*检查数据*/
        if(empty($shipping_id)){
            Common_func::outPut('配送方式为空');
        }
        if(empty($remark)){
            Common_func::outPut('备注为空');
        }

        /*上传图片处理*/
        $res = OrderModel::upload_img();
        if(!$res['return']){
            Common_func::outPut($res['error_desc']);
        }

        /*整理数据*/
        $order_data['order_sn']         = OrderModel::get_order_sn();
        $order_data['status']           = 1;
        $order_data['user_id']          = $_SESSION['user_info']['user_id'];
        $order_data['consulting_fee']   = $consulting_fee;
        $order_data['shipping_id']      = $shipping_id;
        $order_data['remark']           = $remark;
        $order_data['is_prescription']  = 1;
        $order_data['add_time']  = date('Y-m-d H:i:s');

        /*保存数据*/
        $order_id = M('order_info')->add($order_data);

        foreach($res['data'] as $value){
            $images_data['order_id'] = $order_id;
            $images_data['img_url'] = $value;
            $images_data['add_time'] = date('Y-m-d H:i:s');
            M('order_images')->add($images_data);
        }

        $arr['order_id'] = $order_id;
        Common_func::outPut($arr);
    }

    /*
     * 订单详情
     */
    public function order_detail(){
        $order_id     = intval(I('post.order_id'));

        if(empty($order_id)){
            Common_func::outPut('订单ID不能为空');
        }

        /*获取处方单*/
        $images_list = M('order_images')->where("order_id='$order_id'")->field('img_url')->select();

        /*取得订单信息*/
        $order_info = M('order_info as oi')->join('cf_shipping as s on(oi.shipping_id=s.shipping_id)')->where("oi.order_id='$order_id'")->field('oi.order_id,oi.consulting_fee,oi.diagnosis,oi.remark,s.shipping_name')->find();

        /*取得订单商品信息*/
        $goods_arr = M('order_goods as og')->join('cf_order_type as ot on(og.type_id=ot.type_id)')->where("ot.order_id='$order_id'")->field('ot.*,og.*')->select();

        /*根据goods_type分类*/
        foreach($goods_arr as $value){
            $goods[$value['goods_type']][] = $value;
        }
        /*处理商品返回信息*/
        foreach($goods as $key=>$value){
            foreach($value as $k=>$val){
                $goods_list[$key]['type_id'] = $val['type_id'];
                $goods_list[$key]['order_id'] = $val['order_id'];
                $goods_list[$key]['count_price'] = $val['count_price'];
                $goods_list[$key]['number'] = $val['number'];
                $goods_list[$key]['preparation'] = $val['preparation'];
                $goods_list[$key]['goods_type'] = $val['goods_type'];
                $goods_list[$key]['type_name'] = $val['type_name'];
                $goods_list[$key]['list'][$k]['rec_id'] = $val['rec_id'];
                $goods_list[$key]['list'][$k]['goods_id'] = $val['goods_id'];
                $goods_list[$key]['list'][$k]['goods_name'] = $val['goods_name'];
                $goods_list[$key]['list'][$k]['goods_attr'] = $val['goods_attr'];
                $goods_list[$key]['list'][$k]['goods_number'] = $val['goods_number'];
                $goods_list[$key]['list'][$k]['goods_unit'] = $val['goods_unit'];
                $goods_list[$key]['list'][$k]['goods_price'] = $val['goods_price'];
            }
        }

        /*添加患者信息*/
        $sufferer = M('order_info as oi')->join('cf_user_sufferer as us on(oi.sufferer_id=us.sufferer_id)')->where("oi.order_id='$order_id'")->field('us.sufferer_id,us.sufferer_name,us.sex,us.age,us.tel,us.province,us.city,us.district,us.address')->find();
        /*取得患者所在区域*/
        $region = OrderModel::get_sufferer_region($sufferer['province'],$sufferer['city'],$sufferer['district']);
        unset($sufferer['provinceget_sufferer_region'],$sufferer['city'],$sufferer['district']);
        $sufferer['region_info'] = $region;

        $data['images_list'] = $images_list;
        $data['order_info'] = $order_info;
        $data['goods_list'] = $goods_list;
        $data['sufferer'] = $sufferer;

        if(empty($data)){
            Common_func::outPut(array());
        }else{
            Common_func::outPut($data);
        }

    }

    /*
     *获取地区
     */
    public function region(){
        $parent_id = intval(I('post.parent_id'));

        /*检验参数是否为空*/
        if(empty($parent_id)){
            Common_func::outPut('地区ID不能为空');
        }

        /*查询数据*/
        $order_list = OrderModel::get_region($parent_id);

        /*返回数据*/
        if(empty($order_list)){
            Common_func::outPut(array());
        }else{
            Common_func::outPut($order_list);
        }
    }


    /*
     *取得配送方式
     */
    public function shipping_list(){
        $shiping_list = M('shipping')->where("enabled='1'")->field('shipping_id,shipping_code,shipping_name,shipping_desc')->select();
        if($shiping_list){
            Common_func::outPut($shiping_list);
        }
        Common_func::outPut(array());
    }

    /*
     * 保存订单信息
     */
    public function save(){
        $sufferer_info      = json_decode(htmlspecialchars_decode(trim(I('post.sufferer_info'))),true);
        $goods_list         = json_decode(htmlspecialchars_decode(trim(I('post.goods_list'))),true);
        $order_info         = json_decode(htmlspecialchars_decode(trim(I('post.order_info'))),true);

        /*检查订单信息数据*/
        if(empty($order_info)){
            Common_func::outPut('订单信息不能为空');
        }else{
            /*判断是修改还是添加*/
            $is_add = empty($order_info['order_id']) ? true: false;
            /*判断是自动保存还是医生手动提交*/
            $is_draft = $order_info['is_draft']==1 ? true: false;
        }

        /*如果不是草稿添加验证*/
        if(!$is_draft){
            /*检查患者信息*/
            if(empty($sufferer_info)){
                Common_func::outPut('请输入患者信息');
            }elseif(empty($sufferer_info['sufferer_name'])){
                Common_func::outPut('请输入患者姓名');
            }elseif(empty($sufferer_info['sex'])){
                Common_func::outPut('请输入患者性别');
            }elseif(empty($sufferer_info['age'])){
                Common_func::outPut('请输入患者年龄');
            }elseif(empty($sufferer_info['tel'])){
                Common_func::outPut('请输入患者联系电话');
            }elseif(!Common_func::is_mobile($sufferer_info['tel'])){
                Common_func::outPut('患者联系电话格式不正确');
            }elseif(empty($sufferer_info['province'])){
                Common_func::outPut('收货人省份不能为空');
            }elseif(empty($sufferer_info['city'])){
                Common_func::outPut('收货人城市不能为空');
            }elseif(!empty(OrderModel::get_region($sufferer_info['city']))){
                if(empty($sufferer_info['district'])){
                    Common_func::outPut('收货人地区不能为空');
                }
            }elseif(empty($sufferer_info['address'])){
                Common_func::outPut('收货人详细地址不能为空');
            }

            /*检查订单填写*/
            if(empty($order_info['shipping_id'])){
                Common_func::outPut('请选择配送方式');
            }elseif(empty($order_info['diagnosis'])){
                Common_func::outPut('请填写诊断结果');
            }elseif(empty($order_info['remark'])){
                Common_func::outPut('请填写订单备注');
            }

            /*检查商品数组*/
            if(empty($goods_list)){
                Common_func::outPut('请输入商品信息');
            }
            foreach($goods_list as $value){
                if(empty($value['type_name'])){
                    Common_func::outPut('类名不能为空');
                }elseif(empty(intval($value['goods_type']))){
                    Common_func::outPut('类ID不能为空');
                }elseif(empty(intval($value['count_price']))){
                    Common_func::outPut('单剂价格不可为空');
                }elseif(empty(intval($value['number']))){
                    Common_func::outPut('请输入剂数');
                }elseif(empty($value['number'])){
                    Common_func::outPut('请输入制法');
                }elseif(count($value['list'])<=0){
                    Common_func::outPut('商品不能为空');
                }
            }
        }

        /*处理患者信息，如果是草稿就直接保存数据*/
        if($is_draft){
            //检查是否为空，为空不保存
            if(!empty($sufferer_info)){
                /*处理患者信息*/
                $sufferer_info = OrderModel::handle_sufferer_info($sufferer_info);
            }
        }else{
            /*处理患者信息*/
            $sufferer_info = OrderModel::handle_sufferer_info($sufferer_info);
        }

        /*处理订单详情*/
        if($is_draft){
            if($is_add){
                /*添加订单详情*/
                $order_info = OrderModel::add_order_info($order_info,$sufferer_info['sufferer_id']);
            }else{
                /*修改订单详情*/
                $order_info = OrderModel::update_order_info($order_info,$sufferer_info['sufferer_id']);
            }
        }else{
            if($is_add){
                /*添加订单详情*/
                $order_info = OrderModel::add_order_info($order_info,$sufferer_info['sufferer_id']);
            }else{
                /*修改订单详情*/
                $order_info = OrderModel::update_order_info($order_info,$sufferer_info['sufferer_id']);
            }
        }

        /*处理商品信息*/
        if($is_draft){
            //检查是否为空，为空不保存
            if(!empty($goods_list)){
                //判断是添加还是修改
                if($is_add){
                    /*添加商品*/
                    $goods_list = OrderModel::add_order_goods($goods_list,$order_info['order_id']);
                }else{
                    /*修改商品*/
                    $goods_list = OrderModel::update_order_goods($goods_list,$order_info['order_id']);
                }
            }
        }else{
            if($is_add){
                /*添加商品*/
                $goods_list = OrderModel::add_order_goods($goods_list,$order_info['order_id']);
            }else{
                /*修改商品*/
                $goods_list = OrderModel::update_order_goods($goods_list,$order_info['order_id']);
            }
        }

        /*计算订单总金额*/
        $order_amount = M('order_type')->where("order_id='".$order_info['order_id']."'")->field("sum(count_price*number) as order_amount")->select();
        foreach($order_amount as $value){
            $order_info['order_amount'] = $order['order_amount'] = ceil($value['order_amount']);
        }
        /*修改数据*/
        M('order_info')->where("order_id='".$order_info['order_id']."'")->save($order);

        /*返回数据*/
        $data['order_info'] = $order_info;
        $data['goods_list'] = $goods_list;
        $data['sufferer_info'] = $sufferer_info;

        Common_func::outPut($data);
    }
}