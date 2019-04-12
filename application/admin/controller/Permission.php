<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Level as LevelModel;
use think\Validate;
use think\Request;
use page\Page;
//管理员权限设定
class Permission extends Common
{

	//展示页
	public function lst()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		
		$request_obj = Request::instance();
		//如果是ajax请求
		if ($request_obj->isAjax()) {
			//如果是查找创建者
			if (input('get.type') == 'creator') {
				//实例化模型 返回创建者的信息（id和名字）
				if ($res = $this->permissionModel->get_creator()) {
					$str = '';
					foreach ($res as $key) {
						$str .= "<a href='javascript:void(0);' onClick='permission_get_by_creator({$key['id']});' user_id = {$key['id']}>".$key['username']."</a>";
					}
					return $str;	
				}else{
					return "<a href='javascript:void(0);' style='color:red;'>当前没有创建过的权限</a>";
				}
			}
			//如果是根据权限名字查找
			if ($permission_name = input('get.permission_name')) {
				$per = $this->permissionModel->alias('a')
						->join('blog_admin b','a.creator_id=b.id and a.name='."'$permission_name'")
						->join('blog_admin c','a.reviser_id=c.id')
						->field('a.id,a.name,b.username creator,a.creat_time,c.username reviser,a.revise_time')
						->find();
				if ($per) {
					$this->assign('per_name',$permission_name);
					$this->assign('per',$per);
					return $this->fetch('lst_name_find');
				}else{
					return 'false';
				}
			}
		}
		//如果是pjax请求
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			//如果是刚点进来查看此页面
			if (input('post.')) {
				//所有数据
				$count = $this->permissionModel->select_lst();
				//获取到数据
				$data = $this->permissionModel->pjax_lst($count);
				//获取分页的样式
				$page_obj = new Page($count,10,'permission_page',1);
				$page = $page_obj->render;
				//输出模板变量
				$this->assign('page',$page);
				$this->assign('pers',$data);
				$this->assign('count',$count);
				//返回html
				return $this->fetch('permission/lst_pjax');

			}else if($input = input('get.')){
				//如果是get请求则表示pjax筛选条件
				//获取相应的数据长度
				if (isset($input['creator'])) {
					$count = $this->permissionModel->select_count_redis_permission_creator_zset_one($input['creator']);
				}else{
					//获取所有数据长度
					$count = $this->permissionModel->select_lst();
				}
				//获取页数
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$data = $this->permissionModel->pjax_lst($count,$input);
				$page_obj = new Page($count,10,'permission_page',$input['page']);
				$page = $page_obj->render;
				//输出模板变量
				$this->assign('page',$page);
				$this->assign('pers',$data);
				// $this->assign('count',$count);
				//返回html
				return $this->fetch('permission/lst_pjax_min');
			}
		}else{
				//如果是普通请求
				//获取页码
				$input = input('get.');
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				//获取创建者or没有创建者条件的数据长度
				if (isset($input['creator'])) {
					$count = $this->permissionModel->select_count_redis_permission_creator_zset_one($input['creator']);
				}else{
					$count = $this->permissionModel->select_lst();
				}
				//获取到数据
				$data = $this->permissionModel->pjax_lst($count,$input);
				//获取分页的样式
				$page_obj = new Page($count,10,'permission_page',$input['page']);
				$page = $page_obj->render;
				//输出模板变量
				$this->assign('page',$page);
				$this->assign('pers',$data);
				$this->assign('count',$this->permissionModel->select_lst());
				//返回html
				return $this->fetch();
		}
	}

	//添加权限页面
	public function add()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';

		$LevelModel = new LevelModel();
		$lvs = $this->arr_sort($LevelModel->select_lst(),'sort');
		$this->assign('lvs',$lvs);
		//如果是pjax请求
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('permission/add_pjax');
			// return '11';
		//如果是普通请求
		}else{
			return $this->fetch();
			// return $this->fetch('permission/add_pjax');

		}
	}

	//添加权限动作（ajax）
	public function add_change()
	{

		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断新增权限
		if ($this->user_permission['permission_c'] == 0) {return '您没有权限进行增加权限操作';}

		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if (!$data = input('post.')) {
				return '操作非法';
			}
			//验证权限名字是否存在  因业务需求，检查字段唯一只能自己来操作。
			if (trim($data['name'])) {
				if ($this->permissionModel->where(['name'=>$data['name']])->field('id')->find()) {
					return '名称已存在';
				}
			}
			//模块操作级别的验证（下拉框） 
			if ($per = $this->permissionModel->change_level_check($data,$this->user_id,$this->user_permission)) {
				// return $this->error($per);
				return $per;
			}
			//验证数据
			$validate = validate('Permission');
			if (!$validate->check($data)) {
				return $validate->getError();
			}
			//入库
			$res = $this->permissionModel->insertOne($data,$this->user_id);
			if ($res == 2) {
				return 'true';
			}else{
				return '新增失败！';
			}
		}else{
			$this->error('操作失误');
		}
	}

	//编辑页面展示
	public function edit()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//如果获取到id传参
		if ($id = input('id')) {
			$LevelModel = new LevelModel();
			$lvs = $this->arr_sort($LevelModel->select_lst(),'sort');
			$this->assign('lvs',$lvs);
			//查询此id的数据
			$per = $this->permissionModel->selectOne($id);
			if ($per) {//判断id是否存在
					$this->assign('per',$per);
				}else{
					$this->redirect('Index/index');
				}
			//如果是pjax请求
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				return $this->fetch('permission/edit_pjax');	
			//如果是普通请求
			}else{
				return $this->fetch();	
			}
		}else{//如果没有获取到id传参
			$this->error('操作失误');
		}
	}

	//编辑动作(ajax)
	public function edit_change()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断修改权限
		if ($this->user_permission['permission_u']==0) {return '您没有权限进行编辑权限操作';}
		if ($data = input('post.')) {
			$request_obj = Request::instance();
			//如果是ajax请求
			if ($request_obj->isAjax()) {
				//验证级别
				$per_level = $this->permissionModel->permission_check_level($data['id'],$this->user_permission,$this->user_id);
				if ($per_level == 1) {
					return '您不能设定自己的权限!';
				}else if ($per_level == 2) {
					return '此权限的创建者级别高，您无权修改!';
				}elseif ($per_level == 3) {
					return '操作有误!';
				}
				//模块操作级别的验证（下拉框）
				if ($per = $this->permissionModel->change_level_check($data,$this->user_id,$this->user_permission)) {
					return $per;
				}
				//验证权限表名称 由于业务需求 所以只能手动验证
				if (trim($data['name']) && $data['id']) {
					if ($this->permissionModel->where('name='.'\''.$data['name'].'\'and id <>'.$data['id'])->field('id')->find()) {
						return '名称已存在!';
					}
				}
				//验证字段
				$validate = validate('Permission');
				if (!$validate->check($data)) {
					return $validate->getError();
				}
				//修改动作
				if ($this->permissionModel->updateOne($data,$this->user_id)) {
					return 'true';
				}else{
					return '修改失败！';
				}
			}else{
				$this->error('操作非法!');
			}
		}else{
			$this->error('操作失误！');
		}
	}

	//删除动作
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';

		//判断删除权限
		if ($this->user_permission['permission_d']==0) {return '您没有权限进行此操作';}
		$adminModel = model('Admin');
		if ($id = input('post.id')) {
			//验证是否有此id
			if (!$per = $this->permissionModel->selectOne($id)) {
				return '操作失误！';
			}
			//获取创建者id
			$creator_id = $per['creator_id'];
			//验证级别 
			$res = $this->permissionModel->permission_check_level($id,$this->user_permission,$this->user_id);
			if ($res == '1') {
				return '您无法删除自己的权限！';
			}else if ($res == '2') {
				return '您此模块级别不够！';
			}
			if ($adminModel->where(['permission_id'=>$id])->find()) {
				return '有管理员正在使用此条权限，您无法删除！';
			}
			//删除此条id数据
			if ($this->permissionModel->deleteOne($id,$creator_id)) {
				return 'true';
			}else{
				return '删除失败';
			}
		}else{
			$this->error('操作失误');
		}
	}

	//编辑操作转移创建者并修改数据(ajax)
	//此方法不能直接使用，而是需要经过一轮验证才可以
	public function edit_move_per_lst()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断修改权限
		if ($this->user_permission['permission_u']==0) {return '您没有权限进行编辑权限操作';}
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				//验证级别
				$per_level = $this->permissionModel->permission_check_level($data['id'],$this->user_permission,$this->user_id);
				if ($per_level == 1) {
					return '您不能设定自己的权限!';
				}else if ($per_level == 2) {
					return '此条数据已变动！转移/修改失败！';
				}elseif ($per_level == 3) {
					return '操作有误!';
				}
				if (!Validate::token('__token__','',['__token__'=>$data['__token__']])) {
					return '不能重复提交！';
				}
				//转移创建者/修改数据
				$data['creator_id'] = $this->user_id;	//将当前用户id存入数组
				if ($this->permissionModel->edit_move_creator_change($this->user_id,$data)) {
					return 'true';
				}
				return '转移/修改失败！';
			}else{
				return '系统错误！';
			}
		}else{
			$this->redirect('Index/index');
		}
	}

	//批量转移创建者页面 （ajax）
	public function move_more_creator()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断修改权限
		if ($this->user_permission['permission_u']==0) {return 'error您没有权限进行编辑权限操作';}
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {
					foreach ($data['lst_form_checkbox_one'] as $key => $value) {
					 	$res = $this->permissionModel->permission_check_level($value,$this->user_permission,$this->user_id);
					 	if ($res == 1) {
					 		return 'error您所选中的权限中含有自己正在使用的权限，请勾掉后重新操作！';
					 	}elseif ($res == 2) {
					 		return 'error您选中的权限中，有部分创建者级别比您高或同级，请勾掉后重新操作！';
					 	}
					}
					$res = $this->permissionModel->select_more_redis_permission_lst_all_one($data['lst_form_checkbox_one'],$this->user_id,$this->user_permission);
					$this->assign('moves',$res);
					return $this->fetch('permission/move_per_lst');
				}else{
					return 'error没有选中的权限';
				}
			}else{
				die;
			}
		}else{
			die;
		}
	}

	//批量转移创建者处理动作 （ajax）
	public function move_more_creator_change()
	{
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断修改权限
		if ($this->user_permission['permission_u']==0) {return 'error您没有权限进行编辑权限操作';}
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (!Validate::token('__token__','',['__token__'=>$data['__token__']])) {
					return 'error请刷新页面后重新操作';
				}
				//验证数据 修改数据
				if ($res = $this->permissionModel->more_move_permission_creator_change($data['permission_moves_creator'],$this->user_permission)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}else{
			die;
		}
	}

	//批量删除权限处理动作 （ajax）
	public function more_delete()
	{	
		//判断可见权限
		$this->user_permission['permission_see']==0?($this->redirect('Index/index')):'';
		//判断删除权限
		if ($this->user_permission['permission_d']==0) {return 'error您没有权限进行此操作';}
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {
					foreach ($data['lst_form_checkbox_one'] as $key => $value) {
						$res = $this->permissionModel->permission_check_level($value,$this->user_permission,$this->user_id);	//验证权限创建者级别
						if ($res == 1) {
							return 'error您无法删除自己当前使用的权限表,请去除后重新尝试!';
						}else if($res == 2){
							return 'error部分权限创建者的权限设定模块比您高或平级，无法进行操作！';
						}
					}
					if ($res = $this->permissionModel->more_move_permission_delete($data['lst_form_checkbox_one'],$this->user_permission,$this->user_id)) {
						return 'error'.$res;
					}
					return 'true';
				}else{
					return 'error没有选中的权限';
				}
			}else{
				die;
			}
		}else{
			die;
		}
	}

	//查找权限详细信息 返回页面
	public function find_per()
	{
		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($per_id = input('post.per_id')) {
				if ($this->user_permission['permission_see'] == '0') {
					return 'error您没有查看权限详细信息的权限！';
				}
				if (!$per_id) {
					return 'error操作失误！';
				}
				if (!$per = $this->permissionModel->selectOne($per_id)) {//获取到当前表的所有信息
					return 'error没有此表信息！请刷新页面后重试！';
				}
				$levels = $this->arr_sort($levelModel->select_lst(),'sort');//查询所有级别并排序
				$this->assign('levels',$levels);
				$this->assign('per',$per);
				return $this->fetch('permission/find_per');
			}
		}else{
			return;
		}
	}
}