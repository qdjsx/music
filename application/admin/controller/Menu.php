<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Menu as MenuModel;
use app\admin\controller\Page;
class Menu extends Controller
{
	public function menulist(){
		//方跳墙
		$ppp = session('pername');
		// dump($ppp);
		if (empty($ppp)) {
			echo "<script>alert('你还没登陆');top.location='/admin/permission/login';</script>";
			exit;
		}
		$mmvc = [];
		foreach ($ppp as $key => $value) {
			$mmvc[]=$value['mvc'];
		}
		if (!in_array('/admin/menu/menulist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_menu')->count('id');
		$page = new Page(3,$count);

		$menu  = Db::table('mc_menu')->limit($page->limit())->order('id')->select();
		$this->assign('menu',$menu);
		//创始人
		$re = $page->allPage();
		$this->assign('re',$re);
		$artist = Db::table('mc_user')->field('uid,username')->select();
		$this->assign('artist',$artist);
		//收藏的人
		$user = Db::table('mc_menu m,mc_user u,mc_user_menu um')->where('um.menu_id=m.id and um.uid=u.uid')->field('u.username,um.menu_id')->select();
		$this->assign('user',$user);
		//歌曲
		$song = Db::table('mc_menu m,mc_songs s,mc_menu_songs ms')->where('ms.menu_id=m.id and ms.songs_id=s.id')->field('s.name,ms.menu_id')->select();
		$this->assign('song',$song);
		$genre = Db::table('mc_menu m,mc_genre g,mc_menu_genre mg')->where('mg.menu_id=m.id and mg.genre_id=g.id')->field('g.name,mg.menu_id')->select();
		$this->assign('genre',$genre);
		$this->assign('count',$count);
		return $this->fetch();

	}
	//删除歌单，仅仅删除歌单以及   用户歌单表，歌单歌曲表，歌单类型  这三个中间表 
	public function del_menu(){
		//获取歌单id
		$mid = request()->post('mid');
		//删除用户歌单表，用户收藏的歌单
		Db::table('mc_user_menu')->where('menu_id',$mid)->delete();
		//删除歌单歌曲表
		Db::table('mc_menu_songs')->where('menu_id',$mid)->delete();
		//删除歌单类型表
		Db::table('mc_menu_genre')->where('menu_id',$mid)->delete();

		//删除歌单
		$del = Db::table('mc_menu')->where('id',$mid)->delete();
		if ($del) {
			echo json_encode(['status'=>1]);		
		}else{
			echo json_encode(['status'=>0]);		
		}
	}



}	