<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\User as UserModel;
use app\admin\controller\Page;
class User extends Controller
{
	//用户列表
	public function userlist()
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
		if (!in_array('/admin/user/userlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}
		////
			// 搜索内容
		$key = input('param.username');
		if(!empty($key))
		{
			$count = Db::table('mc_user')->where("username like '%$key%' and udertype
				=0")->count();
			$page = new Page(5,$count);
			$res = Db::name('user')->where("username like '%$key%' and udertype=0")->limit($page->limit())->select();
			$allPage = $page->allPage();
		}else{
			///
			$count = Db::table('mc_user')->where('udertype', 0)->count('uid');
			$page = new Page(5,$count);
			$res  = Db::table('mc_user')->where('udertype', 0)->limit($page->limit())->order('uid')->select();
			$allPage = $page->allPage();			
		}			
		$this->assign('res',$res);
		$this->assign('re',$allPage);
		$this->assign('count',$count);
		return $this->fetch();
	}
	//删除用户
	public function del_user()
	{
		$uid = request()->post('uid');
		$del = UserModel::where('uid',$uid)->delete();
		if ($del) {
			echo json_encode(['status'=>1]);		
		}else{
			echo json_encode(['status'=>0]);		
		}
	}
	//多删除
	public function del_all()
	{
	
        foreach ($_GET['all'] as $key => $value) {
            $del =Db::table('mc_user')->delete($value);
        }
		if ($del) {
			return json_encode(['status'=>1]);		
		}else{
			return json_encode(['status'=>0]);		
		}
	}
	//改，用户启用  1是锁定
	public function user_start()
	{
		$uid = input('post.uid');
		$guid = Db::table('mc_user')->where('uid' , $uid)->update(['allowlogin'=>0]);
		if ($guid) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}
	}
	//用户禁用 
	public function user_storp()
	{
		$uid = input('post.uid');
		$guid = Db::table('mc_user')->where('uid' , $uid)->update(['allowlogin'=>1]);
		if ($guid) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}
	}
}