<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Comment as CommentModel;
use think\Request;
use page\Page;

//文章评论控制器
class Comment extends Common
{
	//展示页
	public function lst()
	{
		//验证权限
		$this->user_permission['comment_see']==0?($this->redirect('Index/index')):'';
		$commentModel = new CommentModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $commentModel->redis_select_count_comment_lst();		//父评论长度
				$count_all = $commentModel->redis_select_count_comment_id_zset_all();	//全部评论长度
				$comments = $commentModel->pjax_lst();
				$page_obj = new Page($count,10,'comment_page',1);
				$this->assign('count',$count);
				$this->assign('count_all',$count_all);
				$this->assign('page',$page_obj->render);
				$this->assign('comments',$comments);
				return $this->fetch('comment/lst_pjax');
			}else{
				$input = input('get.');
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$count = $commentModel->redis_select_count_comment_lst();		//父评论长度
				$comments = $commentModel->pjax_lst($input);
				$page_obj = new Page($count,10,'comment_page',$input['page']);
				$this->assign('comments',$comments);
				$this->assign('page',$page_obj->render);
				return $this->fetch('comment/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$count = $commentModel->redis_select_count_comment_lst();		//父评论长度
			$count_all = $commentModel->redis_select_count_comment_id_zset_all();	//全部评论长度
			$comments = $commentModel->pjax_lst($input);
			$page_obj = new Page($count,10,'comment_page',$input['page']);
			$this->assign('count',$count);
			$this->assign('count_all',$count_all);
			$this->assign('comments',$comments);
			$this->assign('page',$page_obj->render);
			return $this->fetch();
		}
	}

	//根据文章id查找评论
	public function find_comment()
	{
		//验证权限
		$this->user_permission['comment_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$commentModel = new CommentModel();
		if ($request_obj->isAjax()) {
			if ($article_id = input('post.id')) {
				$article_id += 0;
				if (is_int($article_id) && $article_id > 0) {
					if (!$commentModel->where(['article_id'=>$article_id])->find()) {
						return 'error没有此文章的评论信息';
					}
					$comments = $commentModel->redis_select_article_comment($article_id);
					$this->assign('comments',$comments);
					$this->assign('str',$article_id);
					return $this->fetch('comment/find_comment');
				}else{
					return 'error只能为正整数的id';
				}
			}
		}
	}

	//查看详细页面
	public function see()
	{
		//验证权限
		$this->user_permission['comment_see']==0?($this->redirect('Index/index')):'';
		if ($comment_id = input('get.comment')) {
			$commentModel = new CommentModel();
			$articleModel = model('admin/Article');
			$comments = $commentModel->redis_select_comment_lst_one($comment_id);
			if ($comments) {
				$article = $articleModel->where(['id'=>$comments['article_id']])->field('id,title')->find();
			}else{
				$article = '';
			}
			$this->assign('comments',$comments);
			$this->assign('article',$article);
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				return $this->fetch('comment/see_pjax');
			}else{
				return $this->fetch();
			}
		}
	}

	//某个文章的评论
	public function article_comment()
	{
		$commentModel = new CommentModel();
		$data = input();
		if ($article_id = input('id')) {	//此处为文章id
			if ($this->user_permission['comment_see'] == 0) {
				return 'error您没有权限查看评论详细信息';
			}
			if (!isset($data['page'])) {
				$data['page'] = 1;
			}
			$count = $commentModel->redis_select_count_comment_article_id_what_zset($article_id);
			$page_obj = new Page($count,10,'comment_article_page',$data['page']);
			$comments = $commentModel->article_page_lst($article_id,$data);
			if (!$comments) {
				return 'error此文章没有评论';
			}
			$this->assign('article_id',$article_id);
			$this->assign('page',$page_obj->render);
			$this->assign('count',$count);
			$this->assign('comments',$comments);
			return $this->fetch();
		}
	}

	//屏蔽 多个or单个 评论
	public function black_change()
	{
		//验证权限
		$this->user_permission['comment_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$commentModel = new CommentModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['comment_u'] == 0) {
				return 'error您没有权限进行屏蔽操作';
			}
			if ($data = input('post.black')) {
				$data = json_decode($data,true);
				if ($res = $commentModel->comment_to_black($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误,请刷新后重新尝试';
			}
		}
	}

	//反屏蔽 多个or单个 评论
	public function unblack_change()
	{
		//验证权限
		$this->user_permission['comment_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$commentModel = new CommentModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.black')) {
				if ($this->user_permission['comment_u'] == 0) {
					return 'error您没有权限进行反屏蔽操作';
				}
				$data = json_decode($data,true);
				if ($res = $commentModel->comment_un_black($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误,请刷新后重新尝试';
			}
		}
	}
}