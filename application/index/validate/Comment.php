<?php 
namespace app\index\validate;

use think\Validate;

//文章评论验证
class Comment extends Validate
{
	protected $rule = [
		'name'				=>'require|max:10',
		'article_id'		=>'require|number',
		'p_id'				=>'require|number',
		'comment'			=>'require|max:80',
		'link_url'			=>'url|max:100',
		'reply_link_url'	=>'url|max:100',
		'__token__'			=>'token',
	];

	protected $message = [
		'name.require'			=>'昵称必须填写',
		'name.max'				=>'昵称超长，缩短一下吧',
		'article_id.require'	=>'操作失误，请刷新后再试试吧~',
		'article_id.number'		=>'操作失误，请刷新后再试试吧~',
		'p_id.require'			=>'操作失误，请刷新后再试试吧~',
		'p_id.number'			=>'操作失误，请刷新后再试试吧~',
		'link_url.url'			=>'网站地址格式错误，应例如：http://www.xxx.com',
		'link_url.max'			=>'网站地址太长啦',
		'reply_link_url.url'	=>'操作失误，请刷新后再试试吧',
		'reply_link_url.max'	=>'操作失误，请刷新后再试试吧',
		'comment.require'		=>'留下您的脚印吧',
		'comment.max'			=>'评论超长，缩短一下吧~',
		'__token__.token'		=>'不能重复提交',
	];
}