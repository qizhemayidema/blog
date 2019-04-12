<?php 
namespace app\index\validate;

use think\Validate;

class Ip extends Validate
{
	protected $rule = [
		'as'			=> 'require',
		'query'			=> 'require|ip',
		'os'			=> 'require',
		'px'			=> 'require',
	];
}