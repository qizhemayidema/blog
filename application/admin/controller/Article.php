<?php
namespace app\admin\controller;

use app\admin\controller\Common;
use app\admin\model\Article as ArticleModel;
use page\Page;
use think\Validate;
use think\Request;
	
//文章控制器
class Article extends Common
{	
	//查看页面(pjax)
	public function lst()
	{
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$articleModel = new ArticleModel();
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($type = input('get.type')) {
				if ($type == 'find_column') {
					$columnModel = model('Column');		//此处实例
					if ($columns = $columnModel->redis_select_column_lst()) {
						$str = '';
						foreach ($columns as $key) {
							$str.= "<li><a href='javascript:void(0);' onclick='article_column_sort(".$key['id'].")' column_id=".$key['id'].">".$key['name']."</a></li>";
						}
						return $str;
					}else{
						return "error您当前没有任何栏目";
					}
				}
			}
		}
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			if (input('post.')) {
				$count = $articleModel->redis_select_count_article_id_zset();
				$articles = $articleModel->pjax_lst($count);
				$page_obj = new Page($count,10,'article_page',1);
				$this->assign('count',$count);
				$this->assign('articles',$articles);
				$this->assign('page',$page_obj->render);
				return $this->fetch('article/lst_pjax');
			}else{
				$input = input('get.');
				if (isset($input['see']) || isset($input['column'])) {			//查询符合条件的数据长度
					if (isset($input['see']) && isset($input['column'])) {
						if ($input['see'] == 'state') {
							$count = $articleModel->redis_select_count_article_column_waht_state_id_zset($input['column']);
						}elseif($input['see'] == 'roll'){
							$count = $articleModel->redis_select_count_article_column_waht_roll_id_zset($input['column']);
						}
					}elseif(isset($input['see'])){
						if ($input['see'] == 'state') {
							$count = $articleModel->redis_select_count_article_state_id_zset();
						}elseif($input['see'] == 'roll'){
							$count = $articleModel->redis_select_count_article_roll_id_zset();
						}
					}elseif(isset($input['column'])){
						$count = $articleModel->redis_select_count_article_column_id_zset_what($input['column']);
					}
				}else{
					$count = $articleModel->redis_select_count_article_id_zset();
				}
				if (!isset($input['page'])) {		//页码
					$input['page'] = 1;
				}
				$articles = $articleModel->pjax_lst($count,$input);		//查找符合条件的数据
				$page_obj = new Page($count,10,'article_page',$input['page']);
				$this->assign('articles',$articles);
				$this->assign('page',$page_obj->render);
				return $this->fetch('article/lst_pjax_min');
			}
		}else{
			$input = input('get.');
			if (isset($input['see']) || isset($input['column'])) {			//查询符合条件的数据长度
				if (isset($input['see']) && isset($input['column'])) {
					if ($input['see'] == 'state') {
						$count = $articleModel->redis_select_count_article_column_waht_state_id_zset($input['column']);
					}elseif($input['see'] == 'roll'){
						$count = $articleModel->redis_select_count_article_column_waht_roll_id_zset($input['column']);
					}
				}elseif(isset($input['see'])){
					if ($input['see'] == 'state') {
						$count = $articleModel->redis_select_count_article_state_id_zset();
					}elseif($input['see'] == 'roll'){
						$count = $articleModel->redis_select_count_article_roll_id_zset();
					}
				}elseif(isset($input['column'])){
					$count = $articleModel->redis_select_count_article_column_id_zset_what($input['column']);
				}
			}else{
				$count = $articleModel->redis_select_count_article_id_zset();
			}
			if (!isset($input['page'])) {		//页码
				$input['page'] = 1;
			}
			$articles = $articleModel->pjax_lst($count,$input);		//查找符合条件的数据
			$page_obj = new Page($count,10,'article_page',$input['page']);
			$this->assign('count',$articleModel->redis_select_count_article_id_zset());
			$this->assign('articles',$articles);
			$this->assign('page',$page_obj->render);
			return $this->fetch();
		}
	}

	//添加页面(pjax)
	public function add()
	{
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$columnModel = model('Column');
		$tagModel = model('Tag');
		$columns = $columnModel->redis_select_column_lst();
		$tags = $tagModel->select_redis_tag_lst();
		$this->assign('columns',$columns);
		$this->assign('tags',$tags);
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('article/add_pjax');
		}else{
			return $this->fetch();
		}
	}

	//添加动作(ajax)
	public function add_change()
	{	
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$articleModel = new ArticleModel(); 
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['article_c'] == '0') {
					return 'error您没有添加权限';
				}
				if ($_FILES['pic_small']['error'] == 0) {		//判断文章列表图片是否合法及存在
					$img = $request_obj->file('pic_small');
					$info = $img->check(['size'=>102400,'ext'=>'jpg,png']);
					if (!$info) {
						return 'error'.$img->getError();
					}
					$data['pic_small'] = $_FILES['pic_small'];		//图片信息存入数组
				}elseif($_FILES['pic_small']['error'] == 4){
					return 'error必须上传文章列表图片';
				}else{
					return 'error文章列表图片上传失败';
				}
				if (isset($data['roll'])) {					//判断滚动图片及是否合法
					if ($_FILES['roll_pic']['error'] == 4) {
						return 'error如果文章为滚动文章，则必须上传滚动图片';
					}elseif ($_FILES['roll_pic']['error'] == 0) {
						$img = $request_obj->file('roll_pic');
						$info = $img->check(['ext'=>'jpg,png']);
						if (!$info) {
							return 'error'.$img->getError();
						}
						$data['roll_pic'] = $_FILES['roll_pic'];		//图片信息存入数组
					}else{
						return 'error图片上传失败';
					}
				}
				if (isset($data['column_id']) && isset($data['tag_id'])) {			//判断栏目与标签是否存在
					if ($res = $articleModel->check_column_tag($data['column_id'],$data['tag_id'])) {
						return 'error'.$res;
					}
				}
				$validate = validate('Article');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $articleModel->insert_one($data)) {
					return 'error'.$res;
				}
				return 'true';	
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}	
		}
	}

	//编辑页面(pjax)
	public function edit()
	{	
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$articleModel = new ArticleModel();
		$columnModel = model('Column');
		$tagModel = model('Tag');
		$columns = $columnModel->redis_select_column_lst();
		$tags = $tagModel->select_redis_tag_lst();
		$this->assign('columns',$columns);
		$this->assign('tags',$tags);
		if ($id = input('id')) {
			$article = $articleModel->redis_select_article_lst_all_one($id);
			if (!$article) {
				$this->error('操作失误');
			}
			$this->assign('article',$article);
		}else{
			$this->error('操作非法');
		}
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('article/edit_pjax');
		}else{
			return $this->fetch();
		}
	}

	//编辑动作
	public function edit_change()
	{
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$articleModel = new ArticleModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if ($this->user_permission['article_u'] == '0') {
					return 'error您没有编辑权限';
				}
				//验证推荐
				$old_data = $articleModel->where(['id'=>$data['id']])->find();		//未更改前的数据
				if (!$old_data) {
					return 'error数据不同步，请刷新后重新尝试';
				}
				if (isset($data['roll'])) {					//此处表明修改文章为滚动文章、逐验证数据
					if ($_FILES['roll_pic']['error'] == 4 && !$old_data['roll_pic']) {
						return 'error如果文章为滚动文章，则必须上传滚动图片';
					}elseif($_FILES['roll_pic']['error'] == 0) {
						$img = $request_obj->file('roll_pic');
						$info = $img->check(['ext'=>'jpg,png']);
						if (!$info) {
							return 'error'.$img->getError();
						}
						$data['roll_pic'] = $_FILES['roll_pic'];		//图片信息存入数组
					}
				}
				if ($_FILES['pic_small']['error'] == 0) {		//判断文章列表图片是否合法及存在
					$img = $request_obj->file('pic_small');
					$info = $img->check(['size'=>102400,'ext'=>'jpg,png']);
					if (!$info) {
						return 'error'.$img->getError();
					}
					$data['pic_small'] = $_FILES['pic_small'];		//图片信息存入数组
				}
				if (isset($data['column_id']) && isset($data['tag_id'])) {			//判断栏目与标签是否存在
					if ($res = $articleModel->check_column_tag($data['column_id'],$data['tag_id'])) {
						return 'error'.$res;
					}
				}
				$validate = validate('Article');
				if (!$validate->check($data)) {
					return 'error'.$validate->getError();
				}
				if ($res = $articleModel->update_one($data,$old_data)) {
					return $res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}
		}
	}

	//展示页查找文章
	public function lst_find_article()
	{
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$articleModel = new ArticleModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.value')) {
				if ($ids = $articleModel->whereOr('title','like','%'.$data.'%')->whereOr('keyword','like','%'.$data.'%')->field('id')->select()) {
					$list = [];
					foreach ($ids as $key) {
						$list[] = $key['id'];
					}
					if (!$articleModel->redis_obj->exists('article_lst')) {
						$articleModel->redis_article_lst();
					}
					$res = $articleModel->redis_obj->Hmget('article_lst',$list);
					$this->assign('text',$data);
					$this->assign('articles',$res);
					return $this->fetch();
				}else{
					return 'error没有找到符合条件的文章';
				}
			}else{
				return 'error操作失误,请刷新后重新尝试';
			}
		}
	}
	//删除及批量删除
	public function delete_change()
	{
		//判断可见权限
		$this->user_permission['article_see']==0?($this->redirect('Index/index')):'';
		$request_obj = Request::instance();
		$articleModel = new ArticleModel();
		if ($request_obj->isAjax()) {
			if ($data = input('post.article')) {
				if ($this->user_permission['article_d'] == '0') {
					return 'error您没有删除权限';
				}
				$data = json_decode($data,true);
				if ($res = $articleModel->delete_data($data)) {		//删除动作
					return 'error'.$res;
				}
				return 'true';
			}else{
				return 'error操作失误，请刷新后重新尝试';
			}
		}
	}
}