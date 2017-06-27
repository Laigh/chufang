<?php
/**
 * Created by PhpStorm.
 * User: Liend
 * Date: 2017/5/23
 * Time: 11:33
 */

namespace Home\Common\Util;

class Common_func{

    /*
     * 返回json数据
     */
    public static function outPut($data, $pager = NULL){
        if (!is_array($data)) {
            $status = array(
                'status' => array(
                    'succeed' => 0,
                    'error_code' => 110,
                    'error_desc' => $data
                )
            );

            die(json_encode($status,JSON_UNESCAPED_UNICODE));
        }
        if (isset($data['data'])) {
            $data = $data['data'];
        }
        $data = array_merge(array('data'=>$data), array('status' => array('succeed' => 1)));
        if (!empty($pager)) {
            $data = array_merge($data, array('paginated'=>$pager));
        }
        die(json_encode($data,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 判断是否为手机号
     * @param $mobile
     * @return bool
     */
    function is_mobile($mobile){
        if(preg_match('/^1[34578]\d{9}$/', $mobile)){
            return true;
        }else{
            return false;
        }
    }

}