<?php 
namespace app\index\controller;

use app\index\controller\Base;

//栏目控制器（页面top）
class Category extends Base
{	
	//获取某个栏目下的文章页
	public function index()
	{
		if ($id = input('id')) {
			$categoryModel = model('index/Category');
			$this->assign('articles',$categoryModel->get_column_article($id));		//某个栏目下的文章
			$this->assign('articles_hot',$categoryModel->select_hot_article());		//热门文章
			$this->assign('sentence',$categoryModel->select_sentence_one());		//每日一句
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				$this->assign('column_lst_one',$categoryModel->select_column_one($id));
				return $this->fetch('category/index_pjax');
			}else{
				$columns = $categoryModel->select_column();
				if (!isset($columns[$id])) {
					return $this->fetch('error/404');		//抛出404
				}
				$this->assign('columns',$columns);			//查找栏目
				$this->assign('column_id',$id);
				return $this->fetch();
			}
		}else{
			return $this->fetch('error/404');		//抛出404
		}
	}
}