<?php
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Comment extends Model
{
	public $redis_obj;

	public function initialize()
	{
		$this->redis_obj = new Redis();
	}
/*****************逻辑入口******************/
	//屏蔽单个或多个评论
	public function comment_to_black($data)		//可以为一维索引数组，值为评论id，或为字符串。值为评论id
	{
		$str = '此条评论已被站长删除，如有疑问请联系站长';		//屏蔽语句
		if (is_array($data)) {
			$list = '';
			foreach ($data as $key => $value) {
				$list .= 'id = '.$value.' OR ';
			}
			$list = substr($list,0,-4);
			$old_res = $this->where($list)->field('id,comment,top_id,old_comment')->select();
			$new_res = [];
			$top_id = [];
			foreach ($old_res as $key) {
				if ($key['old_comment'] != '') {		//此处验证是否已被屏蔽
					return '您选中的数据中存在已被屏蔽的评论，请去掉后重新尝试';
				}
				$new_res[$key['id']]['id'] = $key['id'];
				$new_res[$key['id']]['comment'] = $str;
				$new_res[$key['id']]['old_comment'] = $key['comment'];
				$top_id[] = $key['top_id'];
			}
			$top_id = array_unique($top_id);
			if ($this->saveAll($new_res)) {		//更改动作
				$common_commentModel = model('common/Comment');
				foreach ($top_id as $key => $value) {
					$common_commentModel->redis_common_lst_one($value);
				}
			}else{
				return '操作失败,请刷新后重新尝试';
			}
		}else{
			$old_res = $this->where(['id'=>$data])->field('id,comment,top_id,old_comment')->find();
			if ($old_res['old_comment'] != '') {
				return '您要屏蔽的评论已被屏蔽';
			}
			$new_res = [];
			$new_res['comment'] = $str;
			$new_res['old_comment'] = $old_res['comment'];
			if ($this->update($new_res,['id'=>$old_res['id']])) {
				$common_commentModel = model('common/Comment');
				$common_commentModel->redis_common_lst_one($old_res['top_id']);
			}else{
				return '操作失败,请刷新后重新尝试';
			}
		}
	}
	//反屏蔽单个或多个评论
	public function comment_un_black($data)		//可以为一维索引数组，值为评论id，或为字符串。值为评论id
	{
		if (is_array($data)) {
			$list = '';
			foreach ($data as $key => $value) {
				$list .= 'id = '.$value.' OR ';
			}
			$list = substr($list,0,-4);
			$old_res = $this->where($list)->field('id,comment,top_id,old_comment')->select();
			$new_res = [];
			$top_id = [];
			foreach ($old_res as $key) {
				if ($key['old_comment'] == '') {		//此处验证是否已被屏蔽
					return '您选中的数据中存在未被屏蔽的评论，请去掉后重新尝试';
				}
				$new_res[$key['id']]['id'] = $key['id'];
				$new_res[$key['id']]['comment'] = $key['old_comment'];
				$new_res[$key['id']]['old_comment'] = '';
				$top_id[] = $key['top_id'];
			}
			$top_id = array_unique($top_id);
			if ($this->saveAll($new_res)) {		//更改动作
				$common_commentModel = model('common/Comment');
				foreach ($top_id as $key => $value) {
					$common_commentModel->redis_common_lst_one($value);
				}
			}else{
				return '操作失败,请刷新后重新尝试';
			}
		}else{
			$old_res = $this->where(['id'=>$data])->field('id,comment,top_id,old_comment')->find();
			if ($old_res['old_comment'] == '') {
				return '您要屏蔽的评论未被屏蔽';
			}
			$new_res = [];
			$new_res['comment'] = $old_res['old_comment'];
			$new_res['old_comment'] = '';
			if ($this->update($new_res,['id'=>$old_res['id']])) {
				$common_commentModel = model('common/Comment');
				$common_commentModel->redis_common_lst_one($old_res['top_id']);
			}else{
				return '操作失败,请刷新后重新尝试';
			}
		}
	}
	//删除掉某个文章的所有评论
	public function del_article_comment($article_id)	//文章id
	{
		$res = $this->where(['article_id'=>$article_id])->field('id,top_id')->select();
		$list = [];
		$top_id = [];
		foreach ($res as $key) {
			$list[] = $key['id'];
			$top_id[] = $key['top_id'];
		}
		$top_id = array_unique($top_id);
		if ($this->destroy($list)) {
			foreach ($top_id as $key1 => $value1) {
				$this->redis_del_comment_id_zset_one($value1);
				$this->redis_del_comment_article_id_what_zset_one($value1,$article_id);
			}
			foreach ($list as $key2 => $value2) {
				$this->redis_del_comment_lst_one($value2);
				$this->redis_del_comment_id_zset_all_one($value2);
			}
		}
	}
/**************查找数据*********************/
	//某个文章下的所有评论
	public function redis_select_article_comment($article_id)		//文章id
	{
		$common_commentModel = model('common/Comment');	
		if (!$count = $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset')) {
			if (!$common_commentModel->redis_comment_article_id_what_zset($article_id,true)) {
				return [];
			}else{
				$count = $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset');
			}
		}
		if (!$this->redis_obj->exists('comment_lst')) {
			$common_commentModel->redis_comment_lst();
		}
		return $this->redis_obj->Hmget('comment_lst',$this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',0,$count));
	}
	//查找某个评论的详细信息，必须为顶级id 既父级为0
	public function redis_select_comment_lst_one($comment_id)		//评论id
	{
		if ($this->redis_obj->exists('comment_lst')) {
			$res = $this->redis_obj->Hget('comment_lst',$comment_id);
		}else{
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_lst();
			$res = $this->redis_obj->Hget('comment_lst',$comment_id);
		}
		if (!$res) {
			return [];
		}
		return $res;
	}
/***************查找数据长度****************/
	//comment_lst
	public function redis_select_count_comment_lst()
	{
		if ($count = $this->redis_obj->Hlen('comment_lst')) {
			return $count;
		}else{
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_lst();
			return $this->redis_obj->Hlen('comment_lst');
		}
	}

	// comment_id_zset_all
	public function redis_select_count_comment_id_zset_all()
	{
		if ($count = $this->redis_obj->Zcard('comment_id_zset_all')) {
			return $count;
		}else{
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_id_zset_all();
			return $this->redis_obj->Zcard('comment_id_zset_all');
		}
	}

	//comment_article_id_?_zset
	public function redis_select_count_comment_article_id_what_zset($article_id)	//文章id
	{
		if ($count = $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset')) {
			return $count;
		}else{
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_article_id_what_zset($article_id);
			return $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset');
		}
	}
/***************删除一条数据****************/
	//comment_lst
	public function redis_del_comment_lst_one($comment_id)		//任何评论id
	{
		if ($this->redis_obj->Hexists('comment_lst',$comment_id)) {
			$this->redis_obj->Hdel('comment_lst',$comment_id);
		}
	}
	//comment_id_zset
	public function redis_del_comment_id_zset_one($comment_id)	//评论id  不同的是 这必须为top_id 也就是顶级id
	{
		if ($this->redis_obj->exists('comment_id_zset')) {
			$this->redis_obj->Zrem('comment_id_zset',$comment_id);
		}
	}
	//comment_id_zset_all
	public function redis_del_comment_id_zset_all_one($comment_id)	//任何评论id
	{
		if ($this->redis_obj->exists('comment_id_zset_all')) {
			$this->redis_obj->Zrem('comment_id_zset_all',$comment_id);
		}
	}
	//comment_article_id_?_zset   ?为文章id
	public function redis_del_comment_article_id_what_zset_one($comment_id,$article_id)//评论id  不同的是 这必须为top_id 也就是顶级id,文章id
	{
		if ($this->redis_obj->exists('comment_article_id_'.$article_id.'_zset')) {
			$this->redis_obj->Zrem('comment_article_id_'.$article_id.'_zset',$comment_id);
		}
	}
/***************分页算法********************/
	//分页
	public function pjax_lst($input = null)		//count为
	{
		if (!$this->redis_obj->exists('comment_id_zset')) {
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_id_zset();
		}
		if (!$input) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('comment_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('comment_id_zset',$start,$end);
				}elseif ($input['sort'] == 'sort') {
					$res = $this->redis_obj->Zrevrange('comment_id_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrevrange('comment_id_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('comment_id_zset',$start,$end);
			}
		}
		if (!$res) {
			$res = $this->redis_obj->Zrevrange('comment_id_zset',0,9);
		}
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('comment_lst',$res);
	}

	//某个文章下的评论分页
	public function article_page_lst($article_id,$input = null)		//之前已经查过了comment_article_id_?_zset的长度，此处验证comment_lst是否存在
	{
		if (!$this->redis_obj->exists('comment_lst')) {
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_lst();
		}
		if (!$input) {
			$start = 0;
			$end = 4;
			$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort'])) {
				if ($input['sort'] == 'order') {
					$res = $this->redis_obj->Zrange('comment_article_id_'.$article_id.'_zset',$start,$end);
				}elseif ($input['sort'] == 'sort') {
					$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',$start,$end);
				}else{
					$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',$start,$end);
			}
		}
		if (!$res) {
			$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',0,9);
		}
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('comment_lst',$res);
	}
}