<?php 
namespace app\admin\validate;

use think\Validate;

class Admin extends Validate
{
	protected $rule = [
		'username'			=>'require|alphaNum|length:4,10|unique:admin',
		'password'			=>'require|alphaNum|length:6,16',
		'permission_id'		=>'require|number',
		'__token__'			=>'token',
	];

	protected $message =[
		'username.require'			=>'管理员名必须填写!',
		'username.alphaNum'			=>'管理员名只能含有字母和数字!',
		'username.length'			=>'管理员名的长度必须在4到10之间！',
		'username.unique'			=>'管理员名已存在！',
		'__token__.token'			=>'不能重复提交！',
		'password.require'			=>'管理员密码必须填写！',
		'password.alphaNum'			=>'管理员密码只能含有字母和数字！',
		'password.length'			=>'管理员密码的长度必须在6到16之间！',
		'permission_id.require'		=>'管理员权限等级必须填写！',
		'permission_id.number'		=>'管理员权限等级填写非法！',

	];
}