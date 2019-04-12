<?php
namespace app\index\controller;

use app\index\controller\Base;
use app\index\model\Index as IndexModel;
use think\Request;

//首页（控制器）
class Index extends Base
{
	//首页
	public function index()
	{
		$indexModel = new IndexModel();
		$this->assign('notices_top',$indexModel->select_top_notice());				//置顶公告
		$this->assign('notices',$indexModel->select_notice());						//普通公告		这里如果没有数据的话会从数据库中查
		$this->assign('articles_hot',$indexModel->select_hot_article());			//热门文章
		$this->assign('articles_roll',$indexModel->select_roll_article());			//滚动文章
		$this->assign('tags',$indexModel->select_tag());							//标签
		$this->assign('front_articles',$indexModel->select_front_article());		//文章列表（最新文章）
		$this->assign('sentence',$indexModel->select_sentence_one());				//每日一句
		if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
			return $this->fetch('index/index_pjax');
		}else{
			$this->assign('columns',$indexModel->select_column());						//栏目
			return $this->fetch();
		}
	}

	//异步获取最新发布 or 栏目下文章列表 or 关键字搜索
	public function get_articles()
	{
	 	$request_obj = Request::instance();
	 	if ($request_obj->isAjax()) {
	 		if ($data = input('post.')) {
	 			if (!isset($data['page'])) {
	 				$data['page'] = 2;
	 				$start = 5;
	 				$end = 9;
	 			}else{
	 				$start = $data['page']*5-5;
	 				$end = $start+4;
	 			}
	 			if (isset($data['tag'])) {				//说明是tag下的文章筛选
	 				$tagModel = model('index/Tag');
	 				return json_encode($tagModel->select_tag_article($data['tag'],$data['page']),true);
	 			}
	 			if (isset($data['search_str'])) {
	 				$admin_articleModel = model('admin/Article');
	 				if (!isset($data['page'])) {
	 					$start = 5;
	 					$limit_count = 5;
	 				}else{
	 					$start = $data['page'] *5 -5;
	 					$limit_count = 5;
	 				}
	 				if ($res = $admin_articleModel->where('keyword','like','%'.$data['search_str'].'%')->order('id','desc')->limit($start,$limit_count)->field('id')->select()) {
						if (!$admin_articleModel->redis_obj->exists('article_front_lst')) {
							$admin_articleModel->redis_article_front_lst();
						}
						$list = [];
						foreach ($res as $key) {
							$list[] = $key['id'];
						}
						return json_encode($admin_articleModel->redis_obj->Hmget('article_front_lst',$list),true);
					}else{
						return json_encode([],true);
					}
	 			}
	 			if (isset($data['column'])) {			//说明是栏目下的文章筛选
	 				$indexModel = new IndexModel();
	 				if ($data['column'] == 'index') {	//说明是首页
	 					return json_encode($indexModel->select_front_article($start,$end),true);		//文章列表（最新文章）
	 				}else{
	 					return json_encode($indexModel->select_column_front_article($data['column'],$start,$end),true);		//某个栏目下的文章
	 				}
	 			}else{
	 				return json_encode($indexModel->select_front_article($start,$end),true);		//文章列表（最新文章）
	 			}
	 		}
	 	}
	}

	//搜索功能
	public function search()
	{
		$request_obj = Request::instance();
		if ($request_obj->isAjax()) {
			if ($str = input('str')) {
				$admin_articleModel = model('admin/Article');
				$indexModel = new IndexModel();
				if (!input('page')) {
					$page = 0;
				}else{
					$page = input('page') - 1;
				}
				if ($res = $admin_articleModel->where('keyword','like','%'.$str.'%')->order('id','desc')->limit($page,5)->field('id')->select()) {
					if (!$admin_articleModel->redis_obj->exists('article_front_lst')) {
						$admin_articleModel->redis_article_front_lst();
					}
					$list = [];
					foreach ($res as $key) {
						$list[] = $key['id'];
					}
					$articles = $admin_articleModel->redis_obj->Hmget('article_front_lst',$list);
				}else{
					$articles = [];
				}
				$this->assign('articles',$articles);	//文章信息
				$this->assign('str',$str);				//关键字

				return $this->fetch('index/search_pjax');
			}
		}
	}

	//记录用户信息 and 访问量+1
	public function visit()
	{
		$request_obj = Request::instance();
		$indexModel = new IndexModel();
		$clickModel = model('index/Click');
		if ($request_obj->isAjax()) {
			if ($data = input('post.data')) {
				$data = json_decode($data,true);
				$validate = validate('Ip');
				$clickModel->up_one();		//此处为访问量+1
				if ($validate->check($data)) {
					return $indexModel->insert_ip_one($data);
				}
			}
		}
	}
}
