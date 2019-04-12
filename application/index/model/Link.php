<?php 
namespace app\index\model;

use app\index\model\Base;

class Link extends Base
{
	//查找友情链接
	public function select_link()
	{
		if ($this->redis_obj->exists('link_id_zset')) {
			$count = $this->redis_obj->Zcard('link_id_zset');
		}else{
			$admin_linkModel = model('admin/Link');
			$admin_linkModel->redis_link_id_zset();
			$count = $this->redis_obj->Zcard('link_id_zset');
		}
		if (!$this->redis_obj->exists('link_lst')) {
			$admin_linkModel = model('admin/Link');
			$admin_linkModel->redis_link_lst();
		}
		$res = $this->redis_obj->Zrange('link_id_zset',0,$count);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('link_lst',$res);
	}
}