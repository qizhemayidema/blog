<?php 
namespace app\admin\model;

use think\Model;
use think\cache\driver\Redis;
use think\Session;

class Permission extends Model
{

	public $redis_obj;		//redis实例化对象

	//模型初始化方法
	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

	//查找所有权限 permission_lst
	public function select_lst($re = false)		//如果为true则返回所有peremission_lst信息否则返回长度
	{	
		$res = $this->redis_obj->Hlen('permission_lst');
		if ($res) {
			if ($re == true) {
				return $this->redis_obj->Hgetall('permission_lst');
			}
			return $res;		//返回长度
		}else{
			//更新redis并返回数据
			$lst = $this->redis_permission_lst(true);
			if ($re == true) {
				return $lst;
			}
			return count($lst);		//返回长度
		}
	}
	//查找单条权限信息 permission_lst
	public function select_lst_one($id)		//权限表id
	{
		if ($res = $this->redis_obj->Hget('permission_lst',$id)) {
			return $res;
		}else{
			return $this->redis_permission_lst_one($id,true);
		}
	}
	//查找redis中permission_creator_zset_?的长度
	public function select_count_redis_permission_creator_zset_one($user_id)	//用户id
	{
		if ($count = $this->redis_obj->Zcard('permission_creator_zset_'.$user_id)) {
			return $count;
		}else{
			$this->redis_permission_creator_zset_one($user_id);
			return $this->redis_obj->Zcard('permission_creator_zset_'.$user_id);
		}
	}

	//查询一条数据 permission_lst_all
	public function selectOne($id)  //此传参为 权限表id
	{
		if ($res = $this->redis_obj->Hget('permission_lst_all',$id)) {
			return $res;
		}else{
			//更新此id数据并返回此id数据
			$res = $this->redis_permission_lst_all_one($id,true);
			if (!$res) {
				return false;
			}
			return $res;
		}
	}

	//查找某个用户的权限表信息
	public function select_admin_per($user_id)	//用户id
	{
		$adminModel = model('Admin');
		if ($res = $adminModel->admin_lst_one($user_id)) {
			if ($res == '1') {
				return '没有此管理员的信息';
			}
			$per = $this->selectOne($res['permission_id']);
			if ($per) {
				return $per;
			}else{
				return $this->redis_permission_lst_all_one($res['permission_id'],true);
			}
		}
	}

	/*
	* 新增一条数据
	* data 为传送过来将要插入的数据
	* user_id 为当前新增该表的用户id
	*/
	public function insertOne($data,$user_id)
	{
		unset($data['__token__']);
		$data['creat_time'] = time();
		$data['creator_id'] = $user_id;
		$data['revise_time'] = time();
		$data['reviser_id'] = $user_id;
		//如果存储进去
		if ($this->save($data)) {
			//新增数据
			$this->redis_permission_lst_one($this->id);		//传入当前新增的权限表的id
			$this->redis_permission_lst_all_one($this->id); //传入当前新增的权限表的id
			$this->redis_permission_id_zset_one($this->id); //传入当前新增的权限表的id
			$this->redis_permission_creator_zset_one($user_id,$this->id);	//当前用户id，当前权限表id
			return 2;		//插入成功
		}else{
			return 3;		//插入失败
		}
	}

	//修改一条数据
	public function updateOne($data,$user_id)	//data为将要修改的权限表的数据 user_id为当前操作的用户id 
	{
		//判断权限是否存在 不存在则为0
		if (!isset($data['permission_see'])) {$data['permission_see'] = 0;}
		if (!isset($data['permission_c'])) {$data['permission_c'] = 0;}
		if (!isset($data['permission_u'])) {$data['permission_u'] = 0;}
		if (!isset($data['permission_d'])) {$data['permission_d'] = 0;}
		if (!isset($data['admin_see'])) {$data['admin_see'] = 0;}
		if (!isset($data['admin_c'])) {$data['admin_c'] = 0;}
		if (!isset($data['admin_u'])) {$data['admin_u'] = 0;}
		if (!isset($data['admin_d'])) {$data['admin_d'] = 0;}
		if (!isset($data['level_see'])) {$data['level_see'] = 0;}
		if (!isset($data['level_c'])) {$data['level_c'] = 0;}
		if (!isset($data['level_u'])) {$data['level_u'] = 0;}
		if (!isset($data['level_d'])) {$data['level_d'] = 0;}
		if (!isset($data['link_see'])) {$data['link_see'] = 0;}
		if (!isset($data['link_c'])) {$data['link_c'] = 0;}
		if (!isset($data['link_u'])) {$data['link_u'] = 0;}
		if (!isset($data['link_d'])) {$data['link_d'] = 0;}
		if (!isset($data['notice_see'])) {$data['notice_see'] = 0;}
		if (!isset($data['notice_c'])) {$data['notice_c'] = 0;}
		if (!isset($data['notice_u'])) {$data['notice_u'] = 0;}
		if (!isset($data['notice_d'])) {$data['notice_d'] = 0;}
		if (!isset($data['column_see'])) {$data['column_see'] = 0;}
		if (!isset($data['column_c'])) {$data['column_c'] = 0;}
		if (!isset($data['column_u'])) {$data['column_u'] = 0;}
		if (!isset($data['column_d'])) {$data['column_d'] = 0;}
		if (!isset($data['article_see'])) {$data['article_see'] = 0;}
		if (!isset($data['article_c'])) {$data['article_c'] = 0;}
		if (!isset($data['article_u'])) {$data['article_u'] = 0;}
		if (!isset($data['article_d'])) {$data['article_d'] = 0;}
		if (!isset($data['sentence_see'])) {$data['sentence_see'] = 0;}
		if (!isset($data['sentence_c'])) {$data['sentence_c'] = 0;}
		if (!isset($data['sentence_u'])) {$data['sentence_u'] = 0;}
		if (!isset($data['sentence_d'])) {$data['sentence_d'] = 0;}
		if (!isset($data['tag_see'])) {$data['tag_see'] = 0;}
		if (!isset($data['tag_c'])) {$data['tag_c'] = 0;}
		if (!isset($data['tag_u'])) {$data['tag_u'] = 0;}
		if (!isset($data['tag_d'])) {$data['tag_d'] = 0;}
		if (!isset($data['comment_see'])) {$data['comment_see'] = 0;}
		if (!isset($data['comment_u'])) {$data['comment_u'] = 0;}
		if (!isset($data['visit_see'])) {$data['visit_see'] = 0;}
		if (!isset($data['click_see'])) {$data['click_see'] = 0;}
		unset($data['__token__']);
		$data['revise_time'] = time();
		$data['reviser_id'] = $user_id;
		//如果修改成功
		if ($this->update($data)){
			//更新数据
			$this->redis_permission_lst_one($data['id']);
			$this->redis_permission_lst_all_one($data['id']);
			return true;
		}else{
			return false;
		}
	}

	//删除一条数据
	public function deleteOne($id,$creator_id)	//$id 为权限表id   $creator_id创建者id
	{
		if ($this->destroy($id)) {
			$this->del_redis_permission_lst_one($id);			
			$this->del_redis_permission_lst_all_one($id);
			$this->del_redis_permission_id_zset_one($id);
			$this->del_redis_permission_creator_zset_one($id,$creator_id);
			return true;
		}else{
			return false;
		}
	}
	
	/*
	* 验证管理员权限设定模块的级别
	* 与创建者的级别做对比
	* 如果级别没有创建者高 或平级 没有操作权限
	*/
	public function permission_check_level($change_per_id,$user_permission,$user_id) //change_per_id : 将要操作的权限表id,user_permission:当前用户表信息,user_id:当前用户id
	{
		if ($change_per_id && $user_permission) {
			$levelModel = model('Level');
			//当前该用户级别
			$now_level = $levelModel->select_lst_one($user_permission['permission_level_id']);
			//当前用户权限表id
			$user_permission_id = $user_permission['id'];

			//查找创建者的用户级别
			$sql = 'SELECT c.sort AS sort,b.id AS admin_id FROM blog_permission AS a INNER JOIN blog_admin AS b INNER JOIN blog_level AS c INNER JOIN blog_permission AS d ON a.id = '.$change_per_id.' AND a.creator_id = b.id AND b.permission_id = d.id AND d.permission_level_id = c.id ';
			$res = $this->query($sql);
			//创建者用户级别
			$creator_level = $res[0]['sort'];
			//创建者id
			$creator_id = $res[0]['admin_id'];

			//判断是否有权限操作
			if ($change_per_id == $user_permission_id) {
				return '1';	//自己的权限表
			}
			if($creator_id == $user_id){	//如果是自己创建的表		开发完后需要删除掉
				return '4';
			}
			if ($now_level >= $creator_level ) {
				return '2';	//没有权限
			}
		}else{
			return '3';	//操作失误
		}
	}

	/*
	* 验证修改权限的下拉框
	* data :  管理员权限设定中提交的数据
	* $user_id为当前用户id
	* $user_permission为当前用户的权限表信息
	* 验证所有模块可操作级别是否合法
	* 规则：
	* 	 1的级别最高 不能设置该用户级别之上的级别 
	*/
	public function change_level_check($data,$user_id,$user_permission)
	{
		//获取所有权限
		$levelModel = model('Level');
		$level_all = $levelModel->select_lst();	//redis中查找

		//admin模块将要修改的权限级别
		$admin_level_sort = $level_all[$data['admin_level_id']]['sort'];
		//用户admin模块权限级别
		$user_admin_level_sort = $level_all[$user_permission['admin_level_id']]['sort'];

		//permission模块将要修改成的权限级别
		$permission_level_sort = $level_all[$data['permission_level_id']]['sort'];
		//用户的permission模块权限级别
		$user_permission_level_sort = $level_all[$user_permission['permission_level_id']]['sort'];
		//level模块将要修改成的权限级别
		$level_level_sort = $level_all[$data['level_level_id']]['sort'];
		//用户的level模块权限级别
		$user_level_level_sort = $level_all[$user_permission['level_level_id']]['sort'];

		if ($admin_level_sort <= $user_admin_level_sort) {
			return '【管理员列表模块】的可操作级别设置非法';
		}
		if ($permission_level_sort <= $user_permission_level_sort) {
			return '【管理员权限设定模块】的可操作级别设置非法！'; //permission权限设置非法
		}
		if ($level_level_sort <= $user_permission_level_sort) {
			return '【权限级别模块】的可操作级别设置非法！';
		}
		//编辑表单时验证，意思是如果数组中有id，那就表名此表已存在
		//提交数据与创建者级别作比对
		if (isset($data['id'])) {
			$res = $this->selectOne($data['id']);	//这是当前操作的表信息
			$res = $this->select_admin_per($res['creator_id']);	//此表创建者权限信息
			$creator_admin_level_sort = $level_all[$res['admin_level_id']]['sort'];
			$creator_permission_level_sort = $level_all[$res['permission_level_id']]['sort'];
			$creator_level_level_sort = $level_all[$res['level_level_id']]['sort'];
			if ($creator_admin_level_sort >= $admin_level_sort) {
				return 'tip您将要改变的部分模块级别比此权限的创建者级别高或相同，如想修改此条权限，则此条权限的创建者会自动转移到您的名下，您确定吗？';
			}
			if ($creator_permission_level_sort >= $permission_level_sort) {
				return 'tip您将要改变的部分模块级别比此权限的创建者级别高或相同，如想修改此条权限，则此条权限的创建者会自动转移到您的名下，您确定吗？';
			}
			if ($creator_level_level_sort >= $level_level_sort) {
				return 'tip您将要改变的部分模块级别比此权限的创建者级别高或相同，如想修改此条权限，则此条权限的创建者会自动转移到您的名下，您确定吗？';
			}

		}
	}
	/*
	* 私有化方法
	* 用于更新redis中permission_lst的 所有数据
	* $re :  如果需要返回结果则为true  不需要默认为false
	*/
	private function redis_permission_lst($re = false)
	{
		//查找所有数据存入redis
		$res = $this->alias('a')
			->join('blog_admin b','a.creator_id=b.id')
			->join('blog_admin c','a.reviser_id=c.id')
			->field('a.id,a.name,b.username creator,a.creat_time,c.username reviser,a.revise_time')
			->select();
		$list = [];
			foreach ($res as $key) {
				$list[$key['id']] = json_encode($key);
			}
			$this->redis_obj->Hmset('permission_lst',$list);
		if ($re == true) {
			return $res;
		}
	}
	/*
	* 用于更新permission_lst中的一条数据
	* $id为需要更新的permission表的id
	* $re 默认为false 如果为true则返回此条更新数据的信息
	*/
	public function redis_permission_lst_one($id,$re = false)
	{
		$res = $this->alias('a')
			->join('blog_admin b','a.creator_id=b.id and a.id='.$id)
			->join('blog_admin c','a.reviser_id=c.id')
			->field('a.id,a.name,b.username creator,a.creat_time,c.username reviser,a.revise_time')
			->find();

		if (!$this->redis_obj->Hset('permission_lst',$id,$res)) {
			$this->redis_permission_lst();
			$this->redis_obj->Hset('permission_lst',$id,$res);
		}
		if ($re == true) {
			return $res;
		}
	}

	/*
	* 私有方法
	* 用于删除一条redis中permission_lst的单条数据 
	*/
	private function del_redis_permission_lst_one($id)	//权限表id
	{
		if ($this->redis_obj->Hexists('permission_lst',$id)) {
			$this->redis_obj->Hdel('permission_lst',$id);
		}
	}

	/*
	* 用于更新redis中permission_lst_all一条数据
	* $id :  为当前更新的权限表id
	* $re :  如果需要返回结果则为true  不需要默认为false
	*/
	public function redis_permission_lst_all_one($id,$re = false)
	{
		$res = $this->where(['id'=>$id])->find();
		//查找该数据存入redis
		$this->redis_obj->Hset('permission_lst_all',$res['id'],$res);
		if ($re == true) {
			return $res;
		}
	}

	//根据权限表id删除permission_lst_all中的数据
	private function del_redis_permission_lst_all_one($id)	//权限表id
	{
		if ($this->redis_obj->Hexists('permission_lst_all',$id)) {
			$this->redis_obj->Hdel('permission_lst_all',$id);
		}
	}

	//把权限表id一条存入permission_id_zset中
	private function redis_permission_id_zset_one($id)	//权限表id
	{
		$this->redis_obj->Zadd('permission_id_zset',$id,$id);
	}

	//更新permission_id_zset中的所有数据
	public function redis_permission_id_zset_all()
	{
		$id = $this->field('id')->select();
		foreach ($id as $key) {
			$this->redis_obj->Zadd('permission_id_zset',$key['id'],$key['id']);
		}
	}

	//根据权限表id删除permission_id_zset中的数据
	private function del_redis_permission_id_zset_one($id)	//权限表id
	{
		$this->redis_obj->Zrem('permission_id_zset',$id);

	}

	//根据权限表id和用户id插入或刷新permission_creator_zset_?表
	//如果$id没有传参 则直接刷新permission_creator_zset_?表
	private function redis_permission_creator_zset_one($user_id,$id = null)	//将要增加数据的用户id  权限表id 
	{
		$zset_name = 'permission_creator_zset_'.$user_id;

		//更新所有
		if ($id == null) {
			$res = $this->where('creator_id='.$user_id)->field('id')->select();
			foreach ($res as $key) {
				$this->redis_obj->Zadd($zset_name,$key['id'],$key['id']);
			}
			return ;
		}
		//插入一条
		$res = $this->redis_obj->exists($zset_name);
		if ($res) {
			$this->redis_obj->Zadd($zset_name,$id,$id);
		}else{
			$res = $this->where('creator_id='.$user_id)->field('id')->select();
			foreach ($res as $key) {
				$this->redis_obj->Zadd($zset_name,$key['id'],$key['id']);
			}
		}
	}

	//删除permission_creator_zset_?表中的一条权限表id  
	private function del_redis_permission_creator_zset_one($id,$creator_id)	//权限表id  创建者id  
	{
		$zset_name = 'permission_creator_zset_'.$creator_id;
		if ($this->redis_obj->exists($zset_name)) {
			$this->redis_obj->Zrem($zset_name,$id);
		}else{
			$res = $this->where('creator_id='.$creator_id)->field('id')->select();
			foreach ($res as $key) {
				$this->redis_obj->Zadd($zset_name,$key['id'],$key['id']);
			}
		}
	}	 

	//ajax请求 查找权限表中的所有创建者
	public function get_creator()
	{
		$sql = 'SELECT DISTINCT(creator_id) FROM blog_permission';
		$res = $this->query($sql);
		foreach ($res as $key) {
			$data[] = $key['creator_id'];
		}
		// $data = array_unique($data);

		$adminModel = model('Admin');
		$where = '';
		foreach ($data as $key => $value) {
			$where .= 'or id = '.$value.' ';
		}
		$where = substr($where,2);
		return $adminModel->where($where)->field('id,username')->select();
	}

	//pjax请求 查看权限表  查找相应的数据 每页分10个
	public function pjax_lst($count,$input = null)	//count 为传来的记录总数 	//input为控制器接收到的传参
	{	

		//如果是刚点进来查看页面
		if ($input == null) {
			//与 z表作对比 如果数量不对则更新permission_id_zset表
			$zcount = $this->redis_obj->Zcard('permission_id_zset');
			if ($count != $zcount) {
				$this->redis_permission_id_zset_all();
			}

			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrange('permission_id_zset',$start,$end);
		}else{
		//如果有传参
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;	//计算页数
			$end = $start+9;
			//如果有创建者传来
			if (isset($input['creator'])) {
				$zset_name = 'permission_creator_zset_'.$input['creator'];	//表名称
				if (!$this->redis_obj->Zcard($zset_name)) {
					$this->redis_permission_creator_zset_one($input['creator']);	//如果没有这个表则刷新
				}
				if (isset($input['sort'])) {	//如果有排序规则
					if ($input['sort'] == 'order') {
						$res = $this->redis_obj->Zrange($zset_name,$start,$end);	//查出正序创建id 从start到end
						if (!$res) {
							$res = $this->redis_obj->Zrange($zset_name,0,9);		//如果结果为空 则查第一页
						}
					}else{
						$res = $this->redis_obj->Zrevrange($zset_name,$start,$end);	//查出倒序创建id 从start到end
						if (!$res) {
							$res = $this->redis_obj->Zrevrange($zset_name,0,9);	//如果结果为空 则查第一页
						}
					}
				}else{
					$res = $this->redis_obj->Zrange($zset_name,$start,$end);	//默认：查出正序创建id 从start到end
					if (!$res) {
						$res = $this->redis_obj->Zrange($zset_name,0,9);	//如果结果为空 则查第一页
					}
				}
			}else{
				//如果没有创建者但有排序规则
				if (isset($input['sort'])) {
					if ($input['sort'] == 'order') {
						$res = $this->redis_obj->Zrange('permission_id_zset',$start,$end);
					}else{
						$res = $this->redis_obj->Zrevrange('permission_id_zset',$start,$end);
					}
				}else{	//如果什么都没有 或只有页数
					$res = $this->redis_obj->Zrange('permission_id_zset',$start,$end);
					if (!$res) {
						$this->redis_permission_id_zset_all();
						$res = $this->redis_obj->Zrange('permission_id_zset',$start,$end);
					}
				}
			}
		}
		$list = $this->redis_obj->Hmget('permission_lst',$res);
		return $list;
	}

	//改变权限表的创建者  	其更改内容：mysql表创建者 redis中相关的表
	//$data:数据信息，一维数组，key为权限表id，value为要更改成的管理员id
	//$move_admin_id:将要被转移权限表创建者的管理员id
	//$user_permission:当前用户权限信息
	public function move_creator($data,$move_admin_id,$user_permission)
	{
		$move_admin_per = $this->select_admin_per($move_admin_id);		//被转移权限表创建者的管理员的权限信息
		$levelModel = model('Level');
		$levels = $levelModel->select_lst();							//查询所有级别
		if ($levels[$user_permission['admin_level_id']]['sort'] >= $levels[$move_admin_per['admin_level_id']]['sort']) {
			return '您管理员列表模块级别过低或相同，无法进行此操作！';
		}
		if ($levels[$user_permission['permission_level_id']]['sort'] >= $levels[$move_admin_per['permission_level_id']]['sort']) {
			return '您管理员权限设定模块级别过低相同！无法进行此操作！';
		}
		$list = [];
		foreach ($data as $key => $value) {
			if (!$this->where(['id'=>$key,'creator_id'=>$move_admin_id])->find()) {		//验证每个权限表的创建者是不是一个人or表单有没有篡改
				return '您的操作不合法！请重新操作！';
			}
			$list[$key]['id'] = $key;
			$list[$key]['creator_id'] = $value;
			$list[$key]['reviser_id'] = $value;
		}

		if ($this->saveAll($list)) {	//批量修改
			foreach ($data as $key => $value) {
				//更新redis中数据
				$this->redis_permission_lst_one($key);
				$this->redis_permission_lst_all_one($key);
				$this->del_redis_permission_creator_zset_one($key,$move_admin_id);
				$this->redis_permission_creator_zset_one($value,$key);
			}
		}else{
			return '更改失败！';
		}
	}

	//改变权限创建者并修改
	public function edit_move_creator_change($user_id,$data)				//当前用户id  当前将要修改的数据,修改数据包括创建者id
	{
		if ($this->redis_obj->Hexists('permission_lst',$data['id'])) {		//如果此条数据存在
			$res = $this->selectOne($data['id']);
			$af_creator_id = $res['creator_id'];							//查找到此表先前创建者id
			if ($this->updateOne($data,$user_id)) {
				if ($this->redis_obj->Zcard('permission_creator_zset_'.$af_creator_id)) {//先前创建者的表如果存在
					$this->del_redis_permission_creator_zset_one($data['id'],$af_creator_id);						
				}
				$this->redis_permission_creator_zset_one($data['creator_id'],$data['id']);		//更新有关数据
				return true;						//修改数据
			}
		}
	}

	//批量获取转移创建者符合条件的管理员
	public function select_more_redis_permission_lst_all_one($data,$user_id,$user_permission)	//data:批量数据，一维索引数组 权限表id
	{	
		$res = [];
		$levelModel = model('Level');
		$levels = $levelModel->select_lst();
		foreach ($data as $key => $value) {
			$per_level_id = $this->where(['id'=>$value])->value('permission_level_id');		//权限表级别id
			$user_per_level_sort = $levels[$user_permission['permission_level_id']]['sort'];	//用户权限表级别
			$sort = $levels[$per_level_id]['sort'];							//当前权限表级别
			$sql = 'SELECT a.id,a.username AS username FROM blog_admin AS a INNER JOIN blog_permission AS b INNER JOIN blog_level AS c INNER JOIN blog_level AS d ON a.permission_id = b.id AND b.permission_level_id = c.id AND b.permission_level_id = d.id AND c.id = d.id AND b.permission_see = 1 AND b.permission_u = 1 AND c.sort > '.$user_per_level_sort.' AND d.sort < '.$sort;
			$admin_user = $this->query($sql);
			$length = count($admin_user);
			$admin_user[$length]['id'] = $user_id;
			$admin_user[$length]['username'] = '【您自己的名下】';
			$per_name = $this->where(['id'=>$value])->value('name');
			$res[$value]['permission_id'] = $value;
			$res[$value]['permission_name'] = $per_name;
			$res[$value]['move_admin'] = $admin_user;
		}
		return $res;
	}

	//检查并批量转移创建者
	//data:一维数组 key为权限表id value为转移成的创建者id
	//user_permission:当前用户表信息
	public function more_move_permission_creator_change($data,$user_permission)
	{
		$levelModel = model('Level');
		$levels = $levelModel->select_lst();
		$list = [];		//批量修改的数组
		$creator = [];	//原先创建者的数组
		foreach ($data as $key => $value) {
			$res = $this->select_admin_per($value);		//查找将要转移的创建者权限表信息
			$move_creator_per_sort = $levels[$res['permission_level_id']]['sort'];
			$user_creator_per_sort = $levels[$user_permission['permission_level_id']]['sort'];
			if ($user_creator_per_sort > $move_creator_per_sort) {
				return '部分转移的创建者权限设定模块级别不够！';
			}
			$per = $this->where(['id'=>$key])->field('permission_level_id,creator_id')->find();
			$per_sort = $levels[$per['permission_level_id']]['sort'];			//当前转移的权限表级别
			if ($per_sort <= $move_creator_per_sort) {
				return '部分将要转移的权限设定模块级别比被转移者高或平级！';
			}
			$list[$key]['id'] = $key;
			$list[$key]['creator_id'] = $value;
			$list[$key]['reviser_id'] = $value;
			$creator[$key]['creator_id'] = $per['creator_id'];
		}
		//批量修改
		if ($this->saveAll($list)) {
			foreach ($list as $key => $value) {	
				//更新redis中数据
				$this->redis_permission_lst_one($value['id']);
				$this->redis_permission_lst_all_one($value['id']);
				$this->redis_permission_creator_zset_one($value['creator_id'],$value['id']);
				$this->del_redis_permission_creator_zset_one($value['id'],$creator[$key]['creator_id']);
			}
		}
	}

	//检查并批量删除权限表
	//data:一维索引数组，value为权限表id
	//user_permission:当前用户表信息
	//user_id：当前用户id
	public function more_move_permission_delete($data,$user_permission,$user_id)
	{
		$levelModel = model('Level');
		$adminModel = model('Admin');
		$levels = $levelModel->select_lst();
		$user_permission_level_sort = $levels[$user_permission['permission_level_id']]['sort'];
		$creator = [];
		//遍历检查
		foreach ($data as $key => $value) {
			$per = $this->where(['id'=>$value])->field('name,creator_id,permission_level_id')->find();
			$per_level_sort = $levels[$per['permission_level_id']]['sort'];
			if ($user_id == $per['creator_id']) {
				//空操作
			}else if ($user_permission_level_sort >= $per_level_sort) {
				return '【'.$per['name'].'】的管理员权限设定模块级别比您高或相同，无法进行批量删除操作,请去除后重新尝试！';
			}
			if ($adminModel->where(['permission_id'=>$value])->field('id')->find()) {
				return '【'.$per['name'].'】权限正被部分管理员使用，请去除后重新尝试!';
			}
			$creator[$value] = $per['creator_id'];
		}
		if ($this->destroy($data)) {	//批量删除
			//更新redis中数据
			foreach ($data as $key => $value) {
				$this->del_redis_permission_lst_one($value);
				$this->del_redis_permission_lst_all_one($value);
				$this->del_redis_permission_id_zset_one($value);
				$this->del_redis_permission_creator_zset_one($value,$creator[$value]);
			}
		}
	}
}