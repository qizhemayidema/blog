<?php 
namespace app\index\controller;

use app\index\controller\Base;
use app\index\model\Article as ArticleModel;
use page\Comment_page;
use think\Cookie;
use think\Request;

//文章详细页控制器
class Article extends Base
{	
	//文章详细页面
	public function index()
	{
		$articleModel = new ArticleModel();
		$commentModel = model('index/Comment');
		$flag = 0;

		if ($id = input('id')) {	//此处为文章id
			//判断文章点击量是否+1
			$article_cookie = Cookie::get('article');
			if (!strpos($article_cookie,$id.',')) {
				$article_cookie .= $id.',';
				Cookie::set('article',$article_cookie);
				$flag = 1;
			}
			
			$article = $articleModel->find_article(input('id'));															//文章详细信息
			if (empty($article)) {
				return $this->fetch('error/404');		//抛出404
			}
			$this->assign('article',$article);																				//文章详细信息
			$this->assign('state_articles',$articleModel->select_state_article($article['column_id'],$article['id']));		//文章的相关推荐
			$this->assign('articles_hot',$articleModel->select_hot_article());												//热门文章
			$this->assign('sentence',$articleModel->select_sentence_one());													//每日一句
			//文章评论相关数据
			if ($article['comment_count'] == 0) {
				$this->assign('comments','');
				$this->assign('page','');
				$this->assign('comment_count','');
				$this->assign('page_count',1);
			}else{
				$contents = $commentModel->page_lst($article['id']);															//评论数据;
				$count = $commentModel->select_count_comment_article_id_what_zset($article['id']);								//总评论长度
				$page_obj = new Comment_page($count,5,'comment_page',1,$article['id']);											//评论分页对象
				$this->assign('comments',$contents);																			//评论信息
				$this->assign('page',$page_obj->render);																		//评论分页
				$this->assign('comment_count',$count);																			//评论总长度，计算楼层使用
				$this->assign('page_count',1);																					//第几页
			}
			if (isset($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX']) {
				echo $this->fetch('article/lst_pjax');
			}else{
				$this->assign('columns',$articleModel->select_column());													//查找栏目
				echo $this->fetch('article/lst');
			}
			//文章点击量+1
			if ($flag == 1) {
				$commentModel->click_up_one($id);
			}
		}else{
			return $this->fetch('error/404');		//抛出404
		}
	}

	//文章评论
	public function comment()
	{
		$request_obj = Request::instance();
		$commentModel = model('index/Comment');
		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['code'])) {
					if (!captcha_check($data['code'])) {
						return json_encode(['error' => '1','msg'=>'验证码错误']);
					}
				}
				$validate = validate('Comment');
				if (!$validate->check($data)) {
					return json_encode(['error'=>'1','msg'=>$validate->getError()]);
				}
				if ($res = $commentModel->insert_one($data)) {
					return $res;
				}
			}
		}
	}
	//评论翻页接口
	public function comment_page()
	{
		$request_obj = Request::instance();

		if ($request_obj->isAjax()) {
			if ($data = input('post.')) {
				if (isset($data['article_id']) && isset($data['page'])) {
					$commentModel = model('index/Comment');
					$contents = $commentModel->page_lst($data['article_id'],$data['page']);		//评论内容
					$count = $commentModel->select_count_comment_article_id_what_zset($data['article_id']);		//总评论长度
					$page_obj = new Comment_page($count,5,'comment_page',$data['page'],$data['article_id']);	//分页对象
					$this->assign('comments',$contents);														//评论信息
					$this->assign('page',$page_obj->render);													//评论分页
					$this->assign('comment_count',$count);														//评论总长度，计算楼层使用
					$this->assign('page_count',$data['page']);													//页码
					$this->assign('page',$page_obj->render);
					return $this->fetch('article/comment_page');
				}
			}
		}
	} 
}