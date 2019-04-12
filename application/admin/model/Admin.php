<?php 
namespace app\admin\model;

use think\Model;
use think\Validate;
use think\Image;
use think\cache\driver\Redis;
class Admin extends Model
{	

	public $redis_obj;		//redis实例化对象

	public function initialize()//模型初始化
	{
		$this->redis_obj = new Redis();
	}

	//登陆验证
	public function check($data)
	{
		$token_res = Validate::token('__token__','',['__token__'=>$data['__token__']]);
		if (!$token_res) {
			//重复提交
			return 1;
		}elseif (!$this::get(['username'=>$data['username'],'password'=>md5($data['password'])])) {
			//数据库账号密码
			return 2;
		}else{
			//登陆成功
			return 3;
		}

	}

	//查找admin_lst中的所有数据
	public function lst($count = false)		//如果count为true则返回admin_lst的长度
	{
		if ($count === true) {
			if ($count =  $this->redis_obj->Hlen('admin_lst')) {
				return $count;
			}else{
				$res = $this->all();
				foreach ($res as $key) {
					$this->redis_obj->Hset('admin_lst',$key['id'],$key);
				}
				return count($res);
			}
		}

		if ($res = $this->redis_obj->Hgetall('admin_lst')) {
			return $res;
		}else{
			$res = $this->redis_admin_lst_all(true);
			return $res;
		}
	}

	//查找单条数据 admin_lst
	public function admin_lst_one($admin_id)		//管理员id
	{
		if ($res = $this->redis_obj->Hget('admin_lst',$admin_id)) {
			return $res;
		}else{
			if (!$data = $this->where('id='.$admin_id)->find()) {
				return '1';		//没有此id	
			}
			$this->redis_admin_lst_all();
			return $data;
		}
	}

	//判断管理员是否存在
	public function admin_exists($admin_id)		//管理员id
	{
		if ($this->redis_obj->Hexists('admin_lst',$admin_id)) {
			return true;
		}else{
			$this->redis_admin_lst_all();
			if ($this->redis_obj->Hexists('admin_lst',$admin_id)) {
				return true;
			}
			return false;
		}
	}

	//上传管理员头像并返回头像的路径
	public function dpic_upload($img)	//为$_FILE中的dpic
	{
		$houzhui = strrchr($img['name'],'.');//.xxx

		$image_obj = Image::open($img['tmp_name']);		//打开文件资源
		$image_obj->thumb(125,125,1);	//折叠图片
		$name = uniqid();	//名字
		$path = DS.'static'.DS.'admin'.DS.'dpic'.DS.$name.$houzhui;
		if ($image_obj->save(ROOT_PATH.'public'.$path)) {
			return $path;
		}else{
			return false;
		}
	}

	//插入一条数据并存入redis相关的表
	public function admin_insert_one($data)						//data单条用户表数据
	{	
		if (isset($data['img'])) {								//判断是否有图片上传上来
			if (!$data['dpic'] = $this->dpic_upload($data['img'])) {
				return '头像上传失败！';
			}
			unset($data['img']);
		}
		unset($data['__token__']);								//去掉token的元素
		$data['password'] = md5($data['password']);				//md5加密
		if ($this->save($data)) {
			if (!$this->redis_obj->exists('admin_lst')) {			//如果redis中没有admin_lst表则更新
				$res = $this->all();
				foreach ($res as $key) {
					$this->redis_obj->Hset('admin_lst',$key['id'],$key);
				}
			}else{
				$data['id'] = $this->id;						//将新增数据的id存入data中
				$this->redis_admin_lst_one($data);
				$this->redis_admin_id_zset_one($this->id);
			}
		}else{
			if (isset($data['dpic'])) {
				if (file_exists(ROOT_PATH.'public'.$data['dpic']) && $data['dpic'] != '') {
					unlink(ROOT_PATH.'public'.$data['dpic']);	//如果有图片上传上来了 再删除掉之前的
				}
			}
			return '新增失败，请联系管理员';					//插入失败
		}
	}

	//修改一条数据并存入redis相关的表
	public function admin_update_one($data)		//修改的信息 如果要修改头像要在data中有$_FILES的数组
	{	
		//用户id
		$id = $data['id'];

		//如果有图片上传上来
		if (isset($data['img'])) {
			$img_path = $this->dpic_upload($data['img']);
			if (!$img_path) {
				return '头像上传失败！';
			}
			$old_data = $this->admin_lst_one($id);
			if (file_exists(ROOT_PATH.'public'.$old_data['dpic']) && $old_data['dpic'] != '') {
				unlink(ROOT_PATH.'public'.$old_data['dpic']);
			}
			$data['dpic'] = $img_path;
			unset($data['img']);
		}
		if (isset($data['__token__'])) {		//去掉数组中的token
			unset($data['__token__']);
		}
		if ($data['password'] == '') {			//如果密码为空则去掉这个元素
			unset($data['password']);
		}else{
			$data['password'] = md5($data['password']);	//否则加密
		}
		if ($this->update($data)) {				//存入redis中的相关表
			$data = $this->where(['id'=>$id])->find();
			$this->redis_admin_lst_one($data);
		}else{									
			return '修改失败！，如有问题请联系站长';
		}
	}

	//删除一个管理员并更新相关的表
	public function admin_delete_one($del_admin_id,$user_id)		//将要删除的管理员id	当前用户id
	{
		if ($del = $this->where(['id'=>$del_admin_id])->field('dpic')->find()) {

			//删除相关的表
			if ($this->destroy($del_admin_id)) {
				$this->del_redis_admin_lst_one($del_admin_id);
				$this->del_redis_admin_id_zset_one($del_admin_id);	
			}else{
				return false;
			}
			//删除头像						
			if (file_exists(ROOT_PATH.'public'.$del->dpic) && $del->dpic != '') {
				unlink(ROOT_PATH.'public'.$del->dpic);
			}
			$permissionModel = model('Permission');
			$levelModel = model('Level');
			$list = [];
			if ($res = $permissionModel->where(['reviser_id'=>$del_admin_id])->field('id')->select()) {		//更新权限表内的修改者
				for ($i=0; $i < count($res); $i++) { 
					$list[$i]['id'] = $res[$i]['id'];
					$list[$i]['reviser_id'] = $user_id;
				}
				if ($permissionModel->saveAll($list)) {
					foreach ($list as $key) {
						$permissionModel->redis_permission_lst_one($key['id']);
						$permissionModel->redis_permission_lst_all_one($key['id']);
					}
				}
			}
			$list = [];
			if ($res = $levelModel->where(['reviser_id'=>$del_admin_id])->field('id')->select()) {			//更新级别表内的修改者
				for ($i=0; $i < count($res); $i++) { 
					$list[$i]['id'] = $res[$i]['id'];
					$list[$i]['reviser_id'] = $user_id;
				}
				if ($levelModel->saveAll($list)) {
					foreach ($list as $key) {
						$levelModel->redis_update_level_lst_all_one($key['id']);
					}
				}
			}
			return true;
		}else{
			return false;
		}
	}

	//检查管理员权限分配是否合法
	//条件：用户admin模块的级别必须大于当前选中的权限表的admin模块的级别才能设定
	public function check_permission($user_id,$user_permission,$now_permission_id)		//当前用户id，当前用户权限表，当前选中权限表id
	{
		$permissionModel = model('Permission');
		$levelModel = model('Level');
		$now_permission = $permissionModel->selectOne($now_permission_id);					//获取当前选中的权限表信息
		if ($now_permission['creator_id'] != $user_id) {									//如果不是自己的表

			$now_admin_sort = $levelModel->select_lst_one($now_permission['admin_level_id']);	//获取当前选中的权限表 admin模块权限级别
			$user_admin_sort = $levelModel->select_lst_one($user_permission['admin_level_id']); //当前用户权限表 admin模块权限级别
			$now_per_sort = $levelModel->select_lst_one($now_permission['permission_level_id']); //当前选中权限表的权限设定级别
			$user_per_sort = $levelModel->select_lst_one($user_permission['permission_level_id']);//当前用户权限表 permission模块权限级别
			if ($now_admin_sort <= $user_admin_sort) {
				return '您在此模块的级别比选中权限表的管理员列表级别低或相同，无法选择此权限，建议您使用自己创建的权限';
			}
			if ($now_per_sort <= $user_per_sort) {
				return '您的权限设定级别比选中权限表的权限设定级别低或相同，无法选择此权限，建议您使用自己创建的权限';
			}
		}
	}

	//检查当前更改的用户管理员级别与当前用户作比较
	//注意，这是用户与用户之间的admin级别比较
	public function check_admin_level($user_id,$user_admin_level_id,$act_id)		//当前用户的管理员列表模块级别id，所要进行更改的用户id
	{
		if ($user_id == $act_id) {		//排除是自己的账号
			return false;
		}
		$permissionModel = model('Permission');
		$levelModel = model('Level');
		if ($act_per_id = $this->where(['id'=>$act_id])->field('permission_id')->find()) {
			$act_per = $permissionModel->selectOne($act_per_id['permission_id']);
			$user_admin_level = $levelModel->select_lst_one($user_admin_level_id);
			$act_admin_level = $levelModel->select_lst_one($act_per['admin_level_id']);
			if ($user_admin_level >= $act_admin_level) {
				return '在此模块中，您当前编辑的管理员的级别比您高或相同，无法进行操作！';
			}
		}else{
			return '没有此管理员的信息';
		}
	}

	//检查删除的管理员级别是否合法		参数：当前用户id，当前要删除的用户id,当前用户权限表，当前将要删除的管理员的权限表id
	public function check_del_admin_level($user_id,$del_admin_id,$user_per,$del_user_per_id)	
	{
		if ($user_id == $del_admin_id) {
			return '您无法删除自己的账号，如想注销账号，请联系有关管理员或站长！';
		}
		$permissionModel = model('Permission');
		$levelModel = model('Level');
		$user_level = $levelModel->select_lst_one($user_per['admin_level_id']);
		$del_per = $permissionModel->selectOne($del_user_per_id);
		$del_level = $levelModel->select_lst_one($del_per['admin_level_id']);
		if ($user_level >=$del_level) {
			return '您此模块的级别比将要删除的管理员级别低或相同，无法删除！';
		}
		
	}

	//删除动作中，查找符合条件的转移权限表创建者
	public function select_per_move_admin($user_id,$user_permission,$creator_id)//当前用户id，当前用户权限表信息，当前所选中要转移权限创建者的用户id
	{
		$permissionModel = model('Permission');
		$levelModel = model('Level');
		$del_user_per = $permissionModel->select_admin_per($creator_id);//查询这个用户是否存在
		if (!is_array($del_user_per)) {
			return '此用户不存在';
		}

		//当前用户的管理员列表模块级别
		$user_admin_level = $levelModel->select_lst_one($user_permission['admin_level_id']);
		//当前用户的管理员权限设定模块级别
		$user_permission_level = $levelModel->select_lst_one($user_permission['permission_level_id']);
		//当前要删除的用户的管理员列表模块级别
		$del_user_admin_level = $levelModel->select_lst_one($del_user_per['admin_level_id']);
		//当前要删除的用户的权限设定模块级别
		$del_user_permission_level = $levelModel->select_lst_one($del_user_per['permission_level_id']);
		//判断权限设定模块级别
		if ($user_permission_level >= $del_user_permission_level) {
			return '此用户的权限设定模块级别比您高或平级，您无法转移他创建的权限表到您名下！';
		}
		$sql = "select a.id as `id`,a.username as `username` from blog_admin as a inner join blog_permission as b inner join blog_level as c inner join blog_level as d on a.permission_id = b.id and b.admin_level_id = c.id and b.permission_level_id = d.id and c.sort > ".$user_admin_level." and c.sort <= ".$del_user_admin_level." and d.sort <= ".$del_user_permission_level.' and d.sort > '.$user_permission_level.' and a.id != '.$creator_id;
		$data = $this->query($sql);
		$data[] = ['id'=>$user_id,'username'=>'【您自己的名下】'];
		return $data;
	}

	//删除动作中，查找符合条件的转移级别表创建者
	public function select_level_move_admin($user_id,$user_permission,$move_admin_id,$move_admin_per)//当前用户id，当前用户权限表信息，当前所选中要转移权限创建者的用户id,当前将被转移的用户的权限表信息
	{
		$levelModel = model('Level');
		$user_level_sort = $levelModel->select_lst_one($user_permission['level_level_id']);
		$move_admin_level_per = $levelModel->select_lst_one($move_admin_per['level_level_id']);
		if ($user_level_sort >= $move_admin_level_per) {
			return '此用户的权限级别模块级别比您高或平级，您无法转移他创建的级别！';
		}
		$sql = "select a.id as `id`,a.username as `username` from blog_admin as a inner join blog_permission as b inner join blog_level as c on a.permission_id = b.id and b.level_level_id = c.id and c.sort > ".$user_level_sort." and c.sort <= ".$move_admin_level_per." AND b.level_see = 1 AND b.level_c = 1 AND a.id <> ".$move_admin_id;
		$data = $this->query($sql);
		$data[] = ['id'=>$user_id,'username'=>'【您自己的名下】'];
		return $data;
	}

	//插入一条数据到admin_lst中	hash类型
	private function redis_admin_lst_one($data)		// 新增的数据，数据中包含id
	{
		if ($this->redis_obj->exists('admin_lst')) {		//如果有这张表则直接插入一条数据
			$this->redis_obj->Hset('admin_lst',$data['id'],$data);
		}else{
			$this->redis_admin_lst_all();		//否则刷新整张表
		}
	}

	//插入一条数据到admin_id_zset中  
	private function redis_admin_id_zset_one($id)	// 新增数据的id
	{
		if ($this->redis_obj->exists('admin_id_zset')) {
			$this->redis_obj->Zadd('admin_id_zset',$id,$id);
		}else{
			$this->redis_admin_id_zset_all();
		}
	}

	//删除admin_lst中的一条数据  hash类型
	private function del_redis_admin_lst_one($admin_id)		//管理员id
	{
		if ($this->redis_obj->exists('admin_lst')) {
			$this->redis_obj->Hdel('admin_lst',$admin_id);
		}else{
			$this->redis_admin_lst_all();
		}
	}

	//删除admin_id_zset中的一条数据 zset类型
	private function del_redis_admin_id_zset_one($admin_id)		//管理员id
	{
		if ($this->redis_obj->exists('admin_id_zset')) {
			$this->redis_obj->Zrem('admin_id_zset',$admin_id);
		}else{
			$this->redis_admin_id_zset_all();
		}
	}

	//更新admin_lst中所有数据
	private function redis_admin_lst_all($re = false)
	{
		$res = $this->all();					
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('admin_lst',$list);
		if ($re == true) {
			return $res;
		}
	}

	//更新admin_id_zset中的所有数据
	private function redis_admin_id_zset_all()
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$res = $this->redis_obj->Zadd('admin_id_zset',$key['id'],$key['id']);
		}
	}

	//查找分页中相关的数据
	public function admin_page_lst($count,$data = null)	//$count 管理员的数量 $data get接收到的传参
	{
		$z_count = $this->redis_obj->Zcard('admin_id_zset');
		if ($count != $z_count) {
			$this->redis_admin_id_zset_all();
		}
		if ($data == null) {
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('admin_id_zset',$start,$end);
		}else{
			if (!isset($data['page'])) {
				$data['page'] = 1;
			}
			if (!isset($data['sort'])) {
				$data['sort'] = 'order';
			}
			$start = $data['page']*10-10;
			$end = $start+9;
			if ($data['sort'] == 'order') {
				$res = $this->redis_obj->Zrange('admin_id_zset',$start,$end);
			}elseif ($data['sort'] == 'sort') {
				$res = $this->redis_obj->Zrevrange('admin_id_zset',$start,$end);
			}
		}
		$list = $this->redis_obj->Hmget('admin_lst',$res);
		return $list;
	}

	//转移创建者综合方法
	/*
	* data:二维数组 
		第一层数组结构  下标是按照规则起的 看方法就知道了 
						其中下标为 move_admin_id是将要被转移的创建者id
		第二层数组结构  key 为 要转移的表id  value为将要转到的用户id
	* 思路：首先验证所有数据，再转移/修改
	*/
	public function move_creator_all($data,$user_permission,$user_id)	//表单提交过来的数据，当前用户权限表信息	当前用户id
	{
		$permissionModel = model('Permission');
		$levelModel = model('Level');

		$move_admin_per = $permissionModel->select_admin_per($data['move_admin_id']);		//被转移权限表创建者的管理员的权限信息
		$levels = $levelModel->select_lst();												//查询所有级别

		if ($levels[$user_permission['admin_level_id']]['sort'] >= $levels[$move_admin_per['admin_level_id']]['sort']) {
			return '您管理员列表模块级别过低或相同，无法进行此操作！';
		}

		//验证权限表模块
		if (isset($data['move_permission_id'])) {
			if ($levels[$user_permission['permission_level_id']]['sort'] >= $levels[$move_admin_per['permission_level_id']]['sort']) {
				return '您管理员权限设定模块级别过低相同！无法进行此操作！';
			}
			foreach ($data['move_permission_id'] as $key => $value) {
				if (!$permissionModel->where(['id'=>$key,'creator_id'=>$data['move_admin_id']])->field('id')->find()) {		//验证每个权限表的创建者是不是一个人or表单有没有篡改
					return '您的操作不合法！请重新操作！';
				}
			} 
		}

		//验证级别模块
		if(isset($data['move_level_id'])){
			if ($levels[$user_permission['level_level_id']]['sort'] >= $levels[$move_admin_per['level_level_id']]['sort']) {
				return '您权限级别模块级别过低相同！无法进行此操作！';
			}
			foreach ($data['move_level_id'] as $key => $value) {
				if (!$levelModel->where(['id'=>$key,'creator_id'=>$data['move_admin_id']])->field('id')->find()) {
					return '您的操作不合法！请重新操作！';
				}
				if ($value == $data['move_admin_id']) {
					return '操作失误！';
				}
				if ($move_user_per_id = $this->where(['id'=>$value])->value('permission_id')) {		//是否存在将要转移到的用户id
					$move_user_level_level_id = $permissionModel->where(['id'=>$move_user_per_id])->value('level_level_id');	//将要转移到的用户的级别模块级别id
					if ($value != $user_id) {
						if ($levels[$move_user_level_level_id]['sort'] >= $levels[$key]['sort']) {
							return '您的操作出现错误，请刷新页面后重新尝试';
						}
					}	
				}else{
					return '操作失误！请刷新页面后重新尝试';
				}
			}
		}


		if (isset($data['move_permission_id'])) {
			if ($res = $permissionModel->move_creator($data['move_permission_id'],$data['move_admin_id'],$user_permission)) {
				return $res;
			}
		}

		if (isset($data['move_level_id'])) {
			$levelModel->move_creator($data['move_level_id'],$data['move_admin_id']);
		}
	}
}
