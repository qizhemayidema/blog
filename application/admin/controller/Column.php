<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Column as ColumnModel;
use think\Request;
use page\Page;

//栏目控制器
class Column extends Common
{	
	//展示页面（pjax）
	public function lst()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';
		$columnModel = new ColumnModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $columnModel->redis_select_column_lst_all(true);
				$columns = $columnModel->pjax_lst($count);
				$page = new Page($count,10,'column_page',1);
				$this->assign('count',$count);
				$this->assign('columns',$columns);
				$this->assign('page',$page->render);
				return $this->fetch('column/lst_pjax');
			}else{
				$input = input('get.');
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$count = $columnModel->redis_select_column_lst_all(true);
				$columns = $columnModel->pjax_lst($count,$input);
				$page = new Page($count,10,'column_page',$input['page']);
				$this->assign('columns',$columns);
				$this->assign('page',$page->render);
				return $this->fetch('column/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$count = $columnModel->redis_select_column_lst_all(true);
			$columns = $columnModel->pjax_lst($count,$input);
			$page = new Page($count,10,'column_page',1);
			$this->assign('count',$count);
			$this->assign('columns',$columns);
			$this->assign('page',$page->render);
			return $this->fetch();
		}
	}

	//添加页面 (pjax)
	public function add()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';

		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('column/add_pjax');
		}else{
			return $this->fetch();
		}
	}

	//添加动作ajax
	public function add_change()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['column_c'] == 0) {
			return 'error您没有权限进行添加操作';
		}
		$request_obj = Request::instance();
		$columnModel = new ColumnModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				$validate = validate('Column');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $columnModel->insert_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}
		}
	}

	//编辑页面(pjax)
	public function edit()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';

		$columnModel = new ColumnModel();
		if ($id = input('id')) {
			if ($column = $columnModel->redis_select_column_lst_all_one($id)) {
				$this->assign('column',$column);
				if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
					return $this->fetch('column/edit_pjax');
				}else{
					return $this->fetch();
				}
			}else{
				return $this->error('请刷新后重新尝试');
			}
		}else{
			$this->error('操作失误');
		}
	}

	//编辑动作（ajax）
	public function edit_change()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$columnModel = new ColumnModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['column_c'] == 0) {
					return 'error您没有权限进行修改操作';
				}
				$validate = validate('Column');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $columnModel->update_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}
	}

	//删除动作
	public function delete_change()
	{
		//判断权限
		$this->user_permission['column_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$columnModel = model('admin/Column');
		if ($request_obj->isAjax()) {
			if ($column_id = input('post.column')) {
				if ($this->user_permission['column_d'] == '0') {
					return 'error您没有权限进行删除操作';
				}
				if ($res = $columnModel->delete_one($column_id,$this->user_permission['article_d'])) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error删除失败，请刷新后重新尝试';
			}
		}
	}
}