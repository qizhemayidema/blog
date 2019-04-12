<?php 
namespace app\index\controller;

use app\index\controller\Base;
use app\index\model\Link as LinkModel;

//友情链接控制器
class Link extends Base
{
	//展示页
	public function lst()
	{
		$linkModel = new LinkModel();
		$this->assign('links',$linkModel->select_link());									//查找友情链接
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('link/lst_pjax');
		}else{
			$this->assign('columns',$linkModel->select_column());								//查找栏目
			return $this->fetch();
		}
	}
}