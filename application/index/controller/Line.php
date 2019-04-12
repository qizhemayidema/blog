<?php 
namespace app\index\controller;

use app\index\controller\Base;
use app\index\model\Index as IndexModel;

//在线聊天控制器
class Line extends Base
{
	public function index()
	{
	    if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']){
            return $this->fetch('line/index_pjax');
        }else{
	        $indexModel = new IndexModel();
            $this->assign('columns',$indexModel->select_column());						//栏目
            return $this->fetch('line/index');
        }
	}
}