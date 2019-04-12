<?php
namespace app\admin\model;

use think\Model;
use think\Image;
use think\cache\driver\Redis;

class Article extends Model
{
	public $redis_obj;
	public function initialize()
	{
		$this->redis_obj = new Redis();
	}

/****************************上传图片***************************************/
	//上传列表图片
	public function upload_pic($img)		//列表图片的$_FIELS一维数组
	{
		$res = Image::open($img['tmp_name']);
		$res->thumb(240,165);
		$name = uniqid();
		$houzhui = strrchr($img['name'],'.');	//.xxx
		$path = DS.'static'.DS.'index'.DS.'article_image'.DS.'img'.DS.$name.$houzhui;
		if ($res->save(ROOT_PATH.'public'.$path)) {
			return $path;
		}else{
			return false;
		}
	}

	//上传滚动图片
	public function upload_roll_pic($img)		//滚动图片的$_FIELS一维数组
	{
		$res = Image::open($img['tmp_name']);
		$res->crop(820,200);
		$name = uniqid();
		$houzhui = strrchr($img['name'],'.');	//.xxx
		$path = DS.'static'.DS.'index'.DS.'article_image'.DS.'roll_img'.DS.$name.$houzhui;
		if ($res->save(ROOT_PATH.'public'.$path)) {
			return $path;
		}else{
			return false;
		}
	}
/****************************zset获取数据***********************************/
#长度
	//获取redis中 article_id_zset的长度
	public function redis_select_count_article_id_zset()
	{
		if ($count = $this->redis_obj->Zcard('article_id_zset')) {
			return $count;
		}else{
			return count($this->redis_article_id_zset(true));
		}
	}
	//获取redis中 article_column_?_state_id_zset的长度	？为栏目id
	public function redis_select_count_article_column_waht_state_id_zset($column_id)		//栏目id
	{
		if ($count = $this->redis_obj->Zcard('article_column_'.$column_id.'_state_id_zset')) {
			return $count;
		}else{
			if ($count = count($this->redis_article_column_what_state_id_zset($column_id,true))) {
				return $count;
			}else{
				return 0;
			}
		}
	}
	//获取redis中 article_column_?_roll_id_zset的长度    ？为栏目id
	public function redis_select_count_article_column_waht_roll_id_zset($column_id)			//栏目id
	{
		if ($count = $this->redis_obj->Zcard('article_column_'.$column_id.'_roll_id_zset')) {
			return $count;
		}else{
			if ($count = count($this->redis_article_column_what_roll_id_zset($column_id,true))) {
				return $count;
			}else{
				return 0;
			}
		}
	}
	//获取redis中 article_state_id_zset的长度
	public function redis_select_count_article_state_id_zset()
	{
		if ($count = $this->redis_obj->Zcard('article_state_id_zset')) {
			return $count;
		}else{
			return count($this->redis_article_state_id_zset(true));
		}
	}
	//获取redis中 article_roll_id_zset的长度
	public function redis_select_count_article_roll_id_zset()
	{
		if ($count = $this->redis_obj->Zcard('article_roll_id_zset')) {
			return $count;
		}else{
			return count($this->redis_article_roll_id_zset(true));
		}
	}
	//获取redis中 article_column_id_zset_?的长度 	?为栏目id
	public function redis_select_count_article_column_id_zset_what($column_id)			//栏目id
	{
		if ($count = $this->redis_obj->Zcard('article_column_id_zset_'.$column_id)) {
			return $count;
		}else{
			if ($count = count($this->redis_article_column_id_zset_what($column_id,true))) {
				return $count;
			}else{
				return 0;
			}
		}
	}
	//获取redis中 article_tag_?_id_zset的长度 	?为标签 id
	public function redis_select_count_article_tag_what_id_zset($tag_id)		//标签id
	{
		if ($count = $this->redis_obj->Zcard('article_tag_'.$tag_id.'_id_zset')) {
			return $count;
		}else{
			$this->redis_article_tag_what_id_zset($tag_id);
			return $this->redis_obj->Zcard('article_tag_'.$tag_id.'_id_zset');
		}
	}
#数据
	//article_column_id_zset_?		?为栏目id  	获取全部数据
	public function redis_select_article_column_id_zset_what($column_id)
	{
		if ($count = $this->redis_obj->Zcard('article_column_id_zset_'.$column_id)) {
			return $this->redis_obj->Zrange('article_column_id_zset_'.$column_id,0,$count-1);
		}else{
			$this->redis_article_column_id_zset_what($column_id);
			if ($count = $this->redis_obj->Zcard('article_column_id_zset_'.$column_id)) {
				return $this->redis_obj->Zrange('article_column_id_zset_'.$column_id,0,$count-1);
			}else{
				return false;
			}
		}
	}
	//article_tag_?_id_zset 		?为标签id	获取全部数据
	public function redis_select_article_tag_what_id_zset($tag_id)		//标签id
	{
		if ($count = $this->redis_obj->Zcard('article_tag_'.$tag_id.'_id_zset')) {
			return $this->redis_obj->Zrange('article_tag_'.$tag_id.'_id_zset',0,$count-1);
		}else{
			$this->redis_article_tag_what_id_zset($tag_id);
			if ($count = $this->redis_obj->Zcard('article_tag_'.$tag_id.'_id_zset')) {
				return $this->redis_obj->Zrange('article_tag_'.$tag_id.'_id_zset',0,$count-1);
			}else{
				return false;
			}
		}
	}
/****************************hash获取数据***********************************/
	//获取article_lst中一条数据
	public function redis_select_article_lst_one($article_id)		//文章id
	{
		if (!$this->redis_obj->exists('article_lst')) {
			$this->redis_article_lst();
		}
		return $this->redis_obj->Hget('article_lst',$article_id);
	}
	//获取article_lst_all中一条数据
	public function redis_select_article_lst_all_one($article_id)		//文章id
	{
		if (!$this->redis_obj->Hexists('article_lst_all',$article_id)) {
			$this->redis_article_lst_all($article_id);
		}
		return $this->redis_obj->Hget('article_lst_all',$article_id);
	}
/****************************逻辑入口***************************************/
	//新增一条数据
	public function insert_one($data)		//将要新增的数据，包含上传的图片路径
	{
		if (isset($data['pic_small'])) {
			if ($res = $this->upload_pic($data['pic_small'])) {
				$data['pic_small'] = $res;
			}else{
				return '文章列表图片上传失败';
			}
		}else{
			$data['pic_small'] = '';
		}
		if (isset($data['roll_pic'])) {
			if ($res = $this->upload_roll_pic($data['roll_pic'])) {
				$data['roll_pic'] = $res;
			}else{
				return '滚动图片上传失败';
			}
		}else{
			$data['roll_pic'] = '';
		}
		if (isset($data['__token__'])) {	//去掉token
			unset($data['__token__']);
		}
		$data['click'] = 0;		//文章访问数
		$data['comment_count'] = 0;		//文章评论数
		$data['time'] = time();			//发布时间
		if (!isset($data['roll'])) {
			$data['roll'] = 0;			//是否滚动（幻灯片）
		}
		if (!isset($data['state'])) {
			$data['state'] = 0;			//是否推荐
		}
		$list = $data;					//此处为以下不同逻辑做准备
		$tagModel = model('admin/Tag');
		$columnModel = model('admin/Column');
		$where = '';
		foreach ($list['tag_id'] as $key => $value) {
			$where .= 'or id = '.$value.' ';			//拼凑条件语句
		}
		$where = substr($where,2);
		$column = $columnModel->redis_select_column_lst_one($list['column_id']);		//栏目名称
		$list['column_name'] = $column['name'];
		$list['tag'] = $tagModel->where($where)->field('id,name')->select();						//文章所含的tag二维数组
		$data['tag_id'] = json_encode($data['tag_id']);												//标签转换成json数据

		if ($this->save($data)) {
			$columnModel = model('admin/Column');
			$tagModel = model('admin/Tag');
			$list['id'] = $this->id;
			$this->redis_article_column_id_zset_what_one($this->id,$list['column_id']);
			// $this->redis_article_hot_id_zset_one($this->id,$list['click']);						//点击量0不算 所以不用加这个
			$this->redis_article_id_zset_one($this->id);
			$this->redis_article_tag_what_id_zset_one($this->id,$list['tag']);
			$this->redis_article_front_lst_one($this->id,$list);
			$this->redis_article_content_lst_one($this->id,$list);
			$this->redis_article_lst_one($this->id,$list);
			$this->redis_article_lst_all_one($this->id,$list);

			$columnModel->article_count_up_one($list['column_id']);				//刷新栏目表  栏目下文章+1
			foreach ($list['tag'] as $key) {
				$tagModel->article_count_up_one($key['id']);					//刷新标签   标签下文章+1
			}

			if ($list['state'] == '1') {			//相关推荐
				$this->redis_article_state_id_zset_one($this->id);
				$this->redis_article_column_what_state_id_zset_one($this->id,$list['column_id']);
				$this->redis_article_state_lst_one($this->id,$list);
			}
			if ($list['roll'] == '1') {				//滚动文章
				$this->redis_article_roll_id_zset_one($this->id);
				$this->redis_article_roll_lst_one($this->id,$list);
				$this->redis_article_column_what_roll_id_zset_one($this->id,$list['column_id']);
			}
		}else{
			if (file_exists(ROOT_PATH.'public'.$data['pic_small']) && $data['pic_small'] != '') {
				unlink(ROOT_PATH.'public'.$data['pic_small']);
			}
			if (file_exists(ROOT_PATH.'public'.$data['roll_pic']) && $data['roll_pic'] != '') {
				unlink(ROOT_PATH.'public'.$data['roll_pic']);
			}
			return '添加文章失败，请刷新后重新尝试';
		}
	}

	//修改一条数据
	public function update_one($data,$old_data)		//将要修改的数据，包含上传的图片路径 | 未更改前的数据
	{
		if (!isset($data['state'])) {
			$data['state'] = 0;
		}
		if (!isset($data['roll'])) {
			$data['roll'] = 0;
		}
		if (isset($data['__token__'])) {
			unset($data['__token__']);
		}
		if (isset($data['pic_small'])) {		//上传图片
			if ($res = $this->upload_pic($data['pic_small'])) {
				$data['pic_small'] = $res;
				if (file_exists(ROOT_PATH.'public'.$old_data['pic_small']) && $old_data['pic_small'] != '') {
					unlink(ROOT_PATH.'public'.$old_data['pic_small']);
				}
			}		
		}
		if (isset($data['roll_pic'])) {
			if ($data['roll'] == '1') {
				if ($res = $this->upload_roll_pic($data['roll_pic'])) {
					$data['roll_pic'] = $res;
					if (file_exists(ROOT_PATH.'public'.$old_data['roll_pic']) && $old_data['roll_pic'] != '') {
						unlink(ROOT_PATH.'public'.$old_data['roll_pic']);
					}
				}
			}else{
				$data['roll_pic'] = '';
				if (file_exists(ROOT_PATH.'public'.$old_data['roll_pic']) && $old_data['roll_pic'] != '') {
					unlink(ROOT_PATH.'public'.$old_data['roll_pic']);
				}
			}
		}else{
			if ($data['roll'] == '0') {
				$data['roll_pic'] = '';
				if (file_exists(ROOT_PATH.'public'.$old_data['roll_pic']) && $old_data['roll_pic'] != '') {
					unlink(ROOT_PATH.'public'.$old_data['roll_pic']);
				}
			}else{
				$data['roll_pic'] = $old_data['roll_pic'];
			}
		}
		$data['tag_id'] = json_encode($data['tag_id']);							//标签转换成json数据
		if ($this->update($data)) {

			$tagModel = model('admin/Tag');
			$columnModel = model('admin/Column');

			$list = $this->where(['id'=>$data['id']])->find();					//此处为以下不同逻辑做准备
			$list['tag_id'] = json_decode($list['tag_id'],true);
			$where = '';
			foreach ($list['tag_id'] as $key => $value) {
				$where .= 'or id = '.$value.' ';			//拼凑条件语句
			}
			$where = substr($where,2);
			$column = $columnModel->redis_select_column_lst_one($list['column_id']);		//栏目名称
			$list['column_name'] = $column['name'];
			$list['tag'] = $tagModel->where($where)->field('id,name')->select();			//文章所含的tag二维数组


			$this->redis_article_front_lst_one($list['id'],$list);
			$this->redis_article_content_lst_one($list['id'],$list);
			$this->redis_article_lst_one($list['id'],$list);
			$this->redis_article_lst_all_one($list['id'],$list);
			if ($old_data['column_id'] != $data['column_id']) {			//栏目变动了

				$columnModel->article_count_down_one($old_data['column_id']);		//栏目下文章数-1操作
				$columnModel->article_count_up_one($data['column_id']);				//新的栏目下+1操作

				$this->redis_del_article_column_id_zset_what_one($list['id'],$old_data['column_id']);
				$this->redis_del_article_column_what_state_id_zset_one($list['id'],$old_data['column_id']);
				$this->redis_del_article_column_what_roll_id_zset_one($list['id'],$old_data['column_id']);
				$this->redis_article_column_id_zset_what_one($list['id'],$list['column_id']);
				$this->redis_del_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);

				if ($old_data['state'] == '1' && $data['state'] == '0') {		//相关推荐变动了
					$this->redis_del_article_column_what_state_id_zset_one($list['id'],$list['column_id']);
				}elseif($old_data['state'] == '0' && $data['state'] == '1'){
					$this->redis_article_column_what_state_id_zset_one($list['id'],$list['column_id']);
				}elseif ($data['state'] == '1' && $old_data['state'] == '1') {
					$this->redis_article_column_what_state_id_zset_one($list['id'],$list['column_id']);
				}
				if ($old_data['roll'] == '1' && $data['roll'] == '0') {			//滚动文章变动了
					$this->redis_del_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);
					if (file_exists(ROOT_PATH.'public'.$old_data['roll_pic']) && $old_data['roll_pic'] != '') {
						unlink(ROOT_PATH.'public'.$old_data['roll_pic']);
					}
				}elseif ($old_data['roll'] == '0' && $data['roll'] == '1') {
					$this->redis_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);
				}elseif ($data['roll'] == '1' && $old_data['roll'] == '1') {
					$this->redis_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);
				}
			}

			if ($old_data['state'] == '1' && $data['state'] == '0') {		//相关推荐变动了
				$this->redis_del_article_column_what_state_id_zset_one($list['id'],$list['column_id']);
				$this->redis_del_article_state_id_zset_one($list['id']);
				$this->redis_del_article_state_lst_one($list['id']);
			}elseif($old_data['state'] == '0' && $data['state'] == '1'){
				$this->redis_article_column_what_state_id_zset_one($list['id'],$list['column_id']);
				$this->redis_article_state_lst_one($list['id'],$list);
				$this->redis_article_state_id_zset_one($list['id']);
			}

			if ($old_data['roll'] == '1' && $data['roll'] == '0') {			//滚动文章变动了
				$this->redis_del_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);
				$this->redis_del_article_roll_id_zset_one($list['id']);
				$this->redis_del_article_roll_lst_one($list['id']);
				if (file_exists(ROOT_PATH.'public'.$old_data['roll_pic']) && $old_data['roll_pic'] != '') {
					unlink(ROOT_PATH.'public'.$old_data['roll_pic']);
				}
			}elseif ($old_data['roll'] == '0' && $data['roll'] == '1') {
				$this->redis_article_column_what_roll_id_zset_one($list['id'],$list['column_id']);
				$this->redis_article_roll_id_zset_one($list['id']);
				$this->redis_article_roll_lst_one($list['id'],$list);
			}
			if ($data['roll_pic'] != $old_data['roll_pic']) {
				$this->redis_article_roll_lst_one($list['id'],$list);
			}
			$old_data_tag_arr = json_decode($old_data['tag_id'],true);
			if ($old_data_tag_arr != $list['tag_id']) {		//TAG标签变动了
				$res = array_merge(array_diff($old_data_tag_arr,$list['tag_id']),array_diff($list['tag_id'],$old_data_tag_arr));
				foreach ($res as $key => $value) {
					if (in_array($value,$old_data_tag_arr) && !in_array($value,$list['tag_id'])) {		//这意味着 标签减少了  也就是进行-1操作
						$tagModel->article_count_down_one($value);
						$this->redis_del_article_tag_what_id_zset_one($list['id'],$value);
					}elseif (!in_array($value,$old_data_tag_arr) && in_array($value,$list['tag_id'])) {	//这意味着 标签增多了  也就是进行+1操作
						$tagModel->article_count_up_one($value);
						$this->redis_article_tag_what_id_zset_one($list['id'],$value,true);
					}
				}
			}
		}else{
			return '修改失败，请刷新后重新尝试';
		}
	}

	//删除 一条 or 多条数据
	public function delete_data($data,$type = 'article')				//传来的数据，可能是字符串（单条），也可能是一维索引数组（多条） | type：来处理的类型，例如：删除栏目所带来的删除文章动作 则为 column
	{
		$columnModel = model('admin/Column');
		$tagModel = model('admin/Tag');
		$commentModel = model('admin/Comment');
		if (!is_array($data)) {
			$list[0] = $data;
		}else{
			$list = $data;
		}
		$where = '';
		foreach ($list as $key => $value) {
			$where .='id = '.$value.' OR ';
		}
		$where = substr($where,0,-3);
		$old_data = $this->where($where)->select();
		if ($this->destroy($list)) {
			foreach ($old_data as $key) {
				$this->redis_del_article_column_id_zset_what_one($key['id'],$key['column_id']);
				$this->redis_del_article_hot_id_zset_one($key['id']);
				$this->redis_del_article_id_zset_one($key['id']);
				$this->redis_del_article_front_lst_one($key['id']);
				$this->redis_del_article_content_lst_one($key['id']);
				$this->redis_del_article_lst_one($key['id']);
				$this->redis_del_article_lst_all_one($key['id']);
				if ($key['state'] == '1') {
					$this->redis_del_article_state_id_zset_one($key['id']);
					$this->redis_del_article_column_what_state_id_zset_one($key['id'],$key['column_id']);
					$this->redis_del_article_state_lst_one($key['id']);
				}
				if ($key['roll'] == '1') {
					$this->redis_del_article_roll_id_zset_one($key['id']);
					$this->redis_del_article_column_what_roll_id_zset_one($key['id'],$key['column_id']);
					$this->redis_del_article_roll_lst_one($key['id']);
				}
				$tags = json_decode($key['tag_id'],true);
				foreach ($tags as $key1 => $value1) {
					$this->redis_del_article_tag_what_id_zset_one($key['id'],$value1);
					$tagModel->article_count_down_one($value1);
				}
				//删除文章下的所有评论
				$commentModel->del_article_comment($key['id']);

				if ($type === 'article') {
					//栏目-1
					$columnModel->article_count_down_one($key['column_id']);			//此处为删除栏目时不必再-1
				}

				if (file_exists(ROOT_PATH.'public'.$key['pic_small']) && $key['pic_small'] != '') {
					unlink(ROOT_PATH.'public'.$key['pic_small']);
				}
				if (file_exists(ROOT_PATH.'public'.$key['roll_pic']) && $key['roll_pic'] != '') {
					unlink(ROOT_PATH.'public'.$key['roll_pic']);
				}
			}
		}else{
			return '操作失误，请刷新后重新尝试';
		}
	}

	//批量同步某个栏目下的文章数据（此方法表明更改了栏目信息，故栏目下文章跟着更改）
	public function update_more_column_article($column_id)		//栏目id
	{
		if ($res = $this->redis_select_article_column_id_zset_what($column_id)) {
			foreach ($res as $key => $value) {
				$this->redis_article_front_lst_one($value);
				$this->redis_article_content_lst_one($value);
				$this->redis_article_lst_one($value);
			}
		}else{
			return false;
		}
	}

	//文章中所使用的标签改变名字时，同步redis表的数据
	public function update_more_tag_article($tag_id)		//标签id
	{
		if ($res = $this->redis_select_article_tag_what_id_zset($tag_id)) {
			foreach ($res as $key => $value) {
				$this->redis_article_content_lst_one($value);
			}
		}else{
			return false;
		}
	}
/****************************验证部分***************************************/
	//验证栏目和标签是否存在
	public function check_column_tag($column_id,$tag)	//column_id为int  tag为一维数组
	{
		$columnModel = model('admin/Column');
		$tagModel = model('admin/Tag');
		if (!$columnModel->where(['id'=>$column_id])->field('id')->find()) {
			return '您所选择的栏目不存在，请刷新页面后重新尝试';
		}
		$where = '';
		foreach ($tag as $key => $value) {
			$where .= 'or id = '.$value.' ';
		}
		$where = substr($where,2);
		$res = $tagModel->where($where)->select();
		if (count($res) != count($tag)) {
			return '您所选择的TAG，有部分不存在，建议您刷新后重试';
		}
	}
/****************************redis刷新一条数据******************************/
#以下为zset类型
	//article_column_id_zset_?表  ？为栏目id	 此表为某个栏目下的所有文章id
	public function redis_article_column_id_zset_what_one($article_id,$column_id)	//文章id，栏目id
	{
		if ($this->redis_obj->exists('article_column_id_zset_'.$column_id)) {
			$this->redis_obj->Zadd('article_column_id_zset_'.$column_id,$article_id,$article_id);
		}else{
			$this->redis_article_column_id_zset_what($column_id);
		}
	}
	//article_hot_id_zset		热门文章排序
	public function redis_article_hot_id_zset_one($article_id,$click)			//文章id，点击量
	{
		if ($this->redis_obj->exists('article_hot_id_zset')) {
			$this->redis_obj->Zadd('article_hot_id_zset',$click,$article_id);
		}else{
			$this->redis_article_hot_id_zset();
		}
	}
	//article_id_zset   所有文章id在此表
	public function redis_article_id_zset_one($article_id)			//文章id
	{
		if ($this->redis_obj->exists('article_id_zset')) {
			$this->redis_obj->Zadd('article_id_zset',$article_id,$article_id);
		}else{
			$this->redis_article_id_zset();
		}
	}
	//article_roll_id_zset  轮播文章id在此表
	public function redis_article_roll_id_zset_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_roll_id_zset')) {
			$this->redis_obj->Zadd('article_roll_id_zset',$article_id,$article_id);
		}else{
			$this->redis_article_roll_id_zset();
		}
	}
	//article_state_id_zset   相关推荐id在此表
	public function redis_article_state_id_zset_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_state_id_zset')) {
			$this->redis_obj->Zadd('article_state_id_zset',$article_id,$article_id);
		}else{
			$this->redis_article_state_id_zset();
		}
	}

	//article_column_?_state_id_zset    ？为栏目id     此表为栏目下的相关推荐
	public function redis_article_column_what_state_id_zset_one($article_id,$column_id)		//文章id | 栏目id
	{
		if ($this->redis_obj->exists('article_column_'.$column_id.'_state_id_zset')) {
			$this->redis_obj->Zadd('article_column_'.$column_id.'_state_id_zset',$article_id,$article_id);
		}else{
			$this->redis_article_column_what_state_id_zset($column_id);
		}
	}
	//article_column_?_roll_id_zset		?为栏目id		此表为栏目下的轮播文章
	public function redis_article_column_what_roll_id_zset_one($article_id,$column_id)		//文章id | 栏目id
	{
		if ($this->redis_obj->exists('article_column_'.$column_id.'_roll_id_zset')) {
			$this->redis_obj->Zadd('article_column_'.$column_id.'_roll_id_zset',$article_id,$article_id);
		}else{
			$this->redis_article_column_what_roll_id_zset($column_id);
		}
	}
	/*
	* article_tag_?_id_zset  	?为标签id  			  此表为tag下的文章
	* 如果one为true 则tag的传参应为tag的id，如果为false则tag的传参应为一个二维数组 包含id与name
	* 默认为false
	*/
	public function redis_article_tag_what_id_zset_one($article_id,$tag,$one = false)				
	{
		if ($one === true) {
			if ($this->redis_obj->exists('article_tag_'.$tag.'_id_zset')) {
				$this->redis_obj->Zadd('article_tag_'.$tag.'_id_zset',$article_id,$article_id);
			}else{
				$this->redis_article_tag_what_id_zset($tag);
			}
		}else{
			foreach ($tag as $key) {
				if ($this->redis_obj->exists('article_tag_'.$key['id'].'_id_zset')) {
					$this->redis_obj->Zadd('article_tag_'.$key['id'].'_id_zset',$article_id,$article_id);
				}else{
					$this->redis_article_tag_what_id_zset($key['id']);
				}
			}
		}
	}
#以下为hash类型
	//article_front_lst		 查询列表数据用
	public function redis_article_front_lst_one($article_id,$data = null)		//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_front_lst')) {
			if ($data) {
				$list['id']					=	$data['id'];
				$list['title']				=	$data['title'];
				$list['time'] 				=	$data['time'];
				$list['comment_count'] 		=	$data['comment_count'];
				$list['click'] 				=	$data['click'];
				$list['desc']				=	$data['desc'];
				$list['pic_small']			= 	$data['pic_small'];
				$list['column_name']		=	$data['column_name'];
				$list['column_id']			=	$data['column_id'];
			}else{
				$list = $this->where(['id'=>$article_id])->field('id,title,time,comment_count,click,desc,pic_small,column_id')->find();
				$columnModel = model('admin/Column');
				$list['column_name'] = $columnModel->where(['id'=>$list['column_id']])->value('name');
			}
			$this->redis_obj->Hset('article_front_lst',$article_id,$list);
		}else{
			$this->redis_article_front_lst();
		}
	}
	//article_content_lst		文章详细页面
	public function redis_article_content_lst_one($article_id,$data = null)		//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_content_lst')) {
			if ($data) {
				$list['id']					=	$data['id'];
				$list['title']				=	$data['title'];
				$list['time'] 				=	$data['time'];
				$list['source_text']		=	$data['source_text'];
				$list['source_url']			=	$data['source_url'];
				$list['column_name']		=	$data['column_name'];
				$list['column_id']			=	$data['column_id'];
				$list['click'] 				=	$data['click'];
				$list['comment_count'] 		=	$data['comment_count'];
				$list['content']			=	$data['content'];
				$list['tag']				=	$data['tag'];
			}else{
				$columnModel = model('admin/Column');
				$tagModel = model('admin/Tag');
				$list = $this->where(['id'=>$article_id])->field('id,title,time,source_text,source_url,column_id,click,comment_count,content,tag_id')->find();
				$list['column_name'] = $columnModel->where(['id'=>$list['column_id']])->value('name');
				$tag = json_decode($list['tag_id'],true);
				$where = '';
				foreach ($tag as $key => $value) {
					$where .= 'or id = '.$value.' ';
				}
				$where = substr($where,2);
				$list['tag'] = $tagModel->where($where)->field('id,name')->select();
				unset($list['tag_id']);
			}
			$this->redis_obj->Hset('article_content_lst',$article_id,$list);
		}else{
			$this->redis_article_content_lst();
		}
	}

	//article_state_lst		栏目下的相关推荐
	public function redis_article_state_lst_one($article_id,$data = null)		//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_state_lst')) {
			if ($data) {
				$list['id'] = $data['id'];
				$list['title'] = $data['title'];
			}else{
				$list = $this->where(['id'=>$article_id])->field('id','title')->find();
			}
			$this->redis_obj->Hset('article_state_lst',$article_id,$list);
		}else{
			$this->redis_article_state_lst();
		}
	}

	//article_roll_lst		首页滚动文章
	public function redis_article_roll_lst_one($article_id,$data = null)		//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_roll_lst')) {
			if ($data) {
				$list['id'] = $data['id'];
				$list['title'] = $data['title'];
				$list['roll_pic'] = $data['roll_pic'];
			}else{
				$list = $this->where(['id'=>$article_id])->field('id,title,roll_pic')->find();
			}
			$this->redis_obj->Hset('article_roll_lst',$article_id,$list);
		}else{
			$this->redis_article_roll_lst();
		}
	}

	//article_lst 	后台列表展示用
	public function redis_article_lst_one($article_id,$data = null)			//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_lst')) {
			if ($data) {
				$list['id'] = $data['id'];
				$list['title'] = $data['title'];
				$list['state'] = $data['state'];
				$list['roll'] = $data['roll'];
				$list['pic_small'] = $data['pic_small'];
				$list['column_name'] = $data['column_name'];
				$list['click'] = $data['click'];
				$list['comment_count'] = $data['comment_count'];
				$list['time'] = $data['time'];
			}else{
				$list = $this->where(['id'=>$article_id])->field('id,title,state,roll,pic_small,column_id,click,comment_count,time')->find();
				$columnModel = model('admin/Column');
				$list['column_name'] = $columnModel->where(['id'=>$list['column_id']])->value('name');
				unset($list['column_id']);
			}
			$this->redis_obj->Hset('article_lst',$article_id,$list);
		}else{
			$this->redis_article_lst();
		}
	}

	//article_lst_all	后台文章编辑页用
	public function redis_article_lst_all_one($article_id,$data = null)		//文章id | 将要刷新的数据可有可无，如果没有则再从新查询
	{
		if ($this->redis_obj->exists('article_lst_all')) {
			if ($data) {
				$list['id'] 			= $data['id'];
				$list['title'] 			= $data['title'];
				$list['desc'] 			= $data['desc'];
				$list['keyword']		= $data['keyword'];
				$list['source_text'] 	= $data['source_text'];
				$list['source_url']		= $data['source_url'];
				$list['pic_small']		= $data['pic_small'];
				$list['roll_pic']		= $data['roll_pic'];
				$list['column_id']		= $data['column_id'];
				$list['tag_id']			= $data['tag_id'];		//此处接收的是数组而不是json
				$list['roll']			= $data['roll'];
				$list['state']			= $data['state'];
				$list['content']		= $data['content'];
			}else{
				$list = $this->where(['id'=>$article_id])->field('id,title,desc,keyword,source_text,source_url,pic_small,roll_pic,column_id,tag_id,roll,state,content')->find();
				$list['tag_id'] = json_decode($list['tag_id'],true);
			}
			$this->redis_obj->Hset('article_lst_all',$article_id,$list);
		}else{
			$this->redis_article_lst_all($article_id);
		}
	}
/****************************redis刷新全部数据******************************/
#以下为zset类型
	//刷新article_column_id_zset_?表所有数据  ？为栏目id
	public function redis_article_column_id_zset_what($column_id,$re = false)		//栏目id  |  如果为true则返回全部数据
	{
		if ($res = $this->where(['column_id'=>$column_id])->field('id')->select()) {
			foreach ($res as $key) {
				$this->redis_obj->Zadd('article_column_id_zset_'.$column_id,$key['id'],$key['id']);
			}	
		}
		if ($re === true) {
			return $res;
		}
	}
	//刷新article_hot_id_zset表所有数据
	public function redis_article_hot_id_zset()
	{
		$res = $this->field('id,click')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_hot_id_zset',$key['click'],$key['id']);
		}
	}
	//刷新article_id_zset所有数据
	public function redis_article_id_zset($re = false)		//如果为true则返回全部数据
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}
	//刷新article_roll_id_zset所有数据
	public function redis_article_roll_id_zset($re = false)		//如果为true则返回全部数据
	{
		$res = $this->where(['roll'=>1])->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_roll_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}
	//刷新article_state_id_zset所有数据
	public function redis_article_state_id_zset($re = false)		//如果为true则返回全部数据
	{
		$res = $this->where(['state'=>1])->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_state_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}  
	//刷新article_column_?_state_id_zset表  ？为栏目id  某个栏目的相关推荐
	public function redis_article_column_what_state_id_zset($column_id,$re = false)		//栏目id	|  如果为true则返回全部数据
	{
		$res = $this->where(['column_id'=>$column_id,'state'=>1])->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_column_'.$column_id.'_state_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}
	//刷新article_column_?_roll_id_zset	？为栏目id   某个栏目的轮播文章
	public function redis_article_column_what_roll_id_zset($column_id,$re = false)		//栏目id	|	如果为true则返回全部数据
	{
		$res = $this->where(['column_id'=>$column_id,'roll'=>1])->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('article_column_'.$column_id.'_roll_id_zset',$key['id'],$key['id']);
		}
		if ($re === true) {
			return $res;
		}
	}
	//刷新article_tag_?_id_zset   ？为tagid			某个tag下的文章
	public function redis_article_tag_what_id_zset($tag_id)			//tag的id
	{
		$res = $this->field('id,tag_id')->select();
		$list = [];
		foreach ($res as $key) {
			$tags = json_decode($key['tag_id'],true);
			foreach ($tags as $key1 => $value1) {
				if ($value1 == $tag_id) {
					$list[] = $key['id'];
				}
			}
		}
		foreach ($list as $key => $value) {
			$this->redis_obj->Zadd('article_tag_'.$tag_id.'_id_zset',$value,$value);
		}
	}
#以下为hash类型
	//刷新article_front_lst表的所有数据
	public function redis_article_front_lst()
	{
		$res = $this->alias('a')
					->join('blog_column b','a.column_id=b.id')
					->field('a.id,a.title,a.time,a.comment_count,a.click,a.desc,a.pic_small,a.column_id,b.name column_name')
					->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('article_front_lst',$list);
	}
	//刷新article_content_lst_one表的所有数据
	public function redis_article_content_lst()
	{	
		$res = $this->alias('a')
					->join('blog_column b','a.column_id=b.id')
					->field('a.id,a.title,a.time,a.source_text,a.source_url,a.column_id,b.name column_name,a.click,a.comment_count,a.content,a.tag_id')
					->select();
		$tagModel = model('admin/Tag');
		$list = [];
		foreach ($res as $key) {
			$tag = json_decode($key['tag_id'],true);
			$where = '';
			foreach ($tag as $key1 => $value1) {
				$where .= 'or id = '.$value1.' ';
			}
			$where = substr($where,2);
			unset($key['tag_id']);
			unset($key['tag']);
			$key['tag'] = $tagModel->where($where)->field('id,name')->select();
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('article_content_lst',$list);
	}
	//刷新article_state_lst表所有数据
	public function redis_article_state_lst()
	{
		$res = $this->where('state','=','1')->field('id,title')->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('article_state_lst',$list);
	}
	//刷新article_roll_lst表所有数据
	public function redis_article_roll_lst()
	{
		$res = $this->where(['roll'=>1])->field('id,title,roll_pic')->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('article_roll_lst',$list);
	}
	//刷新article_lst表全部数据
	public function redis_article_lst()
	{
		$res = $this->alias('a')
					->join('blog_column b','a.column_id = b.id')
					->field('a.id,a.title,a.state,a.roll,a.pic_small,b.name column_name,a.click,a.comment_count,a.time')
					->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key);
		}
		$this->redis_obj->Hmset('article_lst',$list);
	}
	//刷新article_lst_all某个表的数据
	public function redis_article_lst_all($article_id)		//文章id
	{
		$res = $this->where(['id'=>$article_id])->field('id,title,desc,keyword,source_text,source_url,pic_small,roll_pic,column_id,tag_id,roll,state,content')->find();

		if ($res) {
			$res['tag_id'] = json_decode($res['tag_id'],true);
			$this->redis_obj->Hset('article_lst_all',$article_id,$res);
		}
	}
/****************************redis删除一条数据******************************/
#以下为zset类型
	//article_column_id_zset_?   ？为栏目id   
	public function redis_del_article_column_id_zset_what_one($article_id,$column_id)		//文章id，栏目id
	{
		if ($this->redis_obj->exists('article_column_id_zset_'.$column_id)) {
			$this->redis_obj->Zrem('article_column_id_zset_'.$column_id,$article_id);
		}
	}
	//article_state_id_zset  	相关推荐
	public function redis_del_article_state_id_zset_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_state_id_zset')) {
			$this->redis_obj->Zrem('article_state_id_zset',$article_id);
		}
	}
	//article_column_?_state_id_zset 	?为栏目id
	public function redis_del_article_column_what_state_id_zset_one($article_id,$column_id)		//文章id，栏目id
	{
		if ($this->redis_obj->exists('article_column_'.$column_id.'_state_id_zset')) {
			$this->redis_obj->Zrem('article_column_'.$column_id.'_state_id_zset',$article_id);
		}
	}
	//article_roll_id_zset
	public function redis_del_article_roll_id_zset_one($article_id)			//文章id
	{
		if ($this->redis_obj->exists('article_roll_id_zset')) {
			$this->redis_obj->Zrem('article_roll_id_zset',$article_id);
		}
	}
	
	//article_column_?_roll_id_zset   ?为栏目id
	public function redis_del_article_column_what_roll_id_zset_one($article_id,$column_id)		//文章id,栏目id
	{
		if ($this->redis_obj->exists('article_column_'.$column_id.'_roll_id_zset')) {
			$this->redis_obj->Zrem('article_column_'.$column_id.'_roll_id_zset',$article_id);
		}
	}
	//article_tag_?_id_zset   ?为tagid
	public function redis_del_article_tag_what_id_zset_one($article_id,$tag_id)			//文章id,tagid
	{
		if ($this->redis_obj->exists('article_tag_'.$tag_id.'_id_zset')) {
			$this->redis_obj->Zrem('article_tag_'.$tag_id.'_id_zset',$article_id);
		}
	}
	//article_hot_id_zset
	public function redis_del_article_hot_id_zset_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_hot_id_zset')) {
			$this->redis_obj->Zrem('article_hot_id_zset',$article_id);
		}
	}
	//article_id_zset
	public function redis_del_article_id_zset_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_id_zset')) {
			$this->redis_obj->Zrem('article_id_zset',$article_id);
		}
	}
#以下为hash类型
	//article_state_lst		相关推荐  
	public function redis_del_article_state_lst_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_state_lst')) {
			$this->redis_obj->Hdel('article_state_lst',$article_id);
		}
	}
	//article_roll_lst			滚动文章
	public function redis_del_article_roll_lst_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_roll_lst')) {
			$this->redis_obj->Hdel('article_roll_lst',$article_id);
		}
	}
	//article_front_lst			文章列表
	public function redis_del_article_front_lst_one($article_id)	//文章id
	{
		if ($this->redis_obj->exists('article_front_lst')) {
			$this->redis_obj->Hdel('article_front_lst',$article_id);
		}
	}
	//article_content_lst     详细文章
	public function redis_del_article_content_lst_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_content_lst')) {
			$this->redis_obj->Hdel('article_content_lst',$article_id);
		}
	}
	//article_lst 
	public function redis_del_article_lst_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_lst')) {
			$this->redis_obj->Hdel('article_lst',$article_id);
		}
	}
	//article_lst_all
	public function redis_del_article_lst_all_one($article_id)		//文章id
	{
		if ($this->redis_obj->exists('article_lst_all')) {
			$this->redis_obj->Hdel('article_lst_all',$article_id);
		}
	}
/****************************分页算法**************************************/
	/*
	* 查询符合条件的分页数据
	* 如果没有input传参，证明是post提交不带任何条件，此时count传参为article_lst的长度
	* 如果有input传参，证明是get提交带条件，此时count传参为符合条件的zset表长度，input传参为接收到的条件
	*/
	public function pjax_lst($zcount,$input = null)
	{
		if (!$input) {
			$count = $this->redis_obj->Hlen('article_lst');
			if ($zcount != $count) {
				$this->redis_article_lst();
			}
			$start = 0;
			$end = 9;
			$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
		}else{
			if (!$this->redis_obj->exists('article_lst')) {		//如果不存在则刷新
				$this->redis_article_lst();
			}
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$start = $input['page']*10-10;
			$end = $start+9;
			if (isset($input['sort']) || isset($input['column']) || isset($input['see'])) {
				if (isset($input['sort']) && isset($input['column']) && isset($input['see'])) {
					if ($input['sort'] == 'order') {	//正序排列
						if ($input['see'] == 'state') {		//相关推荐
							$res = $this->redis_obj->Zrange('article_column_'.$input['column'].'_state_id_zset',$start,$end);
						}elseif($input['see'] == 'roll'){	//滚动文章
							$res = $this->redis_obj->Zrange('article_column_'.$input['column'].'_roll_id_zset',$start,$end);
						}else{	//默认
							$res = $this->redis_obj->Zrevrange('article_column_id_zset'.$input['column'],$start,$end);
						}
					}elseif($input['sort'] == 'sort'){	//倒序排列
						if ($input['see'] == 'state') {		//相关推荐
							$res = $this->redis_obj->Zrevrange('article_column_'.$input['column'].'_state_id_zset',$start,$end);
						}elseif($input['see'] == 'roll'){	//滚动文章
							$res = $this->redis_obj->Zrevrange('article_column_'.$input['column'].'_roll_id_zset',$start,$end);
						}else{	//默认
							$res = $this->redis_obj->Zrevrange('article_column_id_zset'.$input['column'],$start,$end);
						}
					}else{
						$res = $this->redis_obj->Zrevrange('article_column_id_zset'.$input['column'],$start,$end);
					}
				}elseif (isset($input['sort']) && isset($input['column'])) {
					if ($input['sort'] == 'order') {	//正序排列
						$res = $this->redis_obj->Zrange('article_column_id_zset_'.$input['column'],$start,$end);
					}elseif ($input['sort'] == 'sort') {//倒序排列
						$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$input['column'],$start,$end);
					}else{	//默认
						$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$input['column'],$start,$end);
					}
				}elseif (isset($input['sort']) && isset($input['see'])) {
					if ($input['see'] == 'state') {	//相关推荐
						if ($input['sort'] == 'order') {	//正序排列
							$res = $this->redis_obj->Zrange('article_state_id_zset',$start,$end);
						}elseif ($input['sort'] == 'sort') {//倒序排列
							$res = $this->redis_obj->Zrevrange('article_state_id_zset',$start,$end);
						}else{	//默认
							$res = $this->redis_obj->Zrevrange('article_state_id_zset',$start,$end);
						}
					}elseif ($input['see'] == 'roll') {	//轮播文章
						if ($input['sort'] == 'order') {	//正序排列
							$res = $this->redis_obj->Zrange('article_roll_id_zset',$start,$end);
						}elseif ($input['sort'] == 'sort') {	//倒序排列
							$res = $this->redis_obj->Zrevrange('article_roll_id_zset',$start,$end);
						}else{	//默认
							$res = $this->redis_obj->Zrevrange('article_roll_id_zset',$start,$end);
						}
					}else{
						$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
					}
				}elseif (isset($input['column']) && isset($input['see'])) {
					if ($input['see'] == 'state') {	//相关推荐
						$res = $this->redis_obj->Zrevrange('article_column_'.$input['column'].'_state_id_zset',$start,$end);
					}elseif ($input['see'] == 'roll') {	//滚动文章
						$res = $this->redis_obj->Zrevrange('article_column_'.$input['column'].'_roll_id_zset',$start,$end);
					}else{	//默认
						$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$input['column'],$start,$end);
					}
				}elseif (isset($input['sort'])) {
					if ($input['sort'] == 'order') {	//正序排列
						$res = $this->redis_obj->Zrange('article_id_zset',$start,$end);
					}elseif ($input['sort'] == 'sort') {	//倒序排列
						$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
					}else{	//默认
						$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
					}
				}elseif (isset($input['see'])) {
					if ($input['see'] == 'state') {
						$res = $this->redis_obj->Zrevrange('article_state_id_zset',$start,$end);
					}elseif ($input['see'] == 'roll') {
						$res = $this->redis_obj->Zrevrange('article_roll_id_zset',$start,$end);
					}else{
						$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
					}
				}elseif (isset($input['column'])) {
					$res = $this->redis_obj->Zrevrange('article_column_id_zset_'.$input['column'],$start,$end);
				}
			}else{
				$res = $this->redis_obj->Zrevrange('article_id_zset',$start,$end);
			}
		}
		if (!$res) {
			return array();
		}
		$list = $this->redis_obj->Hmget('article_lst',$res);
		return $list;
	}
}