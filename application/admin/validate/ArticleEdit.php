<?php
namespace app\admin\validate;

use think\Validate;

class ArticleEdit extends Validate
{
	protected $rule = [
		'title'			=>		'require|max:20',
		'desc'			=>		'require|max:50',
		'keyword'		=>		'require|max:50',
		'source_text'	=>		'require|max:10',
		'source_url'	=>		'require|url',
		'column_id'		=>		'require|number',
		'tag_id'		=>		'require',
		'roll'			=>		'number',
		'state'			=>		'number',
		'content'		=>		'require',
		'__token__'		=>		'token',
	];

	protected $message = [
		'title.require'			=>		'文章标题必须填写',
		'title.max'				=>		'文章标题最大长度为：20',
		'desc.require'			=>		'文章简介必须填写',
		'desc.max'				=>		'文章简介最大长度为：50',
		'keyword.require'		=>		'关键字必须填写',
		'keyword.max'			=>		'关键字最大长度为：50',
		'source_text.require'	=>		'来源名称必须填写',
		'source_text.max'		=>		'来源名称最大长度为：10',
		'source_url.require'	=>		'来源地址必须填写',
		'source_url.url'		=>		'来源地址格式不合法，应例如：http://www.baidu.com',
		'column_id.require'		=>		'文章必须选择栏目',
		'column_id.number'		=>		'操作失误！刷新后请重试',
		'tag_id.require'		=>		'标签至少需要选择一个',
		'roll.number'			=>		'操作失误！刷新后请重试',
		'state.number'			=>		'操作失误！刷新后请重试',
		'content.require'		=>		'文章内容必须填写',
		'__token__.token'		=>		'不能重复提交',
	];
}