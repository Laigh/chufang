<?php
namespace Home\Common\Util;

class TpApi{

	static function GetData($file){
		$file = dirname(__FILE__).'/'.$file;
		$text = self::file_get_contents($file);

		$text = preg_replace("%//\s+[^\n]+%", '', $text);
		$data = json_decode($text, true);
		if (empty($data)){
			return false;
		} else {
			return self::formatData($data);
		}
	}

	static function formatData($data)
	{
		$return = array();
		foreach ($data['controller'] as $key => $value) {
			$api = array_filter(explode(' ', $key));
			$temp = array('type'=>$api[0], 'url'=>trim(end($api)), 'request'=>$value['request']);
			$return['api'][] = $temp;
		}

		$return['server'] = $data['server'];
		$return['model'] = $data['model'];

		return $return;
	}

	static function file_get_contents($file){
		if (($fp = @fopen($file, 'rb')) === false){
			return false;
		}else{
			$fsize = @filesize($file);
			if ($fsize){
				$contents = fread($fp, $fsize);
			}else{
				$contents = '';
			}

			fclose($fp);
			return $contents;
		}
	}
}