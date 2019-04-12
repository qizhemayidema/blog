<?php 
namespace app\index\model;

use app\index\model\Base;

//文章详细页模型
class Article extends Base
{
	//查找一篇文章的详细信息
	public function find_article($article_id)		//文章id
	{
		if (!$this->redis_obj->Hexists('article_content_lst',$article_id)) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_content_lst();
		}
		$res = $this->redis_obj->Hget('article_content_lst',$article_id);
		if (!$res) {
			return [];
		}
		return $res;
	}

	//查找文章的相关推荐
	public function select_state_article($column_id,$article_id)		//栏目id，文章id，查找推荐文章时去掉这个传过来的文章id
	{
		if (!$this->redis_obj->exists('article_column_'.$column_id.'_state_id_zset')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_column_what_state_id_zset($column_id);
		}
		if (!$this->redis_obj->exists('article_state_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_state_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_column_'.$column_id.'_state_id_zset',0,4);
		if (!$res) {
			return [];
		}
		foreach ($res as $key => $value) {
			if ($value == $article_id) {
				unset($res[$key]);
			}
		}
		return $this->redis_obj->Hmget('article_state_lst',$res);
	}
} 