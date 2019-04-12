<?php 
namespace app\admin\controller;

use think\Controller;
use think\Session;
use app\admin\model\Permission as PermissionModel;

//后台父类控制器
class Common extends Controller
{
	protected $user_id;				//当前用户id
	protected $user_permission;		//当前用户的权限表信息
	protected $permissionModel;		//权限表模块的实例化对象

	protected function _initialize()
	{	
		if (!$session = Session::get('admin')) {
			$this->redirect('Login/index');
		}
		$this->permissionModel = new PermissionModel();
		// $res = $this->permissionModel->select();
		// var_dump($res);
		// die;
		//获取当前用户的id
		$this->user_id = $session['id'];
		//获取当前用户的权限表所有信息
		$res = $this->permissionModel->select_admin_per($this->user_id);
		if (!is_array($res) || $res === false) {
			unset($_SESSION);
			$this->redirect('Login/index');
		}
		$this->user_permission = $res;
		$this->assign('user_permission',$this->user_permission); //把该用户的权限表传入模板
		$this->assign('session',$session);	//把用户信息传入模板
	}
	//二维数组按照某一个字段排列
	protected function arr_sort($arr,$field)
	{
		$array1 = [];
		foreach ($arr as $key) {
			$array1[] = $key["$field"];
		}
		array_multisort($array1,SORT_ASC,$arr);
		return $arr;
	}
}