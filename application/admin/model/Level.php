<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;

class Level extends Model
{	
	public $redis_obj;
	//模型初始化
	protected function initialize()
	{
		$this->redis_obj = new Redis();
	}
/**************************查询数据************************************/
	// 查询所有级别	redis中的level_lst 只有id name sort字段
	public function select_lst()
	{
		if ($res = $this->redis_obj->Hgetall('level_lst')) {
			return $res;
		}else{
			$res = $this->field('id,name,sort')->select();
			$adminModel = model('Admin');
			foreach ($res as $key) {
				$this->redis_obj->Hset('level_lst',$key['id'],$key);
			}
			return $res;
		}
	}

	//查询单个级别的sort，redis中的level_lst
	public function select_lst_one($id,$all = false)		//级别id,all如果为true则返回整条数据，否则返回sort
	{
		if ($res = $this->redis_obj->Hget('level_lst',$id)) {
			if ($all == true) {
				return $res;
			}else{
				return $res['sort'];
			}
		}else{
			$res = $this->column('id,name,sort');
			foreach ($res as $key) {
				$this->redis_obj->Hset('level_lst',$key['id'],$key);
			}
			if ($all == true) {
				return $res[$id];
			}else{
				return $res[$id]['sort'];
			}
		}
	}

	//在 level_lst_all 中查找单条数据
	public function select_lst_all_one($level_id)
	{
		if ($res = $this->redis_obj->Hget('level_lst_all',$level_id)) {
			return $res;
		}else{
			$this->redis_level_lst_all();
			return $this->redis_obj->Hget('level_lst_all',$level_id);
		}
	}

	//查找所有级别所有字段  包括创建者 修改者名字	redis中level_lst_all
	public function select_lst_all($re = false)		//re : 如果为true则返回数据长度 否则返回所有数据
	{
		if ($re == true) {
			if ($count = $this->redis_obj->Hlen('level_lst_all')) {
			 	return $count;
			 }else{
			 	return count($this->redis_level_lst_all(true));			 	
			 }
		}
		if ($res = $this->redis_obj->Hgetall('level_lst_all')) {
			return $res;
		}else{
			return $this->redis_level_lst_all(true);
		}
	}

	//查找redis中 level_creator_zset_?数据长度
	public function select_count_redis_level_creator_zset_one($creator_id)		//创建者id	
	{
		if ($count = $this->redis_obj->Zcard('level_creator_zset_'.$creator_id)) {
			return $count;
		}else{
			$this->redis_level_creator_zset($creator_id);
			return $this->redis_obj->Zcard('level_creator_zset_'.$creator_id);
		}
	}

	/*
	* 批量查找转移创建者符合条件的管理员(非动作)
	* data:一维数组，value为级别id
	* user_id:当前用户id
	* user_permission:当前用户权限表信息
	* 注意：此方法使用前已经经过了验证
	*/
	public function more_move_creator_data($data,$user_id,$user_permission)
	{
		$user_level_sort = $this->select_lst_one($user_permission['level_level_id']);		//当前用户级别模块等级sort
		$list = [];
		foreach ($data as $key => $value) {
			$now_level_sort = $this->select_lst_one($value);			//当前要转移的级别表sort
			$sql = 'SELECT a.id,a.username AS username FROM blog_admin AS a INNER JOIN blog_permission AS b INNER JOIN blog_level AS c INNER JOIN blog_level AS d ON a.permission_id = b.id AND b.level_level_id = c.id AND b.level_level_id = d.id AND c.id = d.id AND b.level_see = 1 AND b.level_u = 1 AND c.sort > '.$user_level_sort.' AND d.sort < '.$now_level_sort;
			$move_user = $this->query($sql);
			$move_user[] = ['id'=>$user_id,'username'=>'【您自己的名下】'];
			$level_lst = $this->select_lst_one($value,true);
			$list[$key]['level_id'] = $value;
			$list[$key]['level_name'] = $level_lst['name'];
			$list[$key]['move_user'] = $move_user;
		}
		return $list;
	}
/**************************逻辑入口************************************/
	//新增一条数据
	public function insert_one($data,$user_id)		//新增数据   当前用户id
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		$data['creator_id'] = $user_id;
		$data['create_time'] = time();
		$data['reviser_id'] = $user_id;
		$data['revise_time'] = time();
		if ($this->save($data)) {
			$data['id'] = $this->id;
			$this->redis_level_lst_one($data);
			$this->redis_level_lst_all_one($data);
			$this->redis_level_id_zset_one($this->id);
			$this->redis_level_sort_zset_one($data['sort'],$this->id);
			$this->redis_level_creator_zset_one($this->id,$user_id);
			$this->redis_level_creator_zset_user_sort_one($this->id,$data['sort'],$user_id);
		}else{
			return '新增等级失败！';
		}
	}

	//修改一条数据(普通)
	public function update_one($data,$user_id)		//将要修改的数据  当前用户id
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		$data['reviser_id'] = $user_id;
		$data['revise_time'] = time();
		if ($this->update($data)) {
			$data = $this->where(['id'=>$data['id']])->find();
			$this->redis_update_level_lst_one($data['id'],$data);
			$this->redis_update_level_lst_all_one($data['id'],$data);
			$this->redis_update_level_sort_zset_one($data['id'],$data['sort']);
			$this->redis_update_level_creator_zset_user_sort_one($data['id'],$data['sort'],$data['creator_id']);
		}else{
			return '编辑级别失败！请刷新页面后重试！';
		}
	}

	//修改并转移创建者（转移创建者）
	public function update_move_one($data,$move_creator_id)		//要更改成的数据  将要转移成的创建者id
	{
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		$data['creator_id'] = $move_creator_id;
		$data['reviser_id'] = $move_creator_id;
		$data['revise_time'] = time();
		$old_creator_id = $this->where(['id'=>$data['id']])->value('creator_id');		//未更改前的创建者id

		if ($this->update($data)) {
			$data = $this->where(['id'=>$data['id']])->find();
			//刷新redis中的数据
			$this->redis_update_level_lst_one($data['id'],$data);
			$this->redis_update_level_lst_all_one($data['id'],$data);
			$this->redis_update_level_sort_zset_one($data['id'],$data['sort']);
			$this->redis_update_level_creator_zset_user_sort_one($data['id'],$data['sort'],$data['creator_id']);
			$this->redis_level_creator_zset_one($data['id'],$move_creator_id);
			$this->redis_level_creator_zset_user_sort_one($data['id'],$data['sort'],$move_creator_id);
			//删除redis中相关数据
			$this->redis_delete_level_creator_zset_one($data['id'],$old_creator_id);
			$this->redis_delete_level_creator_zset_user_sort_one($data['id'],$old_creator_id);
		}else{
			return '编辑级别失败！请刷新页面后重试！';
		}
	}

	//删除一条数据
	public function delete_one($level_id)
	{
		$data = $this->where(['id'=>$level_id])->find();		//删除之前的数据
		if ($this->destroy($level_id)) {
			$this->redis_delete_level_lst_one($data['id']);
			$this->redis_delete_level_lst_all_one($data['id']);
			$this->redis_delete_level_id_zset_one($data['id']);
			$this->redis_delete_level_sort_zset($data['id']);
			$this->redis_delete_level_creator_zset_one($data['id'],$data['creator_id']);
			$this->redis_delete_level_creator_zset_user_sort_one($data['id'],$data['creator_id']);
		}else{
			return '删除失败！请刷新页面后重试';
		}
	}
	/*
	* 从admin来的请求 批量转移创建者 即所有数据都是一个用户创建的
	* data:一维数组 key为级别表id value为将要转移成创建者的id
	* move_admin_id: 将要被转移级别表创建者的管理员id
	* 注意：此方法使用前已经经过了验证
	*/
	public function move_creator($data,$move_admin_id)
	{
		$list = [];
		foreach ($data as $key => $value) {
			$list[$key]['id'] = $key;
			$list[$key]['creator_id'] = $value;
			$list[$key]['reviser_id'] = $value;
			$list[$key]['revise_time'] = time();
		}
		if ($this->saveAll($list)) {
			foreach ($data as $key => $value) {
				$res = $this->where(['id'=>$key])->field('sort')->find();
				
				$this->redis_update_level_lst_all_one($key);
				$this->redis_update_level_creator_zset_one($key,$value);
				$this->redis_update_level_creator_zset_user_sort_one($key,$res['sort'],$value);

				$this->redis_delete_level_creator_zset_one($key,$move_admin_id);
				$this->redis_delete_level_creator_zset_user_sort_one($key,$move_admin_id);
			}
		}
	}
	/*
	* 批量转移创建者 本模块请求来的
	* data:key为级别id，value为创建者id
	*/
	public function more_move_creator($data)
	{
		$list = [];
		$old_level_list = [];							//用来删除/更新与创建者相关的redis表
		foreach ($data as $key => $value) {
			$list[$key]['id'] = $key;
			$list[$key]['creator_id'] = $value;
			$list[$key]['reviser_id'] = $value;
			$list[$key]['revise_time'] = time();
			$res = $this->select_lst_all_one($key);		//未修改前的级别
			$old_level_list[$key]['sort'] = $res['sort'];
			$old_level_list[$key]['creator_id'] = $res['creator_id'];
		}
		if ($this->saveAll($list)) {
			foreach ($data as $key => $value) {
				$this->redis_delete_level_creator_zset_one($key,$old_level_list[$key]['creator_id']);
				$this->redis_delete_level_creator_zset_user_sort_one($key,$old_level_list[$key]['creator_id']);

				$this->redis_update_level_lst_all_one($key);
				$this->redis_level_creator_zset_one($key,$value);
				$this->redis_level_creator_zset_user_sort_one($key,$old_level_list[$key]['sort'],$value);
			}
		}else{
			return '转移失败，请刷新页面后重试';
		}
	}
	/*
	* 批量删除级别
	* data：一维数组 value为级别id
	*/
	public function more_delete($data)
	{	
		$old_creator = [];
		foreach ($data as $key => $value) {
			$old_creator[$key] = $this->where(['id'=>$value])->value('creator_id');
		}
		if ($this->destroy($data)) {
			foreach ($data as $key => $value) {
				$this->redis_delete_level_lst_one($value);
				$this->redis_delete_level_lst_all_one($value);
				$this->redis_delete_level_id_zset_one($value);
				$this->redis_delete_level_sort_zset($value);
				$this->redis_delete_level_creator_zset_one($value,$old_creator[$key]);
				$this->redis_delete_level_creator_zset_user_sort_one($value,$old_creator[$key]);
			}
		}else{
			return '批量删除失败，请刷新重新尝试';
		}
	}
/**************************新增redis数据************************************/

	// 新增一条数据到 level_lst表中
	public function redis_level_lst_one($data)		//新增数据 包含id		
	{
		if ($this->redis_obj->exists('level_lst')) {
			$list['id'] = $data['id'];
			$list['name'] = $data['name'];
			$list['sort'] = $data['sort'];
			$this->redis_obj->Hset('level_lst',$list['id'],$list);
		}else{
			$this->redis_level_lst_all();
		}
	}

	// 新增一条数据到 level_lst_all表中
	public function redis_level_lst_all_one($data)	//新增数据 包含id
	{
		if ($this->redis_obj->exists('level_lst_all')) {
			$adminModel = model('Admin');
			$data['creator_name'] = $adminModel->where(['id'=>$data['creator_id']])->value('username');
			$data['reviser_name'] = $adminModel->where(['id'=>$data['reviser_id']])->value('username');
			$this->redis_obj->Hset('level_lst_all',$data['id'],$data);
		}else{
			$this->redis_level_lst();
		}
	}

	//新增一条数据到 level_id_zset中
	public function redis_level_id_zset_one($level_id)	//级别id
	{
		if ($this->redis_obj->exists('level_id_zset')) {
			$this->redis_obj->Zadd('level_id_zset',$level_id,$level_id);
		}else{
			$this->redis_level_id_zset();
		}
	}

	//新增一条数据到 level_sort_zset中
	public function redis_level_sort_zset_one($sort,$level_id)	//级别等级（排序）
	{
		if ($this->redis_obj->exists('level_sort_zset')) {
			$this->redis_obj->Zadd('level_sort_zset',$sort,$level_id);
		}else{
			$this->redis_level_sort_zset();
		}
	}

	//新增一条数据到 level_creator_zset_?中
	public function redis_level_creator_zset_one($level_id,$user_id)	//级别id   创建者id
	{
		if ($this->redis_obj->exists('level_creator_zset_'.$user_id)) {
			$this->redis_obj->Zadd('level_creator_zset_'.$user_id,$level_id,$level_id);
		}else{
			$this->redis_level_creator_zset($user_id);
		}
	}

	//新增一条数据到 level_creator_zset_?_sort中 
	public function redis_level_creator_zset_user_sort_one($level_id,$sort,$creator_id)	//级别id	级别等级（排序）  创建者id
	{
		$redis_name = 'level_creator_zset_'.$creator_id.'_sort';
		if ($this->redis_obj->exists($redis_name)) {
			$this->redis_obj->Zadd($redis_name,$sort,$level_id);
		}else{
			$this->redis_level_creator_user_zset($creator_id);
		}
	}
/**************************刷新redis数据************************************/

	//刷新一条数据在 redis中的level_lst
	public function redis_update_level_lst_one($level_id,$level_list = null)	//level_id:level表id，level_list：要刷新的数据
	{
		if ($this->redis_obj->exists('level_lst')) {
			if ($level_list) {
				$res['id'] = $level_list['id'];
				$res['name'] = $level_list['name'];
				$res['sort'] = $level_list['sort'];
				$this->redis_obj->Hset('level_lst',$res['id'],$res);
			}else{
				$res = $this->where(['id'=>$level_id])->field('id,name,sort')->find();
				$this->redis_obj->Hset('level_lst',$res['id'],$res);
			}
		}else{
			$this->redis_level_lst();
		}
	}

	//刷新一条数据在 redis中level_lst_all
	public function redis_update_level_lst_all_one($level_id,$level_list = null)	//level_id:level表id，level_list 要刷新的数据
	{
		if ($this->redis_obj->exists('level_lst_all')) {
			$adminModel = model('Admin');
			if ($level_list) {
				$level_list['creator_name'] = $adminModel->where(['id'=>$level_list['creator_id']])->value('username');
				$level_list['reviser_name'] = $adminModel->where(['id'=>$level_list['reviser_id']])->value('username');
				$this->redis_obj->Hset('level_lst_all',$level_list['id'],$level_list);
			}else{
				$data = $this->where(['id'=>$level_id])->find();
				$data['creator_name'] = $adminModel->where(['id'=>$data['creator_id']])->value('username');
				$data['reviser_name'] = $adminModel->where(['id'=>$data['reviser_id']])->value('username');
				$this->redis_obj->Hset('level_lst_all',$data['id'],$data);
			}
		}else{
			$this->redis_level_lst_all();
		}
	}

	//刷新一条数据到 redis中level_sort_zset    zset类型
	public function redis_update_level_sort_zset_one($level_id,$sort)
	{
		if ($this->redis_obj->exists('level_sort_zset')) {
			$this->redis_obj->Zadd('level_sort_zset',$sort,$level_id);
		}else{
			$this->redis_level_sort_zset();
		}
	}

	//刷新一条数据到 redis中level_creator_zset_?
	public function redis_update_level_creator_zset_one($level_id,$creator_id)
	{
		$redis_name = 'level_creator_zset_'.$creator_id;
		if ($this->redis_obj->exists($redis_name)) {
			$this->redis_obj->Zadd($redis_name,$level_id,$level_id);
		}else{
			$this->redis_level_creator_zset($creator_id);
		}
	}

	//刷新一条数据到 redis中level_creator_zset_?_sort
	public function redis_update_level_creator_zset_user_sort_one($level_id,$sort,$creator_id)	//级别id	级别等级（排序） 要刷新的创建者id
	{
		$redis_name = 'level_creator_zset_'.$creator_id.'_sort';
		if ($this->redis_obj->exists($redis_name)) {
			$this->redis_obj->Zadd($redis_name,$sort,$level_id);
		}else{
			$this->redis_level_creator_user_zset($creator_id);
		}
	}
/**************************删除redis数据************************************/

	//删除 redis中 level_lst中的一条数据
	public function redis_delete_level_lst_one($level_id)	//级别id
	{
		if ($this->redis_obj->exists('level_lst')) {
			$this->redis_obj->Hdel('level_lst',$level_id);
		}
	}

	//删除 redis中 level_lst_all中的一条数据
	public function redis_delete_level_lst_all_one($level_id)	//级别id
	{
		if ($this->redis_obj->exists('level_lst_all')) {
			$this->redis_obj->Hdel('level_lst_all',$level_id);
		}
	}

	//删除 redis中 level_id_zset中的一条是数据
	public function redis_delete_level_id_zset_one($level_id)	//级别id
	{
		if ($this->redis_obj->exists('level_id_zset')) {
			$this->redis_obj->Zrem('level_id_zset',$level_id);
		}
	}

	//删除 redis中 level_sort_zset中的一条数据
	public function redis_delete_level_sort_zset($level_id)
	{
		if ($this->redis_obj->exists('level_sort_zset')) {
			$this->redis_obj->Zrem('level_sort_zset',$level_id);
		}
	}

	//删除 redis中 level_creator_zset_?中的一条数据
	public function redis_delete_level_creator_zset_one($level_id,$creator_id)		//级别id	创建者id
	{
		$redis_name = 'level_creator_zset_'.$creator_id;
		if ($this->redis_obj->exists($redis_name)) {
			$this->redis_obj->Zrem($redis_name,$level_id);
		}
	}

	//删除 redis中 level_creator_zset_?_sort的一条数据
	public function redis_delete_level_creator_zset_user_sort_one($level_id,$creator_id)	//级别id	创建者id
	{
		$redis_name = 'level_creator_zset_'.$creator_id.'_sort';
		if ($this->redis_obj->exists($redis_name)) {
			$this->redis_obj->Zrem($redis_name,$level_id);
		}
	}
/**************************刷新所有redis数据************************************/
	//刷新redis中 level_lst所有数据  hash类型
	private function redis_level_lst($re = false)	//如果re为true则返回所有level_lst数据
	{
		$res = $this->field('id,name,sort')->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
			$this->redis_obj->Hmset('level_lst',$list);
		if ($re == true) {
			return $this->redis_obj->Hgetall('level_lst');
		}
	}

	//刷新redis中 level_lst_all所有数据 hash类型
	private function redis_level_lst_all($re = false)
	{
		$res = $this->field('id,name,desc,sort,creator_id,create_time,reviser_id,revise_time')->select();
		$list = [];
		$adminModel = model('Admin');
		foreach ($res as $key) {
			$key['creator_name'] = $adminModel->where(['id'=>$key['creator_id']])->value('username');
			$key['reviser_name'] = $adminModel->where(['id'=>$key['reviser_id']])->value('username');
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('level_lst_all',$list);
		if ($re == true) {
			return $res;
		}
	}

	//刷新redis中 level_id_zset表所有数据	zset类型
	private function redis_level_id_zset()
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('level_id_zset',$key['id'],$key['id']);
		}
	}

	//刷新redis中 level_sort_zset表所有数据  zset类型
	private function redis_level_sort_zset()
	{
		$res = $this->field('sort,id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('level_sort_zset',$key['sort'],$key['id']);
		}
	}

	//刷新redis中 level_creator_zset_?的指定用户数据
	private function redis_level_creator_zset($user_id)		//要刷新的用户id
	{
		$res = $this->where(['creator_id'=>$user_id])->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('level_creator_zset_'.$user_id,$key['id'],$key['id']);
		}
	}

	//刷新redis中 level_creator_zset_?_sort中的数据
	private function redis_level_creator_user_zset($user_id)		//要刷新的用户id
	{
		$res = $this->where(['creator_id'=>$user_id])->field('id,sort')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('level_creator_zset_'.$user_id.'_sort',$key['sort'],$key['id']);
		}
	}
/**************************验证部分************************************/

	//验证级别等级是否合法
	public function check_level($user_level_id,$sort)		//当前用户级别模块的级别id，将要写入表的等级（排序）
	{
		$user_sort = $this->where(['id'=>$user_level_id])->value('sort');
		if ($user_sort >= $sort) {
			return '您所填写的级别等级不合法，必须填写比自己权限级别模块可操作级别低的级别!';
		}
	}

	/*
	* 验证此级别表的创建者级别 和 当前操作者级别	
	* data：当前要更改成的id,也可以为数组，为数组则表示此级别要更改成的数据 含有id
	* user_id：当前用户id，
	* user_permission：当前操作者权限表信息
	* $tip:分支操作 例如tip1
	*/
	public function check_creator_level($data,$user_id,$user_permission,$tip = null)	//此方法需要不断更新
	{
		if (is_array($data)) {
			$now_level = $this->select_lst_all_one($data['id']);		//当前操作的级别表信息
		}else{
			$now_level = $this->select_lst_all_one($data);				//不是数组 则为id
		}
		$permissionModel = model('Permission');
		if($now_level['creator_id'] != $user_id){	//如果不是自己创建的级别
			//判断创建者级别
			$now_level_creator_per = $permissionModel->select_admin_per($now_level['creator_id']);
			$now_level_sort = $this->select_lst_one($now_level_creator_per['level_level_id']);
			$user_level_sort = $this->select_lst_one($user_permission['level_level_id']);
			if ($now_level_sort <= $user_level_sort) {
				return '2';		//创建者级别比当前操作者高
			}
		}
		//判断自己权限表是否存在此级别
		if ($user_permission['admin_level_id'] == $now_level['id'] || $user_permission['permission_level_id'] == $now_level['id'] || $user_permission['level_level_id'] == $now_level{'id'}) {
			return '1';		//自己的权限表中存在此级别
		}
		if ($tip != 'tip1') {
			//判断是否有管理员正在使用此级别
			if ($permissionModel->where('admin_level_id = '.$now_level['id'].' or permission_level_id = '.$now_level['id'].' or level_level_id = '.$now_level['id'])->field('id')->find()) {
				return '3';		//有权限表正在使用此级别
			}
		}
		if (is_array($data)) {		//这里表示为更改表单时的验证
			$user_level = $this->select_lst_all_one($user_permission['level_level_id']);	//当前用户的级别模块 由级别id查来的级别表信息
			if ($data['sort'] <= $user_level['sort']) {
				return '4';		//用户修改的sort超过了自己权限中的sort
			}
			$now_level_creator_per = $permissionModel->select_admin_per($now_level['creator_id']);
			$now_level_sort = $this->select_lst_one($now_level_creator_per['level_level_id']);
			if ($data['sort'] <= $now_level_sort) {
				return '5';		//用户修改的sort超过或等于此条等级创建者的等级
			}
		}
	}

	/*
	批量验证用户是否可对这些级别进行写操作
	一维数组 value为级别id,
	当前用户id，
	当前用户权限表信息
	如果del为true则验证是否有权限正在使用当前验证的级别
	*/
	public function more_check($data,$user_id,$user_permission,$del = false)
	{
		$permissionModel = model('Permission');
		$user_level_sort = $this->select_lst_one($user_permission['level_level_id']);	//当前用户的级别模块级别等级
		foreach ($data as $key => $value) {
			if ($value == $user_permission['admin_level_id'] || $value == $user_permission['permission_level_id'] || $value == $user_permission['level_level_id']) {
				return '1';							//自己的级别表含有此等级
			}
			if ($del == true) {
				if ($permissionModel->where('(admin_level_id ='.$value.' or permission_level_id = '.$value.' or level_level_id = '.$value.') and id <> '.$user_permission['id'])->field('id')->find()) {
					return '6';						//除自己权限表外，其他权限表有正在使用当前将要进行写操作的级别
				}
			}
			if ($res = $this->select_lst_all_one($value)) {		//如果有这个级别的信息
				if ($res['creator_id'] != $user_id) {			//如果不是自己创建的  则对比当前用户级别模块的级别等级和将要转移的用户的级别等级
					$move_admin_per = $permissionModel->select_admin_per($res['creator_id']);		//查找被转移的级别德尔创建者的权限表信息
					if (!is_array($move_admin_per)) {
						return '2';					//没有被转移的级别的创建者的权限
					}
					$move_level_sort = $this->select_lst_one($move_admin_per['level_level_id']);
					if ($move_level_sort <= $user_level_sort) {
						return '3';					//创建者等级比当前用户级别高
					}
					if ($res['sort'] <= $user_level_sort) {
						return '4';					//将要转移的级别中的sort比自己级别sort等级高
					}
				}else{
					if ($res['sort'] < $user_level_sort) {
						return '4';					//将要转移的级别中的sort比自己级别sort等级高
					}
				}
			}else{
				return '5';							//某个表信息不存在
			}
		}
	}

	/*
	* 批量验证转移到的创建者是否合法
	* 将要转移到
	* data：key为级别id ，value为将要转移到的创建者
	*/
	public function more_check_move($data)
	{
		$permissionModel = model('Permission');
		foreach ($data as $key => $value) {
			$value_per = $permissionModel->select_admin_per($value);
			if ($value_per['level_c'] == 0) {
				return '当前想要转移到的部分管理员没有级别模块的新增权限';				
			}
			$value_level_sort = $this->select_lst_one($value_per['level_level_id']);
			$key_level_sort = $this->select_lst_one($key);
			if ($value_level_sort >= $key_level_sort) {
				return '当前想要转移到的部分管理员级别模块的级别没有被转移级别大';
			}
		}
	}	
/**************************查看页面查出的数据算法**************************/
	//查找出列表页所需的相应数据
	public function pjax_lst($count,$input = null)	//count为数据总长度，input为控制器接收到的传参
	{
		if ($input == null) {		//如果input为空	既刚点击来
			$level_id_zset_count = $this->redis_obj->Zcard('level_id_zset');
			if ($count != $level_id_zset_count) {
				$this->redis_level_id_zset();
			}
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('level_id_zset',$start,$end);
		}else{
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end   = $start+9;
			if (isset($input['creator'])) {							//如果没有创建者 此为创建者id
				if (isset($input['sort'])) {						//如果有排序
					if (!$this->redis_obj->Zcard('level_creator_zset_'.$input['creator'])) {
						$this->redis_level_creator_zset($input['creator']);
					}
					if (!$this->redis_obj->Zcard('level_creator_zset_'.$input['creator'].'_sort')) {
						$this->redis_level_creator_user_zset($input['creator']);
					}
					if ($input['sort'] == 'id_order') {				//如果是按id正序排列
						$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'],$start,$end);
						if (!$res) {
							$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'],0,9);
						}
					}else if ($input['sort'] == 'id_sort') {			//如果是按id倒序排列
						$res = $this->redis_obj->Zrevrange('level_creator_zset_'.$input['creator'],$start,$end);
						if (!$res) {
							$res = $this->redis_obj->Zrevrange('level_creator_zset_'.$input['creator'],0,9);
						}
					}else if ($input['sort'] == 'sort_order') {			//如果是按级别等级正序排列
						$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'].'_sort',$start,$end);
						if (!$res) {
							$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'].'_sort',0,9);
						}
					}else if ($input['sort'] == 'sort_sort') {			//如果是按级别等级倒序排列
						$res = $this->redis_obj->Zrevrange('level_creator_zset_'.$input['creator'].'_sort',$start,$end);
						if (!$res) {
							$res =  $this->redis_obj->Zrevrange('level_creator_zset_'.$input['creator'].'_sort',0,9);
						}
					}		
				}else{												//如果没有排序但有创建者
					$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'],$start,$end);
					if (!$res) {
						$res = $this->redis_obj->Zrange('level_creator_zset_'.$input['creator'],0,9);
					}
				}
			}else{													//如果没有创建者
				if (isset($input['sort'])) {						//如果有排序
					if (!$this->redis_obj->Zcard('level_id_zset')) {
						$this->redis_level_id_zset();
					}
					if (!$this->redis_obj->Zcard('level_sort_zset')) {
						$this->redis_level_sort_zset();
					}
					if ($input['sort'] == 'id_order') {				//如果是按id正序排列
						$res = $this->redis_obj->Zrange('level_id_zset',$start,$end);
					}else if($input['sort'] == 'id_sort'){			//如果是按照id倒序排列
						$res = $this->redis_obj->Zrevrange('level_id_zset',$start,$end);
					}else if($input['sort'] == 'sort_order'){		//如果是按照级别等级正序排列
						$res = $this->redis_obj->Zrange('level_sort_zset',$start,$end);
					}else if($input['sort'] == 'sort_sort'){		//如果是按级别等级倒序排列
						$res = $this->redis_obj->Zrevrange('level_sort_zset',$start,$end);
					}
				}else{												//如果什么都没有或只有页数
					$res = $this->redis_obj->Zrange('level_id_zset',$start,$end);
				}
			}
		}
		$list = $this->redis_obj->Hmget('level_lst_all',$res);
		return $list;
	}
//杂项
	//查找级别表所有创建者 返回id，name
	public function get_creator()
	{
		$sql = 'SELECT DISTINCT(creator_id) FROM blog_level';
		$res = $this->query($sql);
		foreach ($res as $key) {
			$data[] = $key['creator_id'];
		}

		$adminModel = model('Admin');
		$where = '';
		foreach ($data as $key => $value) {
			$where .= 'or id = '.$value.' ';
		}
		$where = substr($where,2);
		return $adminModel->where($where)->field('id,username')->select();
	}
}