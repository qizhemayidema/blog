<?php 
namespace app\index\model;

use app\index\model\Base;
	
//某个栏目下文章模型
class Category extends Base
{	
	//获取某个栏目下的文章
	public function get_column_article($column_id,$start = 0,$end = 4)	//栏目id，从N开始，到N结束
	{
		if (!$this->redis_obj->exists('article_column_id_zset_'.$column_id)) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_column_id_zset_what($column_id);
		}
		if (!$this->redis_obj->exists('article_front_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_front_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$column_id,$start,$end);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_front_lst',$res);
	}
}