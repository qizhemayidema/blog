<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Visit as VisitModel;
use page\Page;

//ip记录控制器
class Visit extends Common
{	
	//展示页
	public function lst()
	{
		//判断可见权限
		$this->user_permission['visit_see']==0?($this->redirect('Index/index')):'';
		
		$visitModel = new VisitModel();
		$count = $visitModel->select_count_redis_visit_lst();
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$page_obj = new Page($count,10,'visit_page',1);
				$visits = $visitModel->pjax_lst();
				$this->assign('count',$count);
				$this->assign('page',$page_obj->render);
				$this->assign('visits',$visits);
				return $this->fetch('visit/lst_pjax');
			}else{
				$input = input();
				if (!isset($input['page'])) {
					$input['page'] = 1;
				}
				$page_obj = new Page($count,10,'visit_page',$input['page']);
				$visits = $visitModel->pjax_lst($input);
				$this->assign('page',$page_obj->render);
				$this->assign('visits',$visits);
				return $this->fetch('visit/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (!isset($input['page'])) {
				$input['page'] = 1;
			}
			$page_obj = new Page($count,10,'visit_page',$input['page']);
			$visits = $visitModel->pjax_lst($input);
			$this->assign('count',$count);
			$this->assign('page',$page_obj->render);
			$this->assign('visits',$visits);
			return $this->fetch();
		}
	}
}