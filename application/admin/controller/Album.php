<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Album as AlbumModel;
use app\admin\controller\Page;
class Album extends Controller
{
	//专辑展示
	public function albumlist(){
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
		if (!in_array('/admin/album/albumlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_album')->count('id');
		$page = new Page(5,$count);
		$album  = Db::table('mc_album')->limit($page->limit())->order('id')->select();

		$this->assign('album',$album);
		$artist = Db::table('mc_artist')->field('name,id')->select();
		$this->assign('artist',$artist);
		$song = Db::table('mc_songs')->field('name,album_id')->select();
		$this->assign('song',$song);
		$this->assign('count' , $count);
		$re = $page->allPage();
		$this->assign('re',$re);
		return $this->fetch();
	}
	//专辑修改页面展示
	public function albumedit(){
		$id = request()->get('id');
		// dump($id);
		$res = Db::table('mc_album b,mc_artist a')->where("b.id=$id and b.artist_id=a.id")->field('b.name bname,a.name aname,b.cover_url')->select();
		$this->assign('res',$res);
		return $this->fetch();
	}
	//修改专辑封面，专辑名称后面写
	public function albumxiu(){
		$id = request()->get('id');
		$name = request()->post('name');
		// 获取文件
		$file = request()->file('files');
		if (!empty($file)) {
			// dump($file);
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'album' );
			$data = '/static/uploads/album/' . $info->getSaveName();
			//获取别的值
		}else{
			$data = Db::table('mc_album')->where('id',$id)->value('cover_url');
		}
	
		$res = AlbumModel::where('id',$id)->update(['cover_url'=>$data,'name'=>$name]);
		if ($res) {
			echo "<script>alert('修改成功');parent.layer.close(parent.layer.getFrameIndex(window.name)); window.parent.location.reload(); parent.layer.closeAll('iframe'); </script>";
			exit;
		}else {
			echo "<script>alert('修改失败');parent.layer.close(parent.layer.getFrameIndex(window.name)); </script>";
			exit;
		}
	}
	//专辑单删
	public function del_album(){
		//获取id
		$aid = request()->post('aid');
		//删除专辑所有的歌曲，同时删除用户歌曲表的歌曲
		$song = Db::table('mc_songs')->where('album_id',$aid)->select();
		if (!empty($song)) {
			foreach ($song as $key => $value) {
				$del_song = Db::table('mc_songs')->where('id',$value['id'])->delete();
				$del_ms = Db::table('mc_menu_songs')->where('songs_id',$value['id'])->delete();
			}
		}
		//删除专辑
		$del = AlbumModel::where('id',$aid)->delete();
		if ($del) {
			echo json_encode(['status'=>1]);		
		}else{
			echo json_encode(['status'=>0]);		
		}
	}



	//专辑评论
	public  function albumpost(){
		$sid = input('param.id');

		if(!empty($sid))
		{
			$count = Db::table('mc_albumpost s, mc_user u')->where("u.uid=s.uid and aid=$sid")->count();

			$page = new Page(3,$count);

			$res = Db::table('mc_albumpost s, mc_user u')->where("u.uid=s.uid and aid=$sid")->limit($page->limit())->select();
			$allPage = $page->allPage();

			$this->assign('res' , $res);
			$this->assign('count' , $count);
			$this->assign('re' , $allPage);
		}	
		return $this->fetch();
	}
	//评论删除
	public function del_post()
	{
		$id = request()->post('spid');
		$res = Db::table('mc_albumpost')->where('id',$id)->delete();

		
		if($res)
		{
			return json_encode(['status' => 1]);
		}else
		{
			return json_encode(['status' => 0]);
		}
	}
	///多删歌曲评论
	public function del_allalbumpost()
	{
		if (empty($_GET['all'])) {
			return json_encode(['status'=>0]);		
		}
		foreach ($_GET['all'] as $key => $value) {
            $del =Db::table('mc_albumpost')->delete($value);
        }
		if ($del) {
			return json_encode(['status'=>1]);		
		}else{
			return json_encode(['status'=>0]);		
		}
	}


}