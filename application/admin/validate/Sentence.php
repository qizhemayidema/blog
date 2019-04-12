<?php 
namespace app\admin\validate;

use think\Validate;

class Sentence extends Validate
{
	protected $rule = [
		'sentence'			=>'require|max:30',
		'other_sentence'	=>'require|max:120',
		'show'				=>'number',
		'__token__'			=>'token',
	];

	protected $message = [
		'sentence.require'			=>'中文句子必须填写',
		'sentence.max'				=>'中文句子最大长度为：30',
		'other_sentence.require'	=>'其他句子必须填写',
		'other_sentence.max'		=>'其他句子最大长度为：100',
		'show.number'				=>'操作非法',
		'__token__.token'			=>'不能重复提交',
	];
} 