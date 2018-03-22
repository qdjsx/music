<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Songs as SongsModel;
use app\admin\controller\Page;


require_once __DIR__ . '../../../../php-sdk-master/autoload.php'; 
use Qiniu\Storage\UploadManager;
use Qiniu\Auth;


class Songs extends Controller
{
	//==========	歌曲列表	
	public function songlist()
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
		if (!in_array('/admin/songs/songlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_songs')->count('id');
		$page = new Page(3,$count);
		$res  = Db::table('mc_songs')->limit($page->limit())->order('id')->select();
		$this->assign('res',$res);
		$artist = Db::table('mc_artist')->field('id,name')->select();
		$this->assign('artist',$artist);
		$album = Db::table('mc_album')->field('id,name')->select();
		$this->assign('album',$album);

		$re = $page->allPage();
		$this->assign('re',$re);
		$this->assign('count',$count);
		return $this->fetch();
	}



	//=========歌曲修改
	public function songsedit(){
		$id = request()->get('id');
		// dump($id);
		$res = Db::table('mc_songs')->where('id',$id)->select();
		$this->assign('res',$res);
		$artist_id = $res[0]['artist_id'];
		$artist = Db::table('mc_artist')->where('id',$artist_id)->value('name');
		$this->assign('artist',$artist);
		$album_id = $res[0]['album_id'];
		$album = Db::table('mc_album')->where('id',$album_id)->value('name');
		$this->assign('album',$album);
		$albumall = Db::table('mc_album')->where('artist_id',$artist_id)->select();
		$this->assign('albumall',$albumall);
		return $this->fetch();
	}
	//歌曲更改，
	public function songsxiu(){
		$id = request()->get('id');
		$name = request()->post('namename');
		$album = request()->post('zhuanji');
		$score = request()->post('score');
		$gold = request()->post('gold');
		// 获取文件
		$file = request()->file('files');
		if (!empty($file)) {
			// dump($file);
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'song' );
			$data = '/static/uploads/song/' . $info->getSaveName();
			//获取别的值
		}else {
			$data = Db::table('mc_songs')->where('id',$id)->value('cover_url');
		}
		//专辑id		
		$album_id = Db::table('mc_album')->where('name',$album)->value('id');
		if (empty($album_id)) {
			$album_id = 0;
		}
		$res = SongsModel::where('id',$id)->update(['cover_url'=>$data,'name'=>$name,'album_id'=>$album_id,'score'=>$score,'gold'=>$gold]);
		if ($res) {
			echo "<script>alert('修改成功');parent.layer.close(parent.layer.getFrameIndex(window.name)); window.parent.location.reload(); parent.layer.closeAll('iframe'); </script>";
			exit;
		}else {
			echo "<script>alert('修改失败');parent.layer.close(parent.layer.getFrameIndex(window.name)); </script>";
			exit;
		}
			
	}


	//==========歌曲删除
	public function del_song(){
		$sid = request()->post('sid');
		$del = SongsModel::where('id',$sid)->delete();
		if ($del) {
			echo json_encode(['status'=>1]);		
		}else{
			echo json_encode(['status'=>0]);		
		}

	}
	//歌曲删除 多删
	public function del_all(){
		if (empty($_GET['all'])) {
			return json_encode(['status'=>0]);		
		}
		foreach ($_GET['all'] as $key => $value) {
            $del =Db::table('mc_songs')->delete($value);
        }
		if ($del) {
			return json_encode(['status'=>1]);		
		}else{
			return json_encode(['status'=>0]);		
		}
	}
	//歌曲评论
	public  function songpost(){
		$sid = input('param.id');

		if(!empty($sid))
		{
			$count = Db::table('mc_songpost s, mc_user u')->where("u.uid=s.uid and sid=$sid")->count();

			$page = new Page(3,$count);

			$res = Db::table('mc_songpost s, mc_user u')->where("u.uid=s.uid and sid=$sid")->limit($page->limit())->select();
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
		$res = Db::table('mc_songpost')->where('id',$id)->delete();

		
		if($res)
		{
			return json_encode(['status' => 1]);
		}else
		{
			return json_encode(['status' => 0]);
		}
	}
	///多删歌曲评论
	public function del_allsongpost()
	{
		if (empty($_GET['all'])) {
			return json_encode(['status'=>0]);		
		}
		foreach ($_GET['all'] as $key => $value) {
            $del =Db::table('mc_songpost')->delete($value);
        }
		if ($del) {
			return json_encode(['status'=>1]);		
		}else{
			return json_encode(['status'=>0]);		
		}
	}


	//////////////歌曲上穿


	public function upload()
    {
        // 上传七牛云需要的参数
        $accessKey = "aVH2o8M48zq29rdytzKuZs4DWE5CAa72a5NB1G8T";
        $secretKey = "3f_HkXvm9tgx8OhHvYUQ7fXT_mqgPZ1tGYlbEJVe";
        $bucketName = "music"; // 空间名称
        // 对上传策略的修改，返回的文件名直接跳转到php上 上传策略
        $policy = [
            'returnUrl'=>'http://music.zhuchenxij.top/admin/songs/cl_upload', // 返回信息的接收地址
            'returnBody'=>'{"key": $(key), "mimeType": $(mimeType) , "avinfo":$(avinfo)}', // 返回的视频文件信息
            'mimeLimit'=>'audio/x-mpeg;audio/mpeg;application/*' // 只允许上传视频格式的文件
        ];
        // 对token的获取
        $upManager = new UploadManager();
        $auth = new Auth($accessKey, $secretKey);
        $token = $auth->uploadToken($bucketName , null , 3600 , $policy);
        list($ret, $error) = $upManager->put($token, 'formput', 'hello world');
        // 将token传给前台
       	$this->assign(['token'=>$token]);
        return $this->fetch();
    }
    //歌曲上传成功后进入的页面，如果存在upload_ret，才会对你展示添加页面
	public function cl_upload(){
		if (empty($_GET['upload_ret'])) {
			echo "<script>alert('上传失败,请重新上传');window.location.href = '/admin/songs/upload';</script>";
			die;
		}
		$up =$_GET['upload_ret'];
 // dump(json_decode(base64_decode($up),true));
 		
 		$all = json_decode(base64_decode($up),true);
 		//歌曲路径  歌曲的路径
 		$music_url = ' http://p3pw8j9d0.bkt.clouddn.com/' .$all['key'];
 		$this->assign('url',$music_url);
 // dump($music_url);

 		return $this->fetch();
	}
	//增加歌曲详细信息
	public function add_song(){
		$music_url = request()->post('music_url');
		//歌名
		$songs_name = request()->post('songs_name');
		//歌手
		$artist_name  = request()->post('artist_name');
		//专辑
		$album_name = request()->post('album_name');
		//封面
		$file = request()->file('files');
		//处理封面
		if (!empty($file)) {
			//封面
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'song' );
			//获取cover_url
			$cover_url = '/static/uploads/song/' . $info->getSaveName();
		} else{
			$cover_url = '';
		}
		//先判断歌手是否已经存在
		$sel_artist_id = Db::table('mc_artist')->where('name',$artist_name)->value('id');
		if (!empty($sel_artist_id)) {
			//填写歌手不是新歌手，而且你还写专辑了
			if (!empty($album_name)) {
				//判断专辑是否已经存在
				$sel_album_id = Db::table('mc_album')->where("artist_id=$sel_artist_id and name='$album_name'")->value('id');
				if(!empty($sel_album_id)){
					$data =[
						'name'=>$songs_name,
						'music_url'=>$music_url,
						'cover_url'=>$cover_url,
						'artist_id'=>$sel_artist_id,
						'album_id'=>$sel_album_id,
					];
					//插入旧歌手的，旧专辑的新歌
					$a = Db::table('mc_songs')->insert($data);
					if ($a) {
						echo "<script>alert('旧歌手旧专辑成功');window.location.href = '/admin/songs/upload';</script>";
						exit;
					}else{
						echo "<script>alert('旧歌手旧专辑失败');window.location.href = '/admin/songs/upload';</script>";
						exit;
					}
				}
				//增加旧歌手，的新专辑里面的新歌
				//先增加新专辑，
				$data = [
					'artist_id'=>$sel_artist_id,
					'name'=>$album_name,
				];
				$album_id = Db::table('mc_album')->insertGetId($data);
				//在增加新歌
				$data1 =[
					'name'=>$songs_name,
					'music_url'=>$music_url,
					'cover_url'=>$cover_url,
					'artist_id'=>$sel_artist_id,
					'album_id'=>$album_id,
				];
				$a = Db::table('mc_songs')->insert($data1);
				if ($a && $album_id) {
					echo "<script>alert('旧歌手新专辑成功');window.location.href = '/admin/songs/upload';</script>";
					exit;
				}else{
					echo "<script>alert('旧歌手新专辑失败');window.location.href = '/admin/songs/upload';</script>";
					exit;
				}
			}
			//填写歌手不是新歌手,你没写专辑
			$data2 =[
					'name'=>$songs_name,
					'music_url'=>$music_url,
					'cover_url'=>$cover_url,
					'artist_id'=>$sel_artist_id,
			];
			$a= Db::table('mc_songs')->insert($data2);
			if ($a) {
				echo "<script>alert('旧歌手无专辑成功');window.location.href = '/admin/songs/upload';</script>";
				exit;
			}else{
				echo "<script>alert('旧歌手无专辑失败');window.location.href = '/admin/songs/upload';</script>";
				exit;
			}
			
		}else{
			//如果你写的歌手是新歌手
			$new_artist_id = Db::table('mc_artist')->insertGetId(['name'=>$artist_name]);
			//你还写专辑了
			if (!empty($album_name)) {
				//增加专辑
				$data3 = [
					'artist_id'=>$new_artist_id,
					'name'=>$album_name,
				];
				$new_album_id = Db::table('mc_album')->insertGetId($data3);				
				$data4 =[
					'name'=>$songs_name,
					'music_url'=>$music_url,
					'cover_url'=>$cover_url,
					'artist_id'=>$new_artist_id,
					'album_id'=>$new_album_id,
				];
				//插入新歌手的，旧专辑的新歌
				$a = Db::table('mc_songs')->insert($data4);
				if ($a) {
					echo "<script>alert('新歌手新专辑成功');window.location.href = '/admin/songs/upload';</script>";
					exit;
				}else{
					echo "<script>alert('新歌手新专辑失败');window.location.href = '/admin/songs/upload';</script>";
					exit;
				}

			}
			//你没有写专辑
			//增加新歌，
			$data5 =[
				'name'=>$songs_name,
				'music_url'=>$music_url,
				'cover_url'=>$cover_url,
				'artist_id'=>$new_artist_id,
			];
			//插入新歌手的，旧专辑的新歌
			$a = Db::table('mc_songs')->insert($data5);
			if ($a) {
				echo "<script>alert('新歌手无专辑成功');window.location.href = '/admin/songs/upload';</script>";
				exit;
			}else{
				echo "<script>alert('新歌手无专辑失败');window.location.href = '/admin/songs/upload';</script>";
				exit;
			}
		}
		
	}    
}
