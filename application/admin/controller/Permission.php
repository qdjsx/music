<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Permission as PermissionModel;
use app\admin\controller\Code;
use app\admin\controller\Page;
class Permission extends Controller
{
	// static public $treeList = [];

	// 1============================管理员列表99页面
	public function adminlist(){
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
		if (!in_array('/admin/permission/adminlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_user')->where('udertype', 1)->count('uid');
		$page = new Page(5,$count);
			//遍历所有的管理员
		$data  = Db::table('mc_user')->where('udertype', 1)->limit($page->limit())->order('uid')->select();
		$re = $page->allPage();
		$this->assign('re',$re);
		//遍历管理员的角色
		$ret = Db::table('mc_user u,mc_role r,mc_user_role ur')->where('ur.uid = u.uid and ur.rid = r.rid')->field('r.rname,u.uid')->select();
		// dump($ret);
		$this->assign('ret',$ret);
		$this->assign('data',$data);
		//总数
		$this->assign('count',$count);
		return  $this->fetch(); 
	}

	//=====1-1===管理员添加 增99页面
	public function adminadd(){
		//遍历所有角色
		$res = Db::table('mc_role')->select();
		$this->assign('res',$res);
		return $this->fetch();
	}
	//增加管理员的时候，判断用户名是不是已经存在
	public function useruser(){
		$username = request()->post('username');
		//查询看，是否已经存在，
		$sel = Db::table('mc_user')->where('username' , $username)->select();
		if ($sel) {
			echo json_encode(['status'=>0]);
			
		}else{
			echo json_encode(['status'=>1]);
		}
	}
	///增加 ajax传值的
	public function do_add_user()
	{
		$username = request()->post('username');
		$phone = request()->post('phone');
		$email = request()->post('email');
		$role = request()->post('role');
		$pass = request()->post('pass');
		$repass = request()->post('repass');
		//判断为空，密码是不是一样，会自动判断，看看js是不是自动判断

		$code = new Code();
		$pass = $code->jiami($pass);
		$data = [
			'username'=> $username,
			'phone'=> $phone,
			'email'=> $email,
			'phone'=> $phone,
			'password'=> $pass,
			'udertype'=> 1,	
		];
		//查询看，是否已经存在，这一块，我在html已经进行判断了。
		// $sel = Db::table('mc_user')->where('username' , $username)->select();
		// if ($sel) {
		// 	echo json_encode(['status'=>5]);
		// 	die;
		// }
		//进行添加用户表  1添加成功， 2添加失败
		$uid = Db::table('mc_user')->insertGetId($data);
		//根据角色表，查出角色id
		$rid = Db::table('mc_role')->where('rname',$role)->select();
		//如果不为空的话，进行添加角色
		if (!empty($rid)) {
			//新增管理员，得加入用户角色表
			$data1 = [
				'uid'=> $uid,
				'rid'=> $rid[0]['rid'],
			];
			$add_ur = Db::table('mc_user_role')->insert($data1);
			if ($uid && $add_ur) {
				echo json_encode(['status'=>1]);
			} else {	
				echo json_encode(['status'=>0]);
			}
		}else {
			if ($uid) {
				echo json_encode(['status'=>1]);
			} else {	
				echo json_encode(['status'=>0]);
			}
		}
		
		
	}




	//========1-2删除管理员
	public function del_admin(){
		$uid = input('post.uid');
		//删除角色表
		$del = Db::table('mc_user')->delete($uid);
		//删除角色权限表
		$sel = Db::table('mc_user_role')->where('uid',$uid)->select();
		if ($sel) {
			$delrp = Db::table('mc_user_role')->where('uid' , $uid)->delete();
		} else{
			$delrp = 1;
		}
		if ($del && $delrp) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}
	}

	//======1-3管理员编辑 ，修改  99页面
	public function adminedit()
	{	//遍历信息
		$uid = request()->get('uid');
		$res = Db::table('mc_user')->where('uid',$uid)->select();
		$this->assign('res',$res);

		//遍历所有角色
		$ress = Db::table('mc_role')->select();
		$this->assign('ress',$ress);
		//遍历默认的角色
		$moren = Db::table('mc_role r,mc_user_role ur')->where("ur.rid = r.rid and ur.uid = $uid")->select();
		// dump($moren);
		if (!empty($moren)) {
			$moren = $moren[0]['rname'];
			$this->assign('moren',$moren);
		}
		
		return $this->fetch();
	}
	//修改管理员的时候，判断用户名是不是已经存在,这里首先判断，你用户名是不是修改的
	public function useruserxiu(){
		//如果你修改过用户名，才会进行判断
		$uid = request()->post('uid');
		$data = Db::table('mc_user')->where('uid' , $uid)->value('username');
		$username = request()->post('username');
		// 1是可用，你没修改过用户名
		if ($data == $username) {
			echo json_encode(['status'=>1]); 
			die;
		}
		//查询看，是否已经存在，你已经修改过
		$sel = Db::table('mc_user')->where('username' , $username)->select();
		if ($sel) {
			echo json_encode(['status'=>0]);			
		}else{
			echo json_encode(['status'=>1]);
		}
	}
	///进行提交修改 //管理员编辑方法
	public function do_adminedit()
	{
		$uid = request()->post('uid');
		$username = input('post.username');
		$phone = input('post.phone');
		$email = input('post.email');
		$role = input('post.role');  //角色名,我要改成角色id
		$pass = input('post.pass');
		//密码操作
		$code = new Code();
		$pass = $code->jiami($pass);
		$data = [
			'username'=>$username,
			'phone'=>$phone,
			'email'=>$email,
			'password'=>$pass,
		];
		//修改用户表
		$update = Db::table('mc_user')->where('uid',$uid)->update($data);
		
		//进行角色修改
		//如果改为0的话，就不给你角色
		//判断用户的角色有没有改变，先查询旧的角色id
		$oldrid = Db::table('mc_user_role')->where('uid',$uid)->value('rid');
		if ($oldrid !=$role) {
			//进行修改
			///将用户角色表里面的删掉，在新增
			$del_ur = Db::table('mc_user_role')->where('uid',$uid)->delete();
			//新增管理员，得加入用户角色表
			$data1 = [
				'uid'=> $uid,
				'rid'=> $role,
			];
			$add_ur = Db::table('mc_user_role')->insert($data1);
			if ($add_ur) {
				echo json_encode(['status'=>1]);
			}else{
				echo json_encode(['status'=>0]);
			}
		} else{
			if ($update) {
				//成功
				echo json_encode(['status'=>1]);
			}else{
				echo json_encode(['status'=>0]);
			}
		}
	}
	//2=================================================================角色管理
	 //实例化本身，调用 自己的方法，
	//角色列表展示  99页面
	public function adminrole(){
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
		if (!in_array('/admin/permission/adminrole', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count =  Db::table('mc_role')->order('rid')->count('rid');
		$page = new Page(3,$count);
		//查询角色，并进行分页
		$data  = Db::table('mc_role')->limit($page->limit())->order('rid')->select();

		//角色所拥有的权限查询   角色，权限，角色权限，  俩id关联，遍历出来  
		$ret = Db::table('mc_role r,mc_role_per rp,mc_permission p')->where('rp.rid = r.rid  and rp.perid = p.perid')->field('r.rname,p.pername,r.rid')->select();
		$this->assign('ret',$ret);
		$this->assign('data',$data);		
		$this->assign('page',$page);
		//总数
		$this->assign('count',$count);
		$re = $page->allPage();
		$this->assign('re',$re);
		return $this->fetch();
	}
	//=====2-1添加角色展示    99页面
	public function roleadd(){
		return $this->fetch();
	}
	//do_add_role 角色添加操作   ajax增加  这个很厉害：
	public function do_add_role(){
		$rname = request()->post('rname');
		$des = request()->post('des');
		if (empty($rname)||empty($des)) {
			return json_encode(['status'=>0]);
		}
		$data = [
			'rname'=> $rname,
			'des'=> $des	
		];
		//查询看，是否已经存在，
		$sel = Db::table('mc_role')->where('rname' , $rname)->select();
		if ($sel) {
			echo json_encode(['status'=>0]);
			die;
		}
		//进行添加  1添加成功， 2添加失败
		$res = Db::table('mc_role')->insert($data);
		if ($res) {
			echo json_encode(['status'=>1]);
		} else {	
			echo json_encode(['status'=>0]);
		}
	}
	//====2-2删除角色，单次删除,同时删除角色权限表的角色的东西
	public function del_role()
	{
		$rid = input('post.rid');
		//删除角色表
		$del = Db::table('mc_role')->delete($rid);
		//删除角色权限表
		$sel = Db::table('mc_role_per')->where('rid',$rid)->select();
		if ($sel) {
			$delrp = Db::table('mc_role_per')->where('rid' , $rid)->delete();
		} else{
			$delrp = 1;
		}
		if ($del && $delrp) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}

	}
	/////给角色赋予权限，也就是改权限
	///遍历该角色权限的页面  99页面
	public function roleedit()
	{	//获取get到的rid
		$rid = $_GET['rid'];
		//获取整个权限表的信息，用于最外部的foreach
		$res = Db::table('mc_permission')->select();
		//获取这个角色的名字
		$role = Db::table('mc_role')->where('rid' , $rid)->field('rname , des')->select();
		//根据权限表和角色权限表，根据角色权限表，获得角色权限表，这个角色对应的权限，为了periddump($role);	
		$result = Db::table('mc_role_per rp, mc_permission p')->where("rp.rid = $rid and rp.perid = p.perid")->field('p.pername , p.perid')->select();
		//权限表的权限id，这个角色所对应的权限放到这个数组中
		$perid = [];
		foreach ($result as $value) {
			$perid[] = $value['perid'];
		}

		$this->assign('rname' , $role[0]['rname']);
		$this->assign('result' , $result);
		$this->assign('res' , $res);
		$this->assign('perid' , $perid);
		$this->assign('rid' , $rid);

		return $this->fetch();
	}
	//=====2-3修改角色权限操作
	//添加角色权限操作
	public function edit_quanxian()
	{
		$pid = input('post.pid_arr');
		$rid = input('post.rid');
		//将字符串按照直接形式分割
		$pid_arr = explode(',', $pid);
		///根据你传上来的rid角色id 删除角色权限表的响应的  
		$del = Db::table('mc_role_per')->where('rid' , $rid)->delete();
		$del = true;
		if (!$del) {
			echo json_encode(['status'=>0]);
			die;
		}

		$data = [];
		$data2 = [];
		$data3 = [];
		foreach ($pid_arr as $value) {
			//
			$data[] = explode('_', $value);
			foreach ($data as $val) {
				$data2[] = $val[1];
				$data3[] = $val[0];
				$data2 = array_unique($data2);
				$data3 = array_unique($data3);
				$data4 = array_merge($data2 , $data3);
			}
		}
		 $data5 = [];
		 foreach ($data4 as $value){
				$data5[] = array(
					'perid'=>$value,
					'rid'=>$rid
				);	
 		}
		
		$res = Db::table('mc_role_per')->insertAll($data5);

		
		if ($res) {
			echo json_encode(['status'=>1]);
			
		} else {
			echo json_encode(['status'=>0]);
		}
	}



	//3=======================权限分类   99页面
	public function admincate(){
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
		if (!in_array('/admin/permission/admincate', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_permission')->where('pid',0)->count('pid');
		$page = new Page(4,$count);
		$data  = Db::table('mc_permission')->where('pid', 0)->limit($page->limit())->order('pid')->select();
		$this->assign('data',$data);
		//总数
		
		$this->assign('count',$count);
		$re = $page->allPage();
		$this->assign('re',$re);
		return $this->fetch();
	}
	//======3-1增加权限分类，也就是增加最大版块
	public function do_add_cate(){	
		$pername = request()->post('name');
		//查询是否存在一样的
		if (empty($pername)) {
			echo json_encode(['status'=>9]);
			exit;
		}
		$sel = Db::table('mc_permission')->where('pername',$pername)->select();
		if (!empty($sel)) {
			//名字已经存在
			echo json_encode(['status'=>2]);
			exit;
		}

		$cheng = Db::table('mc_permission')->insert(['pername'=>$pername,'pid'=>0]);
		if ($cheng) {
			//成功了
			echo json_encode(['status'=>1]);
			exit;
		}else{
			echo json_encode(['status'=>0]);
			exit;
		}

	} 
	///====3-2删除权限分类
	public function del_cate(){
		$perid = request()->post('perid');
		//查出这个大分类的小分类
		$xiao = Db::table('mc_permission')->where('pid',$perid)->select();
		///不为空的话，将小分类id放在数组里面
		if (!empty($xiao)) {
			foreach ($xiao as $key => $value) {
				$xi[] = $value['perid'];
			}
			//查询在rp表里有没有小分类
			$rpxiao = Db::table('mc_role_per')->where('perid' ,'in', $xi)->select();
			if (!empty($rpxiao)) {
				//rp表里有小分类，一定有大分类，删除大分类
				//删除rp角色权限表的大分类
				Db::table('mc_role_per')->where('perid',$perid)->delete();
				//遍历删rp除角色权限表的所有小分类
				foreach ($rpxiao as $key => $val) {
					Db::table('mc_role_per')->where('perid',$val['perid'])->delete();
				}
			}
			///删除p表里面的小分类
			Db::table('mc_permission')->where('pid',$perid)->delete();
		}
		//查询角色权限表的大分类
		 $rpbig = Db::table('mc_role_per')->where('perid',$perid)->select();
		if (!empty($rpbig)) {
			//rp表里有单独的一个分类，直接删除
			Db::table('mc_role_per')->where('perid',$perid)->delete();
		}
		//删除p里面这个大分类
		$del = Db::table('mc_permission')->where('perid',$perid)->delete();
		

		if ($del) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}
	}
	//=====3-3权限分类名字修改
	//展示修改页面  99页面
	public function cateedit(){
		$perid = request()->get('perid');
		$this->assign('perid',$perid);

		$pername = Db::table('mc_permission')->where('perid',$perid)->value('pername');
		// $pername = $pername[0]['pername'];
		$this->assign('pername',$pername);
		return $this->fetch();
	}

	public function edit_fenlei(){
		$pername = request()->post('pername');
		$perid = request()->post('perid');
		//先判断名字有没有改
		$oldname = Db::table('mc_permission')->where('perid',$perid)->value('pername');
		if ($oldname == $pername) {
			echo json_encode(['status'=>0]);
			die;
		}
		//判断修改的名字是否重复，
		$pernn = Db::table('mc_permission')->where('pername',$pername)->select(); 
		if ($pernn) {
			echo json_encode(['status'=>0]);
			die;
		}
		$up = Db::table('mc_permission')->where('perid',$perid)->update(['pername'=>$pername]);
		if ($up) {
			echo json_encode(['status'=>1]);
		} else{
			echo json_encode(['status'=>0]);
		}

	}

	//4==================权限管理  99页面
	public function adminrule(){
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
		if (!in_array('/admin/permission/adminrule', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}

		$count = Db::table('mc_permission')->where('pid','>','0')->count('pid');
		$page = new Page(4,$count);
		$data  = Db::table('mc_permission')->where('pid','>','0')->limit($page->limit())->order('pid')->select();
		//权限列表// 大分类
		$this->assign('data',$data);
		//小分类所属的大分类
		$res = Db::table('mc_permission')->where('pid','0')->select();
		// dump($res);
		$this->assign('res',$res);
		//总数
		
		$this->assign('count',$count);
		$re = $page->allPage();
		$this->assign('re',$re);
		return $this->fetch();
	}
	//=====4-1增加小分类
	public function add_guanli(){
		$bpername = request()->post('bpername');		
		$mvc = request()->post('mvc');
		$spername = request()->post('spername');
		if (empty($bpername) || empty($mvc) ||empty($spername) ) {
			echo json_encode(['status'=>3]);
			die;
		}

		
		$bpid = Db::table('mc_permission')->where('pername',$bpername)->select();
		// dump($bpid);die;
		$pid = $bpid[0]['perid'];
		// dump($pid);
		//查询这个大分类的所有小分类，看名字有没有重复的
		$name = Db::table('mc_permission')->where('pid',$pid)->select();
		$allpername = [];
		foreach ($name as $key => $value) {
			$allpername[]=$value['pername'];
		}
		//查看小分类名字是否存在
		if (in_array($spername, $allpername)) {
			echo json_encode(['status'=>2]);
			die;
		}
		//进行添加
		$data =[
			'pername'=>$spername,
			'mvc'=>$mvc,
			'pid'=>$pid,
		];
		$cheng =  Db::table('mc_permission')->insert($data);
		if ($cheng) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}

	}
	//=====4-2删除小权限，也就小分类
	public function do_del_quanxian(){
		$perid = request()->post('perid');
		//删除rp角色权限表的所有小权限
		//先查出来
		$selxiao = Db::table('mc_role_per')->where('perid',$perid)->select();
		if (!empty($selxiao)) {
			Db::table('mc_role_per')->where('perid',$perid)->delete();
		}
		$del = Db::table('mc_permission')->where('perid',$perid)->delete();

		if ($del) {
			echo json_encode(['status'=>1]);
		} else {
			echo json_encode(['status'=>0]);
		}
	}	
	//=======4-3修改小权限，
	//遍历页面  99页面
	public function cateedit4 (){
		$perid = request()->get('perid');
		$this->assign('perid',$perid);
		//找到这个分类
		$pername = Db::table('mc_permission')->where('perid',$perid)->select();
		$pername = $pername[0]['pername'];
		$this->assign('pername',$pername);
		//根据小的分类找到父类$perid 
		$pid = Db::table('mc_permission')->where('perid',$perid)->value('pid');
		$moren = Db::table('mc_permission')->where('perid',$pid)->value('pername');
		$this->assign('moren',$moren);
		//遍历所有的大分类
		$bfen = Db::table('mc_permission')->where('pid',0)->select();
		$this->assign('bfen',$bfen);

		return $this->fetch();
	}
	//进行权限管理的修改，将小分类变道别的分类下面
	public function edit_guanli(){
		//小分类的id
		$perid = request()->post('perid');
		//传过来的小分类的名字
		$spername = request()->post('spername');
		//传过来的大分类的名字
		$bpername = request()->post('bpername');

		// $perid = 45;
		// $spername = '管理员管理';
		// $bpername = '管理员管理';
		
		//查看小分类所在的大分类变化没
		$oldpid = Db::table('mc_permission')->where('perid',$perid)->value('pid');
		$oldbigname = Db::table('mc_permission')->where('perid',$oldpid)->value('pername');
		//旧的名字，不等于新的名字，进行变化
		if ($oldbigname != $bpername) {
			//大分类变化的话，看看小分类的名字是否重复
			//查询这个新的大分类的所有小分类，看名字有没有重复的
			$pid =  Db::table('mc_permission')->where('pername',$bpername)->value('perid');
			$name = Db::table('mc_permission')->where('pid',$pid)->select();
			$allpername = [];
			foreach ($name as $key => $value) {
				$allpername[]=$value['pername'];
			}
			//查看小分类名字是否存在
			if (in_array($spername, $allpername)) {
				echo json_encode(['status'=>2]);
				die;
			}	
			//找到新的大分类的perid,s是人家的pid
			// $pid = Db::table('mc_permission')->where('pername',$bpername)->value('perid');
			$cheng = Db::table('mc_permission')->where('perid',$perid)->update(['pid'=>$pid]);
			if ($cheng) {
				echo json_encode(['status'=>1]);
				die;
			}else{
				echo json_encode(['status'=>0]);
				die;
			}
		}
		//判断小分类的名字是否变化,在大分类没变的前提下
		$oldname = Db::table('mc_permission')->where('perid',$perid)->value('pername');
		if ($oldname != $spername) {
			//看看小分类的名字是否重复
			//查询这个新的大分类的所有小分类，看名字有没有重复的
			$pid =  Db::table('mc_permission')->where('pername',$bpername)->value('perid');
			$name = Db::table('mc_permission')->where('pid',$pid)->select();
			$allpername = [];
			foreach ($name as $key => $value) {
				$allpername[]=$value['pername'];
			}
			//查看小分类名字是否存在
			if (in_array($spername, $allpername)) {
				echo json_encode(['status'=>2]);
				die;
			}	
			//
			$cheng = Db::table('mc_permission')->where('perid',$perid)->update(['pername'=>$spername]);
			if ($cheng) {
				echo json_encode(['status'=>1]);
				die;
			}else{
				echo json_encode(['status'=>0]);
				die;
			}
		} else {
			echo json_encode(['status'=>9]);
			die;
		}

	}

	//后台登录页面 99页面
	public function login(){
		return $this->fetch();
	}

	//登陆
	public function admin_login(){
		$username = request()->post('username');
		$password = request()->post('password');
		//看用户名是否正确
		$user = Db::table('mc_user')->where('username',$username)->select(); 	
		if (!$user) {
			echo "<script>alert('用户名不存在');top.location='/admin/permission/login';</script>";
			die;
		}
		if ($user[0]['udertype'] != 1) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/login';</script>";
			die;
		}
		$pass = $user[0]['password'];
		$code = new Code();
		$pa = $code->jiami($password);
		if ($pass != $pa) {
			echo "<script>alert('密码错误');top.location='/admin/permission/login';</script>";
			die;
		}
		//查询用户角色表，找到这个管理员的角色
		$uid = $user[0]['uid'];
		$rid = Db::table('mc_user_role')->where('uid',$uid)->value('rid');		

		//角色权限表，找到这个角色的所有权限
		$per = Db::table('mc_role_per')->where('rid',$rid)->select();		
		//将角色权限表，所有的 权限id遍历到数组中
		$perid = [];
		foreach ($per as $key => $value) {
			$perid[] = $value['perid'];
		}		
		//找到这些权限
		$pername = Db::table('mc_permission')->where('perid' ,'in', $perid)->select();
		//将所有的权限和用户所有信息存到session,注意，sesssion(pername)是数组
		session('pername', $pername);
		session('user',$user);
		session('username',$username);
		$picture = $user[0]['picture'];
		session('picture',$picture);
		session('uid',$uid);

		$this->success('登陆成功','admin/permission/admin_index');
	}

	//后台首页
	public function admin_index(){
		$perArr = session('pername');
		if (empty($perArr)) {
			echo "<script>alert('请登录');top.location='/admin/permission/login';</script>";
		}
		$this->assign('perArr',$perArr);
		return $this->fetch();
	}
	//后台退出
	public function logout(){
		session(null);
		$this->success('退出成功','admin/permission/login');
	}


	//我的桌面，以后继续修改
	public function welcome()
	{
		return $this->fetch();
	}



}