<?php 
namespace app\admin\validate;

use think\Validate;

class LevelEdit extends Validate
{
	protected $rule = [
		'id'		=>'require|number',
		'name'		=>'require|chsAlphaNum|length:1,10',
		'sort'		=>'require|length:1,11',
	];

	protected $message = [
		'id.require'		=>'操作非法！',
		'id.number'			=>'操作非法！',
		'name.require'		=>'名称必须填写!',
		'name.chsAlphaNum'	=>'名称只能含有：汉子，字母，数字！',
		'name.length'		=>'名称长度必须在1到10之间!',
		'sort.require'		=>'级别等级必须填写！',
		'sort.length'		=>'级别等级字符长度只能在1到11之间！',
	];
}