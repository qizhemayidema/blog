<?php 
namespace app\admin\validate;

use think\Validate;

class Link extends Validate
{
	protected $rule = [
				'link_name'	=>'require|chsAlphaNum|max:12',
				'link_url'	=>'require|url',
				'__token__'	=>'token',
			];

	protected $message = [
				'link_name.require'		=>'名称不能为空',
				'link_name.chsAlphaNum'	=>'名称只能含有汉字、字母、数字',
				'link_name.max'			=>'名称最大长度为12',
				'link_url.require'		=>'地址不能为空',
				'link_url.url'			=>'链接不合法',
				'__token__.token'		=>'不能重复提交',
			];
}