<?php 
return[
	// 视图输出字符串内容替换
	'view_replace_str'=> [
		'__STATIC__'=>'/static/index',
	],
	'http_exception_template' => [
		404 => APP_PATH.'index/view/error/404.html',
		500 => APP_PATH.'index/view/error/404.html',
	],
];
