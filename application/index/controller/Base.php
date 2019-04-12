<?php 
namespace app\index\controller;

use think\Controller;
use think\Cookie;

//前台父类控制器
class Base extends Controller
{
	public function _initialize()
	{
		if (!Cookie::has('article')) {;
			Cookie::set('article','0,');
		}
		$agent = $_SERVER["HTTP_USER_AGENT"];
		if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')){	 //ie浏览器判断
			echo $this->fetch('error/replace_browser');
			die;
		}
	}
}