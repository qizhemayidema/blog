<?php
namespace app\admin\controller;

use app\admin\controller\Common;
use think\Request;
use app\admin\model\Notice as NoticeModel;
use page\Page;
	
//网站公告控制器
class Notice extends Common
{	
	//展示页面
	public function lst()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';

		$noticeModel = new NoticeModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $noticeModel->redis_select_notice_lst_all(true);
				$notices = $noticeModel->pjax_lst($count);
				$page_obj = new Page($count,10,'notice_page',1);
				$this->assign('count',$count);
				$this->assign('notices',$notices);
				$this->assign('page',$page_obj->render);
				return $this->fetch('notice/lst_pjax');
			}else{
				$input = input('get.');
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				if (isset($input['see'])) {				//查询总数据长度
					if ($input['see'] == 'show') {
						$count = $noticeModel->redis_select_notice_show_id_zset_count();
					}elseif ($input['see'] == 'top') {
						$count = $noticeModel->redis_select_notice_top_id_zset_count();
					}else{
						$count = $noticeModel->redis_select_notice_id_zset_count();
					}
				}else{
					$count = $noticeModel->redis_select_notice_id_zset_count();
				}
				$notices = $noticeModel->pjax_lst($count,$input);		//此处count的传参没有用处
				$page_obj = new Page($count,10,'notice_page',$input['page']);
				$this->assign('notices',$notices);
				$this->assign('page',$page_obj->render);
				return $this->fetch('notice/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			if (isset($input['see'])) {				//查询总数据长度
				if ($input['see'] == 'show') {
					$count = $noticeModel->redis_select_notice_show_id_zset_count();
				}elseif ($input['see'] == 'top') {
					$count = $noticeModel->redis_select_notice_top_id_zset_count();
				}else{
					$count = $noticeModel->redis_select_notice_id_zset_count();
				}
			}else{
				$count = $noticeModel->redis_select_notice_id_zset_count();
			}
			$notices = $noticeModel->pjax_lst($count,$input);		//此处count的传参没有用处
			$page_obj = new Page($count,10,'notice_page',$input['page']);
			$this->assign('count',$noticeModel->redis_select_notice_lst_all(true));
			$this->assign('notices',$notices);
			$this->assign('page',$page_obj->render);
			return $this->fetch();
		}
	}
	//添加页面（pjax）
	public function add()
	{	
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('notice/add_pjax');
		}else{
			return $this->fetch();
		}
	}
	//添加动作（pjax）
	public function add_change()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';

		$request_obj = Request::instance();
		$noticeModel = new NoticeModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['notice_c'] == '0') {
				return 'error您没有权限进行增加操作';
			}
			if ($data = input('post.')) {
				if (isset($data['show'])) {
					if ($res = $noticeModel->check_notice_count()) {		//检查数量
						return 'error'.$res;
					}
				}
				$validate = validate('Notice');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $noticeModel->insert_one($data)) {				//插入动作
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误,请刷新后重新尝试';
			}
		}
	}
	//编辑页面（pjax）
	public function edit()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';

		$noticeModel = new NoticeModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if ($notice_id = input('id')) {
				$notice = $noticeModel->redis_select_notice_lst_all_one($notice_id);
			}else{
				return 'error操作非法';
			}
			$this->assign('notice',$notice);
			return $this->fetch('notice/edit_pjax');
		}else{
			if ($notice_id = input('id')) {
				$notice = $noticeModel->redis_select_notice_lst_all_one($notice_id);
			}else{
				$this->error('操作失误,请刷新后重新尝试');
			}
			$this->assign('notice',$notice);
			return $this->fetch('notice/edit');
		}
	}
	//编辑动作（ajax
	public function edit_change()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';

		$request_obj = Request::instance();
		$noticeModel = new NoticeModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['notice_u'] == '0') {
				return 'error您没有权限进行修改操作！';
			}
			if ($data = input('post.')) {
				if (isset($data['show'])) {
					 if ($res = $noticeModel->check_notice_count($data)) {
					 	return 'error'.$res;
					 }
				}else{
					$data['show'] = 0;
				}
				$validate = validate('Notice');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $noticeModel->update_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}	
		}
	}
	//删除动作
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$noticeModel = new NoticeModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['notice_d'] == '0') {
				return 'error您没有权限进行删除操作！';
			}
			if ($notice_id = input('post.notice_id')) {
				$list = [];
				$list[] = $notice_id;
				if ($res = $noticeModel->delete_more($list)) {
						return 'error'.$res;
					}
					return 'true';
			}else{
				return 'error操作失误,请刷新后重新尝试';
			}
		}
	}
	//批量删除动作(ajax)
	public function more_delete_change()
	{
		//判断可见权限
		$this->user_permission['notice_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$noticeModel = new NoticeModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['notice_d'] == '0') {
				return 'error您没有权限进行删除操作！';
			}
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {
					if ($res = $noticeModel->delete_more($data['lst_form_checkbox_one'])) {
						return 'error'.$res;
					}
					return 'true';
				}
			}else{
				return 'error操作失误, 请刷新后重新尝试';
			}
		}
	}
}