<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Artist as ArtistModel;
use app\admin\controller\Page;
class Artist extends Controller
{
	// ~~~~~~~		歌手列表	~~~~~~~~~
	public function artistlist()
	{
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
		if (!in_array('/admin/artist/artistlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_artist')->count('id');
		$page = new Page(3,$count);
		$artist  = Db::table('mc_artist')->limit($page->limit())->order('id')->select();
		$this->assign('artist' , $artist);
		$album = Db::table('mc_album')->field('artist_id,name')->select();
		$this->assign('album' , $album);
		$song = Db::table('mc_songs')->field('artist_id,name sname')->select();
		$this->assign('song' , $song);
		$this->assign('count' , $count);
		$re = $page->allPage();
		$this->assign('re',$re);
		return $this->fetch();
	}
	//歌曲修改 页面
	public function artistedit()
	{
		$id = request()->get('id');
		// dump($id);
		$res = ArtistModel::where('id',$id)->select();
		$this->assign('res',$res);
		return $this->fetch();
		return $this->fetch();
	}
	//修改歌曲的封面，修改歌曲的歌名，后面写
	public function artistxiu(){
		$id = request()->get('id');
		$name = request()->post('name');
		// 获取文件
		$file = request()->file('files');
		if (!empty($file)) {
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'artist' );
			$data = '/static/uploads/artist/' . $info->getSaveName();
		}else {
			$data = Db::table('mc_artist')->where('id',$id)->value('cover_url');
		}

		$res = ArtistModel::where('id',$id)->update(['cover_url'=>$data,'name'=>$name]);
		if ($res) {
			echo "<script>alert('修改成功');parent.layer.close(parent.layer.getFrameIndex(window.name)); window.parent.location.reload(); parent.layer.closeAll('iframe'); </script>";
			exit;
		}else {
			echo "<script>alert('修改失败');parent.layer.close(parent.layer.getFrameIndex(window.name)); </script>";
			exit;
		}
	}

	//删除歌手，删除一首  
	public function del_artist(){
		//获取id
		$aid = request()->post('aid');
		//删除歌手所有的歌曲
		$song = Db::table('mc_songs')->where('artist_id',$aid)->select();
		if (!empty($song)) {
			foreach ($song as $key => $value) {
				$del_song = Db::table('mc_songs')->where('id',$value['id'])->delete();
				$del_ms = Db::table('mc_menu_songs')->where('songs_id',$value['id'])->delete();
			}
		}
		//删除歌手所有专辑
		$album = Db::table('mc_album')->where('artist_id',$aid)->select();
		if (!empty($album)) {
			foreach ($album as $key => $v) {
				$del_album = Db::table('mc_album')->where('id',$v['id'])->delete();
			}
		}
		//删除歌手
		$del = ArtistModel::where('id',$aid)->delete();
		if ($del) {
			echo json_encode(['status'=>1]);		
		}else{
			echo json_encode(['status'=>0]);		
		}
	}
}