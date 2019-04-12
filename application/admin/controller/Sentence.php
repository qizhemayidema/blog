<?php 
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Sentence as SentenceModel;
use think\Request;
use page\Page;

//每日一句控制器
class Sentence extends Common
{
	//展示页pjax
	public function lst()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		$sentenceModel = new SentenceModel();
		$count = $sentenceModel->select_sentence_lst(true);
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$sentences = $sentenceModel->pjax_lst($count);
				$page = new Page($count,10,'sentence_page',1);
				$this->assign('count',$count);
				$this->assign('sentences',$sentences);
				$this->assign('page',$page->render);
				return $this->fetch('sentence/lst_pjax');
			}else{
				$data = input('get.');
				if (!isset($data['page'])) {
					$data['page'] = 1;
				}
				$sentences = $sentenceModel->pjax_lst($count,$data);
				$page = new Page($count,10,'sentence_page',$data['page']);
				$this->assign('sentences',$sentences);
				$this->assign('page',$page->render);
				return $this->fetch('sentence/lst_pjax_min');
			}
		}else{
			$data = input('get.');
			if (!isset($data['page'])) {
				$data['page'] = 1;
			}
			$sentences = $sentenceModel->pjax_lst($count,$data);
			$page = new Page($count,10,'sentence_page',$data['page']);
			$this->assign('count',$count);
			$this->assign('sentences',$sentences);
			$this->assign('page',$page->render);
			return $this->fetch();
		}
	}

	//新增页pjax
	public function add()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('sentence/add_pjax');
		}else{
			return $this->fetch();
		}
	}

	//新增动作ajax
	public function add_change()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$sentenceModel = new SentenceModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['sentence_c'] == 0) {
					return 'error您没有权限进行新增动作';
				}
				$validate = validate('Sentence');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $sentenceModel->insert_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}
		}
	}

	//编辑页面pjax
	public function edit()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		if ($id = input('id')) {
			$sentenceModel = new SentenceModel();
			$sentence = $sentenceModel->selectt_sentence_lst_one($id);			
			if (!$sentence) {
				$this->error('操作失误，请重新操作');
			}
			$this->assign('sentence',$sentence);
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				return $this->fetch('sentence/edit_pjax');
			}else{
				return $this->fetch();
			}
		}else{
			$this->error('操作失误');
		}
	}

	//编辑动作ajax
	public function edit_change()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$sentenceModel = new SentenceModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['sentence_u'] == 0) {
					return 'error您没有权限进行修改动作';
				}
				$validate = validate('Sentence');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $sentenceModel->update_one($data)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'erro操作失误，请刷新后重新尝试';
			}
		}
	}
	//删除一条
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$sentenceModel = new SentenceModel();
		if ($request_obj->isAjax()) {
			if ($id = input('post.id')) {
				if ($this->user_permission['sentence_d'] == 0) {
					return 'error您没有权限进行删除动作';
				}
				if ($res = $sentenceModel->delete_data($id)) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重试尝试';
			}
		}
	}

	//批量删除动作ajax
	public function more_del_change()
	{
		//判断可见权限
		$this->user_permission['sentence_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$sentenceModel = new SentenceModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['sentence_d'] == 0) {
					return 'error您没有权限进行删除动作';
				}
				if ($res = $sentenceModel->delete_data($data['lst_form_checkbox_one'])) {
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重试尝试';
			}
		}
	}
}