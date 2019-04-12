<?php 
namespace app\index\model;

use app\index\model\Base;

class Index extends Base
{
	//查找置顶公告，用于页面展示
	public function select_top_notice()
	{
		if (!$this->redis_obj->exists('notice_top_id_zset')) {
			$admin_noticeModel = model('admin/Notice');
			$admin_noticeModel->redis_notice_top_id_zset();
		}
		if (!$this->redis_obj->exists('notice_lst')) {
			$admin_noticeModel = model('admin/Notice');
			$admin_noticeModel->redis_notice_lst();
		}
		$res = $this->redis_obj->Zrevrange('notice_top_id_zset',0,4);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('notice_lst',$res);
	}

	//查找普通公告，用于页面展示
	public function select_notice()
	{
		if (!$this->redis_obj->exists('notice_no_top_show_id_zset')) {
			$admin_noticeModel = model('admin/Notice');
			$admin_noticeModel->redis_notice_no_top_show_id_zset();
		}
		if (!$this->redis_obj->exists('notice_lst')) {
			$admin_noticeModel = model('admin/Notice');
			$admin_noticeModel->redis_notice_lst();
		}
		$res = $this->redis_obj->Zrevrange('notice_no_top_show_id_zset',0,4);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('notice_lst',$res);
	}

	//查找轮播文章，用于页面展示
	public function select_roll_article()
	{
		if (!$zcount = $this->redis_obj->Zcard('article_roll_id_zset')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_roll_id_zset();
			$zcount = $this->redis_obj->Zcard('article_roll_id_zset');
		}
		if (!$this->redis_obj->exists('article_roll_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_roll_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_roll_id_zset',0,$zcount-1);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_roll_lst',$res);
	}

	//查找标签，用于页面展示
	public function select_tag()
	{
		if (!$this->redis_obj->exists('tag_id_zset')) {
			$admin_tagModel = model('admin/Tag');
			$admin_tagModel->redis_tag_id_zset();
		}
		if (!$this->redis_obj->exists('tag_lst')) {
			$admin_tagModel = model('admin/Tag');
			$admin_tagModel->redis_tag_lst();
		}
		$res = $this->redis_obj->Zrevrange('tag_id_zset',0,4);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('tag_lst',$res);
	}

	//查找文章（列表） 用于展示
	public function select_front_article($start = 0,$end = 4)		//默认每页5条
	{
		if (!$this->redis_obj->exists('article_id_zset')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_id_zset();
		}
		if (!$this->redis_obj->exists('article_front_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_content_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_front_lst',$res);
	}

	//获取某个栏目下的文章 用于展示
	public function select_column_front_article($column_id,$start = 0,$end = 4)		//栏目id，开始，结束
	{
		if (!$this->redis_obj->exists('article_column_id_zset_'.$column_id)) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_column_id_zset_what();
		}
		if (!$this->redis_obj->exists('article_front_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_content_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$column_id,$start,$end);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_front_lst',$res);
	}

	//新增一个ip相关信息
	public function insert_ip_one($data)		//ip相关信息
	{
		$visitModel = model('common/Visit');
		$ip = $data['query'];
		if (!$visitModel->where(['ip'=>$ip])->field('id')->find()) {		//表明数据库中没有此ip记录，则新增
			$list = [];
			$list['ip'] = $data['query'];
			$list['os'] = $data['os'];
			$list['px'] = $data['px'];
			$list['city'] = $data['city'];
			$list['country'] = $data['country'];
			$list['code'] = $data['countryCode'];
			$list['isp'] = $data['isp'];
			$list['as'] = $data['as'];
			$list['lon'] = $data['lon'];
			$list['lat'] = $data['lat'];
			$list['timezone'] = $data['timezone'];
			$list['time'] = time();
			if ($visitModel->save($list)) {
				$list['id'] = $visitModel->id;
				$visitModel->redis_visit_lst_one($visitModel->id,$list);
				$visitModel->redis_visit_id_zset_one($visitModel->id);
			}
		}
	}
}