<?php
namespace app\index\model;

use app\index\model\Base;

class Click extends Base
{
/***********逻辑入口****************/
	//访问量+1
	public function up_one()
	{
		$date = date('Y-m-d');
		if ($click = $this->where(['time'=>$date])->field('id,time,click')->find()) {
			$click['click'] += 1;
			$click = json_decode($click,true);
			if ($this->update($click,['id'=>$click['id']])) {
				$this->redis_click_lst_one($click['id'],$click);
				$this->redis_click_id_zset_one($click['id']);
				$this->redis_click_all(1);
			}
		}else{
			$list = [
				'time'	=>$date,
				'click'	=>1,
			];
			if ($this->save($list)) {
				$list['id'] = $this->id;
				$this->redis_click_lst_one($this->id,$list);
				$this->redis_click_id_zset_one($this->id);
				$this->redis_click_all(1);
			}
		}
	}

/***********刷新一条数据***************/
	//click_lst
	public function redis_click_lst_one($click_id,$data = null)		//click的id，要刷新的数据，可有可无
	{
		if (!$data) {
			$list = $this->where(['id'=>$click_id])->find();
		}else{
			$list = [];
			$list['id'] = $data['id'];
			$list['time'] = $data['time'];
			$list['click'] = $data['click'];
		}
		if ($this->redis_obj->exists('click_lst')) {
			$this->redis_obj->Hset('click_lst',$click_id,$list);
		}else{
			$this->redis_click_lst();
		}
	}
	//click_id_zset
	public function redis_click_id_zset_one($click_id)
	{
		if ($this->redis_obj->exists('click_id_zset')) {
			$this->redis_obj->Zadd('click_id_zset',$click_id,$click_id);
		}else{
			$this->redis_click_id_zset();
		}
	}
	//click_all   自增多少 or 刷新
	public function redis_click_all($count,$shua = false)	//count自增多少，shua如果为true则只刷新，并返回数据，否则进行自增动作
	{
		if ($shua) {
			if (!$this->redis_obj->exists('click_all')) {
				$res = $this->field('click')->select();
				$list = 0;
				foreach ($res as $key) {
					$list += $key['click'];
				}
				$this->redis_obj->set('click_all',$list);
			}
			return $this->redis_obj->get('click_all');
		}else{
			if ($this->redis_obj->exists('click_all')) {
				$this->redis_obj->inc('click_all',$count);
			}else{
				$res = $this->field('click')->select();
				$list = 0;
				foreach ($res as $key) {
					$list += $key['click'];
				}
				$this->redis_obj->set('click_all',$list);
			}
		}
		
	}

/***********刷新所有数据***************/
	//click_lst
	public function redis_click_lst()
	{
		$res = $this->select();
		$list = [];
		foreach ($res as $key) {
			$list[$key['id']] = json_encode($key,true);
		}
		$this->redis_obj->Hmset('click_lst',$list);
	}
	//click_id_zset
	public function redis_click_id_zset()
	{
		$res = $this->field('id')->select();
		foreach ($res as $key) {
			$this->redis_obj->Zadd('click_id_zset',$key['id'],$key['id']);
		}
	}
}