<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Link as LinkModel;
use think\Request;
use page\Page;

//友情链接控制器
class Link extends Common
{
	//展示页面（pjax）
	public function lst()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';

		$linkModel = new LinkModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $linkModel->select_lst_all(true);	//所有数据长度
				$data = $linkModel->pjax_lst($count);	//符合条件的数据
				$page_obj = new Page($count,10,'link_page',1);
				$this->assign('count',$count);
				$this->assign('links',$data);
				$this->assign('page',$page_obj->render);
				return $this->fetch('link/lst_pjax');
			}else{
				$input = input('get.');
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$count = $linkModel->select_lst_all(true);	//所有数据长度
				$data = $linkModel->pjax_lst($count,$input);	//符合条件的数据
				$page_obj = new Page($count,10,'link_page',$input['page']);
				$this->assign('links',$data);
				$this->assign('page',$page_obj->render);
				return $this->fetch('link/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$count = $linkModel->select_lst_all(true);	//所有数据长度
			$data = $linkModel->pjax_lst($count,$input);	//符合条件的数据
			$page_obj = new Page($count,10,'link_page',$input['page']);
			$this->assign('count',$count);
			$this->assign('links',$data);
			$this->assign('page',$page_obj->render);
			return $this->fetch();
		}
	}

	//新增页面(pjax)
	public function add()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('add_pjax');
		}else{
			return $this->fetch();
		}
	}
	//新增动作（ajax）
	public function add_change()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['link_c'] == 0) {return 'error您没有权限进行增加友链操作';}

		$request_obj = Request::instance();
		$linkModel = new LinkModel();		
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($_FILES['link_ico']['error'] == '0') {
					$file = $request_obj->file('link_ico');
					if (!$file->check(['size'=>20480,'ext'=>'ico','fileMime'=>'ico'])) {
						return 'error必须为图片文件，且为ico图片，最大不能超过20kb大小';
					}
					$img = $_FILES['link_ico'];
				}else{
					$img = false;
				}
				$validate = Validate('Link');
				$msg = $validate->check($data);
				if (!$msg) {
					return 'error'.$validate->getError();
				}
				if ($res = $linkModel->insert_one($data,$img)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}
	}

	//修改页面(pjax)
	public function edit()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$linkModel = new LinkModel();
		if ($link_id = input('id')) {
			$link = $linkModel->select_lst_one($link_id);		//查询一条数据
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				if (!$link) {
					return '操作失误';
				}
				$this->assign('link',$link);
				return $this->fetch('link/edit_pjax');
			}else{
				if (!$link) {
					$this->error('操作失误');
				}
				$this->assign('link',$link);
				return $this->fetch();
			}
		}else{
			$this->redirect('Index/index');
		}
	}

	//修改动作（ajax）
	public function edit_change()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$linkModel = new LinkModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['link_u'] == 0) {
				return 'error您没有修改权限';
			}
			if ($data = input('post.')) {
				if ($_FILES['link_ico']['error'] == 0) {
					$file = $request_obj->file('link_ico');
					if (!$file->check(['size'=>20480,'ext'=>'ico','fileMime'=>'ico'])) {
						return 'error必须为图片文件，且为ico图片，最大不能超过20kb大小';
					}
					$img = $_FILES['link_ico'];
				}else{
					$img = '';
				}
				$validate = Validate('Link');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $linkModel->update_one($data,$img)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新页面后重新操作';
			}
		}
	}

	//删除动作
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$linkModel = new LinkModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['link_d'] == 0) {
				return 'error您没有删除权限';
			}
			if ($link_id = input('post.id')) {
				$list = [];
				$list[] = $link_id;
				if ($res = $linkModel->delete_data($list)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误,请刷新后重新操作';
			}
		}
	}

	//根据域名/名称查找
	public function find_link()
	{
		$request_obj = Request::instance();
		$linkModel = new LinkModel();
		if ($request_obj->isAjax()) {
			//判断可见权限
			if ($this->user_permission['link_see'] == '0') {
				return 'error您没有权限进行此操作';
			}
			if ($search = input('post.search')) {
				$res = $linkModel->where('link_name|link_url','like','%'.$search.'%')->select();
				if ($res) {
					$this->assign('search',$search);
					$this->assign('links',$res);
					return $this->fetch();
				}else{
					return 'error没有此名称/友链';
				}
			}
		}
	}

	//批量删除动作
	public function more_del_change()
	{
		//判断可见权限
		$this->user_permission['link_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['link_d'] == 0) {return 'error您没有权限进行此操作';}
		$request_obj = Request::instance();
		$linkModel = new LinkModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {
					if ($res = $linkModel->delete_data($data['lst_form_checkbox_one'])) {
						return 'error'.$res;
					}
					return 'true';
				}else{
					return 'error没有选中的友链';
				}
			}else{
				return 'error操作失误，请重新操作';
			}
		}
	}
} 