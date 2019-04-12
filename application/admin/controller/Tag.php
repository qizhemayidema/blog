<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Tag as TagModel;
use page\Page;
use think\Request;

class Tag extends Common
{
	//展示页
	public function lst()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$tagModel = new TagModel();
		// var_dump($tagModel->redis_obj->Zrevrange('tag_artice_count_zset',0,100));
		// die;
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $tagModel->select_count_redis_tag_lst_all();
				$tags = $tagModel->pjax_lst($count);
				$page_obj = new Page($count,10,'tag_page',1);
				$this->assign('count',$count);
				$this->assign('tags',$tags);
				$this->assign('page',$page_obj->render);
				return $this->fetch('tag/lst_pjax');
			}else{
				$input = input();
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$count = $tagModel->select_count_redis_tag_lst_all();
				$tags = $tagModel->pjax_lst($count,$input);			//此处传输的count没有作用
				$page_obj = new Page($count,10,'tag_page',$input['page']);
				$this->assign('tags',$tags);
				$this->assign('page',$page_obj->render);
				return $this->fetch('tag/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$count = $tagModel->select_count_redis_tag_lst_all();
			$tags = $tagModel->pjax_lst($count,$input);			//此处传输的count没有作用
			$page_obj = new Page($count,10,'tag_page',$input['page']);
			$this->assign('count',$count);
			$this->assign('tags',$tags);
			$this->assign('page',$page_obj->render);
			return $this->fetch();
		}
	}

	//添加tag页面
	public function add()
	{	
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('tag/add_pjax');
		}else{
			return $this->fetch();
		}
	}

	//添加动作
	public function add_change()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$request_obj = Request::instance();
		$tagModel = new TagModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['tag_c'] == 0) {
					return 'error您没有添加权限';				//判断权限在这
				}
				$validate = validate('Tag');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $tagModel->insert_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作有误，请刷新后重新尝试';
			}
		}
	}

	//修改页面（pjax）
	public function edit()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$tagModel = new TagModel();
		if ($id = input('id')) {
			$res = $tagModel->select_redis_tag_lst_all_one($id);
			$this->assign('tag',$res);
		}else{
			return $this->error('操作失误');
		}
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('tag/edit_pjax');
		}else{
			return $this->fetch();
		}
	}

	//修改动作(ajax)
	public function edit_change()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$request_obj = Request::instance();
		$tagModel = new TagModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['tag_u'] == 0) {
					return 'error您没有权限进行修改动作';
				}
				$validate = validate('Tag');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $tagModel->update_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}
		}
	}

	//删除动作
	public function delete_change()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$request_obj = Request::instance();
		$tagModel = new TagModel();
		if ($request_obj->isAjax()) {
			if ($tag = input('post.tag')) {
				if ($this->user_permission['tag_d'] == '0') {
					return 'error您没有权限进行删除操作';
				}
				$tag = json_decode($tag,true);
				if ($res = $tagModel->delete_data($tag)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}
	}

	//模糊搜索根据tag名称查找
	public function find_tag()
	{
		//判断权限
		$this->user_permission['tag_see']==0?$this->redirect('Index/index'):'';
		$request_obj = Request::instance();
		$tagModel = model('admin/Tag');
		if ($request_obj->isAjax()) {
			if ($str = input('post.str')) {
				$tags = $tagModel->where('name','like','%'.$str.'%')->select();
				if ($tags) {
					$this->assign('tags',$tags);
					$this->assign('str',$str);
					return $this->fetch();
				}else{
					return 'error没有找到相关TAG';
				}
			}
		}
	}

	//标签下文章
	public function tag_article()
	{
		$request_obj = Request::instance();
		$tagModel = new TagModel();
		$articleModel = model('admin/Article');
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				if ($this->user_permission['article_see'] == 0) {
					return 'error您没有权限查看文章';
				}
				$count = $articleModel->redis_select_count_article_tag_what_id_zset($data['tag_id']);
				if (!$count) {
					return 'error此TAG下没有文章存在';
				}
				$page_obj = new Page($count,10,'tag_article_page',$data['page']);
				$articles = $tagModel->tag_article_lst($data['tag_id'],$data);
				$this->assign('page',$page_obj->render);
				$this->assign('tag_id',$data['tag_id']);
				$this->assign('articles',$articles);
				return $this->fetch();
			}
		}
	}
}