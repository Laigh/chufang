<?php
/**
 * Created by PhpStorm.
 * User: Liend
 * Date: 2017/5/23
 * Time: 11:33
 */

namespace Home\Common\Util;

class Images{
    /**
     * ����ͼƬ
     * @param string $dir
     * @return bool|string
     */
    public function upload_img($dir='Uploads/images/'){

        if(!is_dir('./'.$dir)){
            if(!mkdir('./'.$dir,0777,true)){
                $result['return'] = false;
                $result['error_desc'] = 'Ŀ¼����ʧ��';
                return $result;
            }
        }

        $upload = new \Think\Upload();
        $upload->maxSize   =     2097152 ;
        $upload->exts      =     array('jpg','gif','png','jpeg');
        $upload->rootPath  =     './'.$dir;
        $upload->savePath  =     '';


        $info   =   $upload->upload();

        if(!$info) {
            $result['return'] = false;
            $result['error_desc'] = $upload->getError();

            return $result;
        }else{
            foreach($info as $file){
                $url =  $file['savepath'].$file['savename'];
            }
            $imgurl = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/'.$dir.$url;

            $result['return'] = true;
            $result['data'] = $imgurl;

            return $result;
        }
    }
}