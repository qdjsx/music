<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\controller\Page;
use think\Session;


require_once __DIR__ . '../../../../php-sdk-master/autoload.php'; 

use Qiniu\Auth;
// 引入上传类
use Qiniu\Storage\UploadManager;
// 需要填写你的 Access Key 和 Secret Key

class Mv extends Controller
{	
	// MV列表 
	public function mvlist()
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
		if (!in_array('/admin/mv/mvlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}


		$num = Db::name('mv')->count();
		$page = new Page(3,$num);
		$mv = Db::name('mv')->limit($page->limit())->select();
		$allPage = $page->allPage();
		
		$this->assign('mv' , $mv);
		$this->assign('num' , $num);
		$this->assign('allPage' , $allPage);
		return $this->fetch();
	}

	// ~~~~~~~~~~~~~		添加mv		~~~~~~~~	
	public function addmv()
	{
		return $this->fetch();
	}

	// 逐个删除mv
	public function delmv()
	{
		$vid = input('param.vid');
		// var_dump($vid);
		$res = Db::name('mv')->delete($vid);

		$isExists = DB::name('mvpost')->where("vid=$vid")->select();
		if($isExists)
		{
			$result = DB::name('mvpost')->where("vid=$vid")->delete();
		}

		if($res)
		{
			return json_encode(['state' => 1]);
		}else
		{
			return json_encode(['state' => 0]);
		}
	}

	//批量删除mv
	public function delall()
	{
        foreach ($_GET['all'] as $key => $value) {
            $res =Db::name('mv')->delete($value);

            $isExists = DB::name('mvpost')->where("vid=$value")->select();
            if($isExists)
            {	
            	$result = DB::name('mvpost')->where("vid=$value")->delete();
            }
        }

		if($res)
		{
			return json_encode(['state' => 1]);
		}else
		{
			return json_encode(['state' => 0]);
		}

	}

	// 修改mv页面
	public function editmv()
	{
		$vid = input('get.vid');
		$info = Db::name('mv')->where("vid=$vid")->select();
		$this->assign('info' , $info);
		return $this->fetch();
	}

	//执行修改mv
	public function doeditmv()
	{
		$vid = input('post.vid');
		// $orignInfo = Db::name('mv')->where("vid=$vid")->select();

		$file = request()->file('file');
		if (!empty($file)) {
			// dump($file);
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'mv' );
			$data['pic'] = '/static/uploads/mv/' . $info->getSaveName();
			//获取别的值
		}else {
			$data['pic'] = Db::table('mc_mv')->where('vid',$vid)->value('pic');
		}
		$data['vname'] = input('post.vname');
		$data['vsinger'] = input('post.vsinger');
		$data['vintro'] = input('post.vintro');

		$data['vid'] = $vid;
		$data['update_time'] = time();

		$res = Db::name('mv')->update($data);

		if ($res) {
			echo "<script>alert('修改成功');parent.layer.close(parent.layer.getFrameIndex(window.name)); window.parent.location.reload(); parent.layer.closeAll('iframe'); </script>";
			exit;
		}else {
			echo "<script>alert('修改失败');parent.layer.close(parent.layer.getFrameIndex(window.name)); </script>";
			exit;
		}
	}

	// 添加MV
   // 添加MV
    function doupload()
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
		if (!in_array('/admin/mv/mvlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

        $accessKey ="aVH2o8M48zq29rdytzKuZs4DWE5CAa72a5NB1G8T";
        $secretKey = "3f_HkXvm9tgx8OhHvYUQ7fXT_mqgPZ1tGYlbEJVe";
        $bucket = "music";
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 生成上传 Token
        $token = $auth->uploadToken($bucket);
        // 要上传文件的本地路径
        // $filePath = './php-logo.png';
        $filePath = $_FILES['mvmv']['tmp_name'][0];
        // dump($filePath);
        // 上传到七牛后保存的文件名
        $key = $_FILES['mvmv']['name'][0];




        // 初始化 UploadManager 对象并进行文件的上传。
        $uploadMgr = new UploadManager();
        // 调用 UploadManager 的 putFile 方法进行文件的上传。
        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);


        if($ret['key']){


        	$url="http://p3nictroy.bkt.clouddn.com/".$ret['key'];
        }


		$data['url'] = $url;
		$file = request()->file('mvmv');
		
		$file = $file[1];


		if(!empty($file)){
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
			$pic= '/uploads/' . $info->getSaveName();


		}
	
		$vname = input('post.vname');


		if(empty($vname))
		{
			exit ("<script>alert('请添加mv名字');history.go(-1);</script>");
		}else
		{
			$isExists = Db::name('mv')->where("vname='$vname'")->select();
			if($isExists)
			{
				exit ("<script>alert('mv已存在');history.go(-1);</script>");
			}
		}


		$data['vname'] = $vname;
		$data['vsinger'] = input('post.singer');


		// 上传mv之后返回的url	????????????
		


		if(!empty(input('post.remark')))
		{
			$data['vintro'] = input('post.remark');
		}


		$data['create_time'] = time();


		$aff_id = Db::name('mv')->insertGetId($data);


		if($aff_id)
		{	
			exit ("<script>alert('添加mv成功');history.go(-1);</script>");
		}else
		{
			exit ("<script>alert('添加mv失败');history.go(-1);</script>");
		}
    }
    //////
    //歌曲评论
	public  function mvpost(){
		$sid = input('param.id');

		if(!empty($sid))
		{
			$count = Db::table('mc_mvpost s, mc_user u')->where("u.uid=s.uid and vid=$sid")->count();

			$page = new Page(3,$count);

			$res = Db::table('mc_mvpost s, mc_user u')->where("u.uid=s.uid and vid=$sid")->limit($page->limit())->select();
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
		$res = Db::table('mc_mvpost')->where('id',$id)->delete();

		
		if($res)
		{
			return json_encode(['status' => 1]);
		}else
		{
			return json_encode(['status' => 0]);
		}
	}
	///多删歌曲评论
	public function del_allmvpost()
	{
		if (empty($_GET['all'])) {
			return json_encode(['status'=>0]);		
		}
		foreach ($_GET['all'] as $key => $value) {
            $del =Db::table('mc_mvpost')->delete($value);
        }
		if ($del) {
			return json_encode(['status'=>1]);		
		}else{
			return json_encode(['status'=>0]);		
		}
	}
}