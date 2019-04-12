<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Level as LevelModel;
use app\admin\model\Permission as PermissionModel;
use think\Request;
use think\Validate;
use page\Page;

//管理员级别模块
class Level extends Common
{	
	//展示页面
	public function lst()
	{	
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($type = input('post.type')) {
				if ($type == 'find_creator') {		//如果请求为查找有创建过级别的创建者
					if ($res = $levelModel->get_creator()) {
						$str = '';
						foreach ($res as $key) {
							$str .= "<a href='javascript:void(0);' onclick='level_get_by_creator({$key['id']});' user_id = {$key['id']}>".$key['username']."</a>";
						}
						return $str;		
					}else{
						return "<a href='javascript:void(0);' style='color:red;'>当前没有创建过的级别</a>";
					}
				}
				if ($type == 'find_level_name') {	//如果请求是根据级别名称寻找
					if ($level_name = input('post.level_name')) {
						if ($level_id = $levelModel->where(['name'=>$level_name])->value('id')) {
							$res = $levelModel->select_lst_all_one($level_id);
							$this->assign('level',$res);
							return $this->fetch('level/find_level_name');
						}else{
							return 'error没有此级别，建议您刷新后重试';
						}
					}else{
						return 'error没有此级别，建议您刷新后重试';
					}
				}
			}
		}

		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $levelModel->select_lst_all(true);
				$res = $levelModel->pjax_lst($count);
				//获取分页的样式
				$page_obj = new Page($count,10,'level_page',1);
				$page = $page_obj->render;
				$this->assign('page',$page);
				$this->assign('levels',$res);
				$this->assign('count',$count);
				return $this->fetch('level/lst_pjax');
			}else{
				$data = input('get.');
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				//如果有创建者
				if (isset($data['creator'])) {
					$count = $levelModel->select_count_redis_level_creator_zset_one($data['creator']);
				}else{
					$count = $levelModel->select_lst_all(true);
				}
				$res = $levelModel->pjax_lst($count,$data);		//获取到的符合条件的数据
				$page_obj = new Page($count,10,'level_page',$data['page']);
				$page = $page_obj->render;						//分页代码
				$this->assign('page',$page);
				$this->assign('levels',$res);
				return $this->fetch('level/lst_pjax_min');
			}
		}else{
				$data = input('get.');
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				//如果有创建者
				if (isset($data['creator'])) {
					$count = $levelModel->select_count_redis_level_creator_zset_one($data['creator']);
				}else{
					$count = $levelModel->select_lst_all(true);
				}
				$res = $levelModel->pjax_lst($count,$data);		//获取到的符合条件的数据
				$page_obj = new Page($count,10,'level_page',$data['page']);
				$page = $page_obj->render;						//分页代码
				$this->assign('page',$page);
				$this->assign('levels',$res);
				$this->assign('count',$levelModel->select_lst_all(true));
				return $this->fetch();
		}
	}

	//添加等级页面(pjax)
	public function add()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('level/add_pjax');
		}else{
			return $this->fetch();
		}
	}

	//添加等级动作(ajax)
	public function add_change()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['level_c'] == 0) { return 'error您没有权限进行增加操作';}
		$levelModel = new LevelModel();
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($data['sort']) {
					if(!preg_match("/^[1-9][0-9]*$/",$data['sort'])){		//验证正整数
						return 'error级别等级只限正整数，且大于0！';
					}
					if ($res = $levelModel->check_level($this->user_permission['level_level_id'],$data['sort'])) {	//验证新增的等级是否合法
						return 'error'.$res;
					}
				}
				$validate_obj = validate('Level');
				if (!$info = $validate_obj->check($data)) {			//验证字段
					return 'error'.$validate_obj->getError();
				}
				if ($res = $levelModel->insert_one($data,$this->user_id)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}
	}

	//编辑等级页面(pjax)
	public function edit()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';

		$levelModel = new LevelModel();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if ($level_id = input('level_id')) {
				if (!$res = $levelModel->select_lst_all_one($level_id)) {
					return '操作失败！请刷新页面后重新操作';
				}
				$this->assign('level',$res);
				return $this->fetch('level/edit_pjax');
			}
		}else{
			if ($level_id = input('level_id')) {
				if (!$res = $levelModel->select_lst_all_one($level_id)) {
					$this->error('操作失误！');
				}
				$this->assign('level',$res);
				return $this->fetch();
			}
		}
	}

	//编辑动作（ajax）
	public function edit_change()
	{	
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['level_u'] == 0) {return 'error您没有权限进行编辑操作';}

		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				$validate = validate('LevelEdit');
				if (!$validate->check($data)) {		//首先验证表单是否完整
					return 'error'.$validate->getError();
				}
				if(!preg_match("/^[1-9][0-9]*$/",$data['sort'])){		//验证正整数
					return 'error级别等级只限正整数，且大于0！';
				}
				if (!$levelModel->where(['id'=>$data['id']])->field('id')->find()) {
					return 'error该级别不存在！';
				}
				if ($levelModel->where('name = \''.$data['name'].'\' and id <> '.$data['id'])->find()) {
					return 'error该级别名称已存在！';
				}
				if ($levelModel->where('sort = \''.$data['sort'].'\' and id <> '.$data['id'])->find()) {
					return 'error该级别等级已存在！';
				}
				$tip = isset($data['tip'])?$data['tip']:'';
					//判断操作是否合法
				if ($res = $levelModel->check_creator_level($data,$this->user_id,$this->user_permission,$tip)) {
					if ($res == '1') {
						return 'error无法更改！您所使用的权限中正在使用此项级别！';
					}else if($res == '2'){
						return 'error此级别的创建者级别比您高或相同，无法更改！';
					}else if($res == '4'){
						return 'error您所修改的级别不能超过自己权限中级别模块的级别！';
					}
					if (!isset($data['tip'])) {			//此处为判断分支，如果存在则表示已经经过提示并确认
						if ($res == '3') {
							return 'tip1有权限表正在使用您当前编辑的级别，您确定更改此项级别吗？';
						}
						if($res == '5'){
							return 'tip2您将要修改成的级别等级超过了创建者的级别模块的级别，如您想要修改，则需转移创建者到您的的名下，您确定吗？';
						}
					}else if($data['tip'] == 'tip1'){
						if($res == '5'){
							return 'tip2您将要修改成的级别等级超过了创建者的级别模块的级别，如您想要修改，则需转移创建者到您的的名下，您确定吗？';
						}
					}else if($data['tip'] == 'tip2'){
						//空操作
					}
				}
				if (!Validate::token('__token__','',['__token__'=>$data['__token__']])) {	//验证token
					return 'error不能重复提交！';
				}
				if (isset($data['tip'])) {unset($data['tip']);}
				if ($tip == 'tip2') {		//修改数据并转移创建者
					$res = $levelModel->update_move_one($data,$this->user_id);
				}else{
					if ($res = $levelModel->update_one($data,$this->user_id)) {
						return 'error'.$res;
					}
				}
				return 'true';
			}
		}else{
			die;
		}
	}

	//删除动作（ajax）
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['level_d'] == 0) {return 'error您没有权限进行删除操作';}

		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($level_id = input('post.level_id')) {
				if(!preg_match("/^[1-9][0-9]*$/",$level_id)){		//验证正整数
					return 'error操作失误！请刷新页面后重新操作';
				}
				if (!$levelModel->where(['id'=>$level_id])->field('id')->find()) {		//判断是否存在
					return 'error没有此级别，建议您刷新后重试';
				}
				if ($res = $levelModel->check_creator_level($level_id,$this->user_id,$this->user_permission)) {
					if ($res == '1') {
						return 'error您当前所使用的权限中含有此个级别，无法删除';
					}else if ($res == '2') {
						return 'error您当前删除的此个级别的创建者【权限级别】模块比您高或相同，无法删除';
					}else if ($res == '3') {
						return 'error当前有权限表正在使用此级别，无法删除';
					}
				}
				if ($res = $levelModel->delete_one($level_id)) {
					return 'error'.$res;
				}
				return 'true';
			}
		}else{
			die;
		}
	}

	//查看级别表详细信息
	public function find_level()
	{
		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($level_id = input('post.level_id')) {
				if ($this->user_permission['level_see'] == 0) {
					return 'error您没有权限级别模块的查看权限！';
				}
				if (!$res = $levelModel->select_lst_all_one($level_id)) {		//判断是否存在
					return 'error没有此级别，建议您刷新后重试';
				}
				$this->assign('level',$res);
				return $this->fetch();
			}
		}
	}

	//批量转移创建者页面
	public function move_more_creator()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['level_u'] == 0) {return 'error您没有权限进行编辑操作';}

		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {		//一维数组 value为将要转移的表的id
					$res = $levelModel->more_check($data['lst_form_checkbox_one'],$this->user_id,$this->user_permission);//验证数据
					if ($res == '1') {
						return 'error您当前所使用的权限中含有部分所选级别，请勾掉后重新尝试';
					}else if ($res == '2') {
						return 'error操作失误,请刷新后重新尝试';
					}else if ($res == '3') {
						return 'error您当前转移的部分级别的创建者级别比您高，请勾掉后重新尝试';
					}else if($res == '4'){
						return 'error您当前转移的部分级别的级别等级比您高或相同，请勾掉后重新尝试';
					}else if($res == '5'){
						return 'error操作失误,请刷新后重新尝试';
					}

					//返回符合条件的数据
					$data = $levelModel->more_move_creator_data($data['lst_form_checkbox_one'],$this->user_id,$this->user_permission);
					$this->assign('moves',$data);
					return $this->fetch();
				}else{
					return 'error您的操作有误，建议您刷新后重试';
				}
			}
		}
	}

	//批量转移创建者动作
	public function move_more_creator_change()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($this->user_permission['level_u'] == 0) {
				return 'error您没有权限进行编辑操作';
			}
			if ($data = input('post.')) {
				if ($data['level_moves_creator']) {
					$list = [];
					foreach ($data['level_moves_creator'] as $key => $value) {
						$list[] = $key;
					}
					$res = $levelModel->more_check($list,$this->user_id,$this->user_permission);//验证数据
					if ($res == '1') {
						return 'error您当前所使用的权限中含有部分所选级别，请勾掉后重新尝试';
					}else if ($res == '2') {
						return 'error操作失误,请刷新后重新尝试';
					}else if ($res == '3') {
						return 'error您当前转移的部分级别的创建者级别比您高，请勾掉后重新尝试';
					}else if($res == '4'){
						return 'error您当前转移的部分级别的级别等级比您高或相同，请勾掉后重新尝试';
					}else if($res == '5'){
						return 'error操作失误,请刷新后重新尝试';
					}
					if ($res = $levelModel->more_check_move($data['level_moves_creator'])) {	//验证被转移的级别与将要转移到的管理员
						return 'error'.$res;
					}
					if (!Validate::token('__token__','',['__token__'=>$data['__token__']])) {	//验证token
						return 'error不能重复提交！';
					}
					if ($res = $levelModel->more_move_creator($data['level_moves_creator'])) {		//转移动作
						return 'error'.$res;
					}
					return 'true';
				}else{
					return '操作失误！请刷新后重新尝试';
				}
			}
		}
	}

	//批量删除动作
	public function more_delete_change()
	{
		//判断可见权限
		$this->user_permission['level_see']==0?($this->redirect('Index/index')):'';
		if ($this->user_permission['level_d'] == 0) {return 'error您没有权限进行删除操作';}
		$request_obj = Request::instance();
		$levelModel = new LevelModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['lst_form_checkbox_one'])) {
					$res = $levelModel->more_check($data['lst_form_checkbox_one'],$this->user_id,$this->user_permission,true);//验证数据
					if ($res == '1') {
						return 'error您当前所使用的权限中含有部分所选级别，请勾掉后重新尝试';
					}else if ($res == '2') {
						return 'error操作失误,请刷新后重新尝试';
					}else if ($res == '3') {
						return 'error您的级别等级比部分选中的级别创建者的等级低，无法删除';
					}else if($res == '4'){
						return 'error您的级别等级比部分选中的级别等级低，无法删除';
					}else if($res == '5'){
						return 'error操作失误,请刷新后重新尝试';
					}else if($res == '6'){
						return 'error您当前所选中的级别中有正在权限表中正在使用的级别，请确保将要删除的级别没有权限在使用，之后才可删除';
					}
					if ($res = $levelModel->more_delete($data['lst_form_checkbox_one'])) {		//删除动作
						return 'error'.$res;
					}
					return 'true';
				}else{
					return 'error操作失误！请刷新页面后重新操作';
				}
			}
		}
	}
}