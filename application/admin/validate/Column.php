<?php 
namespace app\admin\validate;

use think\Validate;

class Column extends Validate
{
	protected $rule = [
		'name'		=>'require|max:10',
		'show'		=>'number',
		'__token__'	=>'token',
	];

	protected $message = [
		'name.require'		=>'栏目名称必须填写',
		'name.max'			=>'栏目名称最大长度为10',
		'show.number'		=>'操作非法',
		'__token__.token'	=>'不能重复提交',
	];
}