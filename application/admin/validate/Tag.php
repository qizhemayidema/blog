<?php 
namespace app\admin\validate;

use think\Validate;

class Tag extends Validate
{
	protected $rule = [
		'name'			=>'require|max:10',
		'desc'			=>'max:20',
		'__token__'		=>'token',
	];

	protected $message = [
		'name.require'			=>'tag名称必须填写',
		'name.max'				=>'tag名称最大长度为10',
		'desc.max'				=>'备注最大长度为20',
		'__token__.token'		=>'不能重复提交',
	];
}