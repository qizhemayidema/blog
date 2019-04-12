<?php 
namespace app\index\controller;

use app\index\controller\Base;
use app\index\model\Tag as TagModel;

//标签控制器
class Tag extends Base
{
	//列表展示页
	public function lst()
	{
		$tagModel = new TagModel();
		$this->assign('tags',$tagModel->select_tag());			//寻找标签
		$this->assign('columns',$tagModel->select_column());								//查找栏目
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('tag/lst_pjax');
		}else{
			return $this->fetch();
		}
	}

	//标签的所属文章页面
	public function find_tag()
	{
		if ($tag_id = input('id')) {
			$tagModel = new TagModel();
			$this->assign('tag',$tagModel->select_tag_one($tag_id));			//查找tag信息 id，name，article_count
			$this->assign('articles_hot',$tagModel->select_hot_article());		//热门文章
			$this->assign('sentence',$tagModel->select_sentence_one());			//每日一句
			$this->assign('articles',$tagModel->select_tag_article($tag_id));	//查找文章
			
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				return $this->fetch('tag/find_tag_pjax');
			}else{
				$this->assign('columns',$tagModel->select_column());				//查找栏目
				return $this->fetch('tag/find_tag');
			}
		}
	}
}