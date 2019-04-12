<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
	// '__pattern__' => [
	//     'name' => '\w+',
	// ],
	// '[hello]'     => [
	//     ':id'   => ['index/hello', ['method' => 'get'], ['id' => '\d+']],
	//     ':name' => ['index/hello', ['method' => 'post']],
	// ],
	'category/[:id]'			=> ['index/category/index',['ext'=>'html']],
	'index/get_articles'		=> ['index/index/get_articles',['ext'=>'html']],
	'search'					=> ['index/index/search',['ext'=>'html']],
	// 'visit'						=> ['index/index/visit',['ext'=>'html']],
	'article/[:id]'				=> ['index/article/index',['ext'=>'html']],
	'link'						=> ['index/link/lst',['ext'=>'html']],
	'tag/:id'					=> ['index/tag/find_tag',['ext'=>'html']],
	'tag'						=> ['index/tag/lst',['ext'=>'html']],
	'line'						=> ['index/line/index',['ext'=>'html']],
];
