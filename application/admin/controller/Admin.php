<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use think\Request;
use app\admin\model\Admin as AdminModel;
use app\admin\model\Level as LevelModel;
use think\Validate;
use page\Page;
use think\Config;

//管理员类
class Admin extends Common
{
	//展示管理员页面
	public function lst()
	{	
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		$adminModel = new AdminModel();
		$request_obj = Request::instance();
		
		if ($request_obj->isAjax()) {
			if (input('get.type') == 'find_admin_name') {		//如果此请求是根据管理员名查找
				if (!input('get.name')) {
					return 'error您的操作非法！';
				}
				if (!$res = $adminModel->where(['username'=>input('get.name')])->find()) {
					return 'error没有找到此管理员';
				}
				$this->assign('admin',$res);
				return $this->fetch('admin/find_admin_name');
			}
		}
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {	//如果是pjax请求
			if (input('post.')) {
				$count = $adminModel->lst(true);			//所有管理员的数量
				$page = new Page($count,10,'admin_page',1);
				$res = $adminModel->admin_page_lst($count);		//查找第一页的数据
				$this->assign('page',$page->render);
				$this->assign('admins',$res);
				$this->assign('count',$count);	//有几个管理员
				return $this->fetch('admin/lst_pjax');
			}
			if ($data = input('get.')) {
				$count = $adminModel->lst(true);			//所有管理员的数量
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				$page = new Page($count,10,'admin_page',$data['page']);
				$res = $adminModel->admin_page_lst($count,$data);		//查找符合条件的数据
				$this->assign('page',$page->render);
				$this->assign('admins',$res);
				$this->assign('count',$count);	//有几个管理员
				return $this->fetch('admin/lst_min_pjax');
			}
		}else{
				$data = input();
				$count = $adminModel->lst(true);			//所有管理员的数量
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				$page = new Page($count,10,'admin_page',$data['page']);
				$res = $adminModel->admin_page_lst($count,$data);		//查找第一页的数据
				$this->assign('page',$page->render);
				$this->assign('admins',$res);
				$this->assign('count',$count);	//有几个管理员
				return $this->fetch();
		}
	}

	//展示管理员新增页面
	public function add()
	{
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		
		$permission_lst = $this->permissionModel->select_lst(true);
		ksort($permission_lst);		//对数组进行排序 按照索引下标
		$this->assign('permission',$permission_lst);
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('admin/add_pjax');
		}else{
			return $this->fetch();
		}
	}
	//新增动作（只接受ajax）
	public function add_change()
	{	
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['admin_c'] == 0) {return '您没有权限进行增加权限操作';}

		$request_obj = Request::instance();
		$adminModel = new AdminModel();
		$levelModel = new LevelModel();

		//如果是ajax请求
		if ($request_obj->isAjax()) {
			//如果是提交表单
			if ($data = input('post.')) {
				//判断是否有头像上传上来和验证
				if ($_FILES['dpic']['error'] == 0) {
					//判断文件大小，后辍，mime和图片是否合法
					$img = $request_obj->file('dpic');
					$info = $img->check(['size'=>102400,'ext'=>'jpg,png']);
					if (!$info) {
						return $img->getError();
					}
					$data['img'] = $_FILES['dpic'];		//图片信息存入数组
				}
				$data['dpic'] = '';
				//验证管理员权限分配
				if (!$data['permission_id']) {
					return '分配权限必须填写！';
				}else{
					if ($info = $adminModel->check_permission($this->user_id,$this->user_permission,$data['permission_id'])) {
						return $info;
					}
				}
				//验证表单
				$validate = validate('Admin');
				if (!$validate->check($data)) {
					return $validate->getError();
				}
				//新增一条数据
				if ($info = $adminModel->admin_insert_one($data)) {	//新增动作
					return $info;
				}
				return 'true';
			}
		}else{
			$this->error('操作失误！');
		}
	}

	//展示修改管理员信息
	public function edit()
	{
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';

		if ($id = input('id')) {
			$adminModel = new AdminModel();
			$data = $adminModel->admin_lst_one($id);
			if ($data == '1') {
				$this->redirect('Index/index');
			}
			$pers = $this->permissionModel->select_lst(true);
			ksort($pers);		//对数组进行排序 按照索引下标
			$this->assign('pers',$pers);
			$this->assign('data',$data);
		}
		//如果是pjax
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('admin/edit_pjax');
		}else{
			return $this->fetch();
		}
	}

	//更改权限动作（只接受ajax）
	public function edit_change()
	{	
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['admin_u'] == 0) {return '您没有权限进行增加编辑操作';}

		$request_obj = Request::instance();
		$adminModel = new AdminModel();
		//如果为post提交
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($data['id'] == $this->user_id) {		//如果修改的是自己的管理员信息
					if ($data['permission_id'] != $this->user_permission['id']) {		//不能更改自己账号权限
						return 'error您不能更改自己账号的权限！';
					}
				}else{//如果是别人的账号
					//验证被操作的管理员级别
					if ($info = $adminModel->check_admin_level($this->user_id,$this->user_permission['admin_level_id'],$data['id'])) {
						return 'error'.$info;
					}
					//验证用户与选中的权限表的级别
					if ($info = $adminModel->check_permission($this->user_id,$this->user_permission,$data['permission_id'])) {
						return 'error'.$info;
					}
				}
				//如果有图片上传上来
				if ($_FILES['dpic']['error'] == 0) {
					$img = $request_obj->file('dpic');
				 	$info =$img->check(['size'=>102400,'ext'=>'jpg,png']);
				 	if (!$info) {
				 		return 'error'.$img->getError();
				 	}
				 	$data['img'] = $_FILES['dpic'];		//将dpic信息存入data数组中
				}
				//验证表单
				$validate = validate('admin/AdminEdit');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				//修改一条数据
				if ($res = $adminModel->admin_update_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return ;
			}	
		}else{
			return ;
		}
	}

	//删除动作
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['admin_d'] == 0) {return '您没有权限进行增加删除操作';}

		$request_obj = Request::instance();
		$adminModel = new AdminModel();
		if ($request_obj->isAjax()) {
			//如果是post请求且有id传过来	此id为将要删除的用户id
			if ($del_admin_id = input('post.id')) {
				//先判断有没有这个管理员存在
				$res = $adminModel->admin_lst_one($del_admin_id);
				if ($res == '1'){			
					return '您操作有误,请刷新页面后重试';
				}
				//检查删除级别		
				if ($info = $adminModel->check_del_admin_level($this->user_id,$del_admin_id,$this->user_permission,$res['permission_id'])) {
					return $info;
				}
				//检查将要删除的管理员有没有创建过的数据 所有表
				$sql = 'SELECT b.creator_id AS b,c.creator_id AS c FROM  blog_level AS b INNER JOIN blog_permission AS c ON (b.creator_id = '.$del_admin_id.' or c.creator_id = '.$del_admin_id.' ) LIMIT 1;';
				if ($adminModel->query($sql)) {
					return 'tip此用户有创建过的数据，如果您执意删除此管理员，则此管理员创建过的数据需要转移到符合条件的管理员下，您确定吗？';
				}
				//这里直接做删除
				if (!$adminModel->admin_delete_one($del_admin_id,$this->user_id)) {
					return '删除失败，您刷新页面后重试';
				}
				return 'true';
			}
		}
		$this->error('操作失误！');
	}

	//转移创建者页面（ajax）
	public function move_creator()
	{
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['admin_d'] == 0) {return '您没有权限进行增加删除操作';}

		$adminModel = new AdminModel;
		$levelModel = new LevelModel;
		$request_obj = Request::instance();
		//如果是ajax请求 就表明是要转移创建者
		if ($request_obj->isAjax()) {
			if ($move_admin_id = input('post.move_admin_id')) {		//将要被转移创建者的管理员id

				//先判断有没有这个管理员存在
				$res = $adminModel->admin_lst_one($move_admin_id);
				if ($res == '1'){			
					return 'error您操作有误,请刷新页面后重试';
				}
				//检查删除级别
				if ($info = $adminModel->check_del_admin_level($this->user_id,$move_admin_id,$this->user_permission,$res['permission_id'])) {
					return 'error'.$info;
				}
				//能到这的话 管理员列表模块级别一定比将要删除的大

				//将被删除的管理员创建过的权限表
				if ($per_lst = $this->permissionModel->where(['creator_id'=>$res['id']])->field('id,name')->select()) {
					//查找符合条件的创建者
					$per_admins = $adminModel->select_per_move_admin($this->user_id,$this->user_permission,$move_admin_id);
					if (is_string($per_admins)) {		//如果是字符串，则表明是提示信息
						return 'error'.$per_admins;
					}
				}else{
					$per_admins = [];
				}

				//将被转移的用户的权限表信息
				$move_admin_per = $this->permissionModel->select_admin_per($move_admin_id);

				//将被删除的管理员创建过的级别
				if ($level_lst = $levelModel->where(['creator_id'=>$move_admin_id])->field('id,name')->select()) {
					//查询符合条件的创建者
					$level_admins = $adminModel->select_level_move_admin($this->user_id,$this->user_permission,$move_admin_id,$move_admin_per);
					if (is_string($level_admins)) {		//如果是字符串，则表明是提示信息
						return 'error'.$level_admins;
					}
				}else{
					$level_admins = [];
				}
				//返回表单
				$this->assign('per_lst',$per_lst);
				$this->assign('per_admins',$per_admins);
				$this->assign('level_lst',$level_lst);
				$this->assign('level_admins',$level_admins);
				$this->assign('move_admin_id',$move_admin_id);
				return $this->fetch();
			}else{
				die;
			}
		}else{
			$this->error('操作非法');
		}
	}

	//转移并删除
	public function move_del()
	{
		//判断可见权限
		$this->user_permission['admin_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['admin_d'] == 0) {return 'error您没有权限进行删除操作';}
		$request_obj = Request::instance();
		$adminModel = new AdminModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
					Config::set('default_ajax_return','json');
					if (!Validate::token('__token__','',['__token__'=>$data['__token__']])) {
						return 'error不能重复提交！';
					}
					//转移创建者并做相关验证
					if ($res = $adminModel->move_creator_all($data,$this->user_permission,$this->user_id)) {
						return 'error'.$res;
					}
					//删除动作
					if (!$adminModel->admin_delete_one($data['move_admin_id'],$this->user_id)) {
						return 'error转移成功，删除管理员失败！';
					}
					return 'true';
			}else{
				return 'error操作失误!';
			}
		}else{
			$this->redirect('Index/index');
		}
	}
}
