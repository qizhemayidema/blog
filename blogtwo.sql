-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: 2019-04-12 06:41:20
-- 服务器版本： 5.7.21
-- PHP Version: 7.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `blogtwo`
--

-- --------------------------------------------------------

--
-- 表的结构 `blog_admin`
--

DROP TABLE IF EXISTS `blog_admin`;
CREATE TABLE IF NOT EXISTS `blog_admin` (
  `id` mediumint(9) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL COMMENT '管理员名称',
  `password` char(32) NOT NULL COMMENT '管理员密码',
  `dpic` varchar(120) DEFAULT NULL COMMENT '头像 缩略图',
  `permission_id` int(11) NOT NULL COMMENT '权限id',
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=MyISAM AUTO_INCREMENT=114 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_article`
--

DROP TABLE IF EXISTS `blog_article`;
CREATE TABLE IF NOT EXISTS `blog_article` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL COMMENT '文章标题',
  `content` mediumtext NOT NULL COMMENT '文章内容',
  `keyword` varchar(150) NOT NULL COMMENT '文章关键字',
  `desc` varchar(150) NOT NULL COMMENT '文章简介',
  `source_text` varchar(30) NOT NULL COMMENT '文章来源地址名称',
  `source_url` varchar(60) NOT NULL COMMENT '文章来源地址',
  `column_id` int(11) NOT NULL COMMENT '所属栏目id',
  `tag_id` varchar(60) NOT NULL COMMENT '所属标签id',
  `click` int(11) NOT NULL COMMENT '点击量',
  `comment_count` int(11) NOT NULL COMMENT '文章评论数',
  `roll_pic` varchar(64) NOT NULL DEFAULT '' COMMENT '滚动图片',
  `pic_small` varchar(64) NOT NULL COMMENT '图片的缩略图',
  `roll` int(1) NOT NULL DEFAULT '0' COMMENT '是否首页滚动，默认为0，滚动为1',
  `state` int(1) NOT NULL DEFAULT '0' COMMENT '是否推荐，默认为0，推荐为1',
  `time` int(11) NOT NULL COMMENT '发布时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=74 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_click`
--

DROP TABLE IF EXISTS `blog_click`;
CREATE TABLE IF NOT EXISTS `blog_click` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` char(10) NOT NULL DEFAULT '' COMMENT '每天的记录日期',
  `click` int(11) NOT NULL DEFAULT '0' COMMENT '今天访问量',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_column`
--

DROP TABLE IF EXISTS `blog_column`;
CREATE TABLE IF NOT EXISTS `blog_column` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '栏目名字 也就是导航栏的东西',
  `show` int(1) NOT NULL DEFAULT '0' COMMENT '是否显示 1为显示 0为不显示',
  `article_count` int(11) NOT NULL COMMENT '栏目下文章数量',
  `time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_comment`
--

DROP TABLE IF EXISTS `blog_comment`;
CREATE TABLE IF NOT EXISTS `blog_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `top_id` int(11) NOT NULL COMMENT '追溯到顶级id，这个字段用来整理每个父评论下的所有评论，但不包括0',
  `p_id` int(11) NOT NULL DEFAULT '0' COMMENT '父级id',
  `article_id` int(11) DEFAULT NULL COMMENT '文章id',
  `name` varchar(30) NOT NULL DEFAULT '' COMMENT '评论者名字',
  `comment` varchar(255) NOT NULL DEFAULT '' COMMENT '评论内容',
  `reply_name` varchar(30) NOT NULL DEFAULT '' COMMENT '被回复者的名字',
  `time` int(11) NOT NULL COMMENT '评论时间',
  `old_comment` varchar(255) NOT NULL DEFAULT '' COMMENT '没被屏蔽前的评论',
  `link_url` varchar(100) NOT NULL DEFAULT '' COMMENT '评论时填写的网址',
  `reply_link_url` varchar(100) NOT NULL DEFAULT '' COMMENT '被回复者的链接',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=109 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_level`
--

DROP TABLE IF EXISTS `blog_level`;
CREATE TABLE IF NOT EXISTS `blog_level` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '级别名称',
  `desc` char(128) NOT NULL COMMENT '级别说明',
  `sort` int(11) NOT NULL COMMENT '级别等级',
  `creator_id` int(11) NOT NULL COMMENT '创建者id',
  `create_time` varchar(100) NOT NULL COMMENT '创建时间',
  `reviser_id` int(11) NOT NULL COMMENT '最后修改者id',
  `revise_time` int(11) NOT NULL COMMENT '最后修改时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=60 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_line`
--

DROP TABLE IF EXISTS `blog_line`;
CREATE TABLE IF NOT EXISTS `blog_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(33) DEFAULT NULL COMMENT '昵称',
  `ip` varchar(15) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL COMMENT '发送消息',
  `create_time` int(11) DEFAULT NULL COMMENT '消息创建时间',
  `state` tinyint(4) DEFAULT NULL COMMENT '状态值',
  `state_message` varchar(32) DEFAULT NULL COMMENT '状态消息',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_link`
--

DROP TABLE IF EXISTS `blog_link`;
CREATE TABLE IF NOT EXISTS `blog_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `link_name` varchar(32) NOT NULL COMMENT '友链名称',
  `link_url` varchar(100) NOT NULL COMMENT '友链链接',
  `link_ico` varchar(125) DEFAULT '' COMMENT '友链地址显示图片',
  `link_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2097180 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_notice`
--

DROP TABLE IF EXISTS `blog_notice`;
CREATE TABLE IF NOT EXISTS `blog_notice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` text NOT NULL COMMENT '公告内容',
  `show` int(1) NOT NULL COMMENT '是否展示，0为否，1为是',
  `top` int(1) NOT NULL COMMENT '是否置顶,0为否，1为是',
  `time` varchar(10) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=30 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_permission`
--

DROP TABLE IF EXISTS `blog_permission`;
CREATE TABLE IF NOT EXISTS `blog_permission` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '0' COMMENT '权限名称',
  `admin_see` int(1) DEFAULT '0' COMMENT '管理员列表模块可见性',
  `admin_c` int(1) DEFAULT '0' COMMENT '管理员列表模块增加操作是为1否为0',
  `admin_u` int(1) DEFAULT '0' COMMENT '管理员列表模块编辑操作是为1否为0',
  `admin_d` int(1) NOT NULL DEFAULT '0' COMMENT '管理员列表模块删除操作是为1否为0',
  `admin_level_id` int(11) NOT NULL COMMENT '管理员列表模块可操作级别',
  `permission_see` int(1) NOT NULL DEFAULT '0' COMMENT '权限设定模块可见性',
  `permission_c` int(1) NOT NULL DEFAULT '0' COMMENT '权限设定模块是否可做增加操作,是为1否为0',
  `permission_u` int(1) NOT NULL DEFAULT '0' COMMENT '权限设定模块是否可做修改操作,是为1否为0',
  `permission_d` int(1) NOT NULL DEFAULT '0' COMMENT '权限设定模块是否可做删除操作,是为1否为0',
  `permission_level_id` int(11) NOT NULL COMMENT '权限设定模块可操作的级别',
  `level_see` int(1) NOT NULL DEFAULT '0' COMMENT '权限级别模块可见性',
  `level_c` int(1) NOT NULL DEFAULT '0' COMMENT '权限级别模块是否可做新增操作 ',
  `level_u` int(1) NOT NULL DEFAULT '0' COMMENT '权限级别模块是否可做修改操作',
  `level_d` int(1) NOT NULL DEFAULT '0' COMMENT '权限级别模块是否可做删除操作',
  `level_level_id` int(11) NOT NULL COMMENT '权限级别模块可操作级别',
  `article_see` int(1) NOT NULL DEFAULT '0' COMMENT '文章模块可见性',
  `article_c` int(1) NOT NULL DEFAULT '0' COMMENT '文章模块是否可做增加操作',
  `article_u` int(1) NOT NULL DEFAULT '0' COMMENT '文章模块是否可做修改操作',
  `article_d` int(1) NOT NULL DEFAULT '0' COMMENT '文章模块是否可做删除操作',
  `column_see` int(1) NOT NULL DEFAULT '0' COMMENT '栏目可见性',
  `column_c` int(1) NOT NULL DEFAULT '0' COMMENT '栏目是否可以执行增加操作',
  `column_u` int(1) NOT NULL DEFAULT '0' COMMENT '栏目是否可以执行修改操作',
  `column_d` int(1) NOT NULL DEFAULT '0' COMMENT '栏目是否可以执行删除操作',
  `tag_see` int(1) NOT NULL DEFAULT '0' COMMENT 'tag模块可见性',
  `tag_c` int(1) NOT NULL DEFAULT '0' COMMENT 'tag模块是否可做增加操作',
  `tag_u` int(1) NOT NULL DEFAULT '0' COMMENT 'tag模块是否可做修改操作',
  `tag_d` int(1) NOT NULL DEFAULT '0' COMMENT 'tag模块是否可做删除操作',
  `comment_see` int(1) NOT NULL DEFAULT '0' COMMENT '评论模块可见性',
  `comment_u` int(1) NOT NULL DEFAULT '0' COMMENT '评论模块是否可做屏蔽与反屏蔽操作',
  `notice_see` int(1) NOT NULL DEFAULT '0' COMMENT '网站公告可见性',
  `notice_c` int(1) NOT NULL DEFAULT '0' COMMENT '网站公告是否可增加操作',
  `notice_u` int(1) NOT NULL DEFAULT '0' COMMENT '网站公告是否可修改操作',
  `notice_d` int(1) NOT NULL DEFAULT '0' COMMENT '网站公告是否可删除操作',
  `link_see` int(1) NOT NULL DEFAULT '0' COMMENT '友情链接模块可见性',
  `link_c` int(1) NOT NULL DEFAULT '0' COMMENT '友情链接模块是否可新增',
  `link_u` int(1) NOT NULL DEFAULT '0' COMMENT '友情链接模块是否可修改',
  `link_d` int(1) NOT NULL DEFAULT '0' COMMENT '友情链接模块是否可删除',
  `sentence_see` int(1) NOT NULL DEFAULT '0' COMMENT '每日一句是否可见',
  `sentence_c` int(1) NOT NULL DEFAULT '0' COMMENT '每日一句是否可做增加操作',
  `sentence_u` int(1) NOT NULL DEFAULT '0' COMMENT '每日一句是否可做修改操作',
  `sentence_d` int(1) NOT NULL DEFAULT '0' COMMENT '每日一句是否可做删除操作',
  `visit_see` int(1) NOT NULL DEFAULT '0' COMMENT 'ip记录模块可见性',
  `click_see` int(1) NOT NULL DEFAULT '0' COMMENT '访问量可见性',
  `creator_id` int(11) NOT NULL COMMENT '创建者',
  `creat_time` int(11) NOT NULL COMMENT '创建时间',
  `reviser_id` int(11) NOT NULL COMMENT '最后修改者id',
  `revise_time` int(11) NOT NULL COMMENT '最后修改时间',
  PRIMARY KEY (`id`),
  KEY `notice_see` (`notice_see`),
  KEY `notice_c` (`notice_c`),
  KEY `notice_u` (`notice_u`),
  KEY `notice_d` (`notice_d`),
  KEY `permission_level_id` (`permission_level_id`)
) ENGINE=MyISAM AUTO_INCREMENT=161 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_sentence`
--

DROP TABLE IF EXISTS `blog_sentence`;
CREATE TABLE IF NOT EXISTS `blog_sentence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sentence` varchar(64) NOT NULL COMMENT '中文句子',
  `other_sentence` varchar(128) NOT NULL COMMENT '其他语言句子',
  `show` int(1) NOT NULL DEFAULT '0' COMMENT '是否显示',
  `time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=48 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_tag`
--

DROP TABLE IF EXISTS `blog_tag`;
CREATE TABLE IF NOT EXISTS `blog_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL COMMENT '标签名字',
  `article_count` int(11) NOT NULL DEFAULT '0' COMMENT '此标签的文章数量',
  `desc` varchar(64) DEFAULT NULL COMMENT 'tag标签备注',
  `time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_visit`
--

DROP TABLE IF EXISTS `blog_visit`;
CREATE TABLE IF NOT EXISTS `blog_visit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` char(15) NOT NULL DEFAULT '' COMMENT 'ip地址',
  `os` char(8) NOT NULL DEFAULT '' COMMENT '操作系统',
  `px` char(9) NOT NULL DEFAULT '' COMMENT '客户端屏幕分辩率',
  `city` varchar(64) NOT NULL DEFAULT '' COMMENT '城市',
  `country` varchar(64) NOT NULL DEFAULT '' COMMENT '国家',
  `code` varchar(12) NOT NULL DEFAULT '' COMMENT '客户端所使用的语言',
  `isp` varchar(128) NOT NULL DEFAULT '' COMMENT '网络供应商',
  `as` varchar(128) NOT NULL DEFAULT '' COMMENT '网络线路',
  `lon` float(7,4) DEFAULT NULL COMMENT '经度',
  `lat` float(6,4) DEFAULT NULL COMMENT '纬度',
  `timezone` varchar(64) NOT NULL DEFAULT '' COMMENT '时区',
  `time` int(10) DEFAULT NULL COMMENT '记录时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=31 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `blog_wxmessage`
--

DROP TABLE IF EXISTS `blog_wxmessage`;
CREATE TABLE IF NOT EXISTS `blog_wxmessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `openid` varchar(255) DEFAULT NULL,
  `content` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
