<?php
//密码类
namespace app\admin\controller;
class  Code
{
	//加密
	public function jiami($first){
		//处理个英文
		// $arr = range('a', 'z');
		// $str = join('', $arr);
		// $str .= strtoupper($str);
		// $three = substr(str_shuffle($str), 0, 3);
		// $two = substr(str_shuffle($str), 0, 2);
		$first = 'qle'.$first;

	 	$code = 0 . 'asd' . base64_encode(substr(strrev($first),0,1)). 1 . substr(strrev($first),1,3). substr(strrev($first),3,1). 2 . 'yz' . substr(strrev($first),3);
	 	return $code;
	}
	//解密
	public function decode($first){
	$code = base64_decode(substr($first,4,4)).substr($first,9,2).substr($first,16);
	return substr(strrev($code),0,-3);
	}
}


// $code = new Code();
// $a = $code->jiami('');
// var_dump($a);
// $b = $code->decode($a);
// var_dump($b);