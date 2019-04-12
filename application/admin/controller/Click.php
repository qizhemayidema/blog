<?php
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Click as ClickModel;
use page\Page;

//访问量控制器
class Click extends Common
{
	public function lst()
	{
		//判断可见权限
		$this->user_permission['click_see']==0?($this->redirect('Index/index')):'';

		$clickModel = new ClickModel();
		$index_clickModel = model('index/Click');
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $clickModel->redis_select_count_click_lst();
				$all_count = $index_clickModel->redis_click_all(1,true);
				$clicks = $clickModel->pjax_lst();
				$page_obj = new Page($count,10,'click_page',1);
				$this->assign('count',$count);
				$this->assign('all_count',$all_count);
				$this->assign('clicks',$clicks);
				$this->assign('page',$page_obj->render);
				return $this->fetch('click/lst_pjax');
			}else{
				$input = input();
				$count = $clickModel->redis_select_count_click_lst();
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$page_obj = new Page($count,10,'click_page',$input['page']);
				$clicks = $clickModel->pjax_lst($input);
				$this->assign('clicks',$clicks);
				$this->assign('page',$page_obj->render);
				return $this->fetch('click/lst_pjax_min');
			}
		}
		$input = input();
		if (!isset($input['page'])) {
			$input['page'] = 1;
		}
		$count = $clickModel->redis_select_count_click_lst();
		$all_count = $index_clickModel->redis_click_all(1,true);
		$clicks = $clickModel->pjax_lst($input);
		$page_obj = new Page($count,10,'click_page',$input['page']);
		$this->assign('count',$count);
		$this->assign('all_count',$all_count);
		$this->assign('clicks',$clicks);
		$this->assign('page',$page_obj->render);
		return $this->fetch();
	}
}