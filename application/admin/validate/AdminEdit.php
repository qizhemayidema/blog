<?php 
namespace app\admin\validate;

use think\Validate;

class AdminEdit extends Validate
{
	protected $rule = [
		'password'			=>'alphaNum|length:6,16',
		'permission_id'		=>'number',
		'__token__'			=>'token',
	];

	protected $message =[
		'__token__.token'			=>'不能重复提交！',
		'password.alphaNum'			=>'管理员密码只能含有字母和数字！',
		'password.length'			=>'管理员密码的长度必须在6到16之间！',
		'permission_id.number'		=>'管理员权限等级填写非法！',

	];
}