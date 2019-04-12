<?php 
namespace app\admin\validate;

use think\Validate;

class Level extends Validate
{
	protected $rule = [
		'name'		=>'require|chsAlphaNum|length:1,10|unique:Level',
		'sort'		=>'require|length:1,11|unique:Level',
		'__token__'	=>'token',
	];

	protected $message = [
		'name.require'		=>'名称必须填写!',
		'name.chsAlphaNum'	=>'名称只能含有：汉子，字母，数字！',
		'name.length'		=>'名称长度必须在1到10之间!',
		'name.unique'		=>'该名称已存在！',
		'sort.require'		=>'级别等级必须填写！',
		'sort.length'		=>'级别等级字符长度只能在1到11之间！',
		'sort.unique'		=>'该等级已存在！',
		'__token__.token'	=>'不能重复提交！',
	];
}