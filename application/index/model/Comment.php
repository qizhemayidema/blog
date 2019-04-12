<?php 
namespace app\index\model;

use app\index\model\Base;

//文章评论模型
class Comment extends Base
{
/****************数据长度**********************/
	//comment_article_id_?_zset的长度
	public function select_count_comment_article_id_what_zset($article_id)
	{
		if ($count = $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset')) {
			return $count;
		}else{
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_article_id_what_zset($article_id);
			return $this->redis_obj->Zcard('comment_article_id_'.$article_id.'_zset');
		}
	}
/****************逻辑入口*********************/
	//新增一条数据
	public function insert_one($data)		//将要新增的数据
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (isset($data['code'])) {
			unset($data['code']);
		}
		$data['time'] = time();
		$data['comment'] = str_replace('<','',$data['comment']);
		$data['comment'] = str_replace('>','',$data['comment']);
		$data['comment'] = str_replace(';','',$data['comment']);
		if ($this->save($data)) {
			if ($data['top_id'] == '') {
				$this->update(['id'=>$this->id,'top_id'=>$this->id]);
			}
			echo json_encode(['error'=>'0','msg'=>'']);		//此处为成功
			
			$top_id = $this->where(['id'=>$this->id])->value('top_id');
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_common_lst_one($top_id);
			$common_commentModel->redis_comment_id_zset_one($top_id);
			$common_commentModel->redis_comment_id_zset_all_one($this->id);
			$common_commentModel->redis_comment_article_id_what_zset_one($data['article_id'],$top_id);

			//更新文章表相关数据
			$admin_articleModel = model('admin/Article');
			$comment_count = $admin_articleModel->where(['id'=>$data['article_id']])->value('comment_count');
			if ($admin_articleModel->update(['comment_count'=>$comment_count+1],['id'=>$data['article_id']])) {
				$admin_articleModel->redis_article_front_lst_one($data['article_id']);
				$admin_articleModel->redis_article_content_lst_one($data['article_id']);
				$admin_articleModel->redis_article_lst_one($data['article_id']);
			}

		}else{
			return json_encode(['error'=>'1','msg'=>'评论失败，刷新后再试试吧']);
		}
	}

	//访问量+1
	public function click_up_one($article_id)	//将要+1的文章id
	{
		$admin_articleModel = model('admin/Article');
		$click = $admin_articleModel->where(['id'=>$article_id])->value('click');
		if ($click !== null) {
			if ($admin_articleModel->update(['click'=>$click+1],['id'=>$article_id])) {
				$admin_articleModel->redis_article_front_lst_one($article_id);
				$admin_articleModel->redis_article_content_lst_one($article_id);
				$admin_articleModel->redis_article_hot_id_zset_one($article_id,$click+1);
				$admin_articleModel->redis_article_lst_one($article_id);
			}
		}
	}
/****************分页算法*********************/
	//评论分页
	public function page_lst($article_id,$page = 1)		//文章id，页码
	{
		if (!$this->redis_obj->exists('comment_lst')) {
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_lst();
		}
		if (!$this->redis_obj->exists('comment_article_id_'.$article_id.'_zset')) {
			$common_commentModel = model('common/Comment');
			$common_commentModel->redis_comment_article_id_what_zset($article_id);
		}
		$start = $page * 5 -5;
		$end = $start+4;
		$res = $this->redis_obj->Zrevrange('comment_article_id_'.$article_id.'_zset',$start,$end);
		if (!$res) {
			return [];
		}
		return $this->redis_obj->Hmget('comment_lst',$res);
	}
}