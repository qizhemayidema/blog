<?php 
namespace app\admin\validate;

use think\Validate;

class Notice extends Validate
{
	protected $rule = [
		'text'			=>	'require',
		'__token__'		=>	'token',
	];

	protected $message = [
		'text.require'		=>'公告内容必须填写',
		'__token__.token'	=>'不能重复提交',
	];
}