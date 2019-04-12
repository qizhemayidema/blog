<?php
namespace app\index\model;

use app\index\model\Base;

class Tag extends Base
{
	//查找所有tag，展示列表用
	public function select_tag()
	{
		if (!$this->redis_obj->exists('tag_lst')) {
			$admin_tagModel = model('admin/Tag');
			$admin_tagModel->redis_tag_lst();
		}
		$list = $this->redis_obj->Hgetall('tag_lst');
		if (!$list) {
			return [];
		}
		return $list;
	}
	//查找一条tag信息
	public function select_tag_one($tag_id)
	{
		if (!$this->redis_obj->exists('tag_lst')) {
			$admin_tagModel = model('admin/Tag');
			$admin_tagModel->redis_tag_lst();
		}
		$res = $this->redis_obj->Hget('tag_lst',$tag_id);
		if (!$res) {
			return [];
		}
		return $res;
	}

	//查找某个tag下的文章	article_tag_?_id_zset
	public function select_tag_article($tag_id,$page = 1)
	{
		$start = $page *5 - 5;
		$end = $start + 4;
		if (!$this->redis_obj->exists('article_tag_'.$tag_id.'_id_zset')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_tag_what_id_zset($tag_id);
		}
		if (!$this->redis_obj->exists('article_front_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_front_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_tag_'.$tag_id.'_id_zset',$start,$end);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_front_lst',$res);
	}
}