<?php 
namespace app\index\model;

use think\Model;
use think\cache\driver\Redis;

class Base extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
	//查找栏目（top）
	public function select_column()
	{
		if (!$zcount = $this->redis_obj->Zcard('column_show_id_zset')) {
			$admin_columnModel = model('admin/Column');
			$admin_columnModel->redis_column_show_id_zset();
			$zcount = $this->redis_obj->Zcard('column_show_id_zset');
		}
		if (!$this->redis_obj->exists('column_lst')) {
			$admin_columnModel = model('admin/Column');
			$admin_columnModel->redis_column_lst();
		}
		$res = $this->redis_obj->Zrange('column_show_id_zset',0,$zcount-1);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('column_lst',$res);
	}

	//查找某个栏目的数据 id、name
	public function select_column_one($column_id)		//栏目id
	{
		if (!$this->redis_obj->exists('column_lst')) {
			$admin_columnModel = model('admin/Column');
			$admin_columnModel->redis_column_lst();
		}
		return $this->redis_obj->Hget('column_lst',$column_id);
	}

	//查找热门文章（右侧）
	public function select_hot_article()
	{
		if (!$this->redis_obj->exists('article_hot_id_zset')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_hot_id_zset();
		}
		if (!$this->redis_obj->exists('article_front_lst')) {
			$admin_articleModel = model('admin/Article');
			$admin_articleModel->redis_article_front_lst();
		}
		$res = $this->redis_obj->Zrevrange('article_hot_id_zset',0,5);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('article_front_lst',$res);
	}

	//查找每日一句(一条)
	public function select_sentence_one()
	{
		if (!$this->redis_obj->exists('sentence_show_id_zset')) {
			$admin_sentenceModel = model('admin/Sentence');
			$admin_sentenceModel->redis_sentence_show_id_zset();
		}
		if (!$this->redis_obj->exists('sentence_lst')) {
			$admin_sentenceModel = model('admin/Sentence');
			$admin_sentenceModel->redis_sentence_lst();
		}
		$res = $this->redis_obj->Zrevrange('sentence_show_id_zset',0,0);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hget('sentence_lst',$res[0]);
	}
}