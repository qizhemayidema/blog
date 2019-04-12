<?php 
namespace app\admin\controller;

use app\admin\controller\Common;

//后台主页控制器
class Index extends Common
{
	public function index()
	{
		return $this->fetch();
	}
}
