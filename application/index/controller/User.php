<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use app\index\model\User as UserModel;
use think\Session;
use app\index\model\Ucpaas as UcpaasModel;//短信验证的类
use app\index\model\Genre as GenreModel; //使用类型管理表
use app\index\controller\Code;
use app\index\controller\Page;

class User extends Controller
{
  //跳转页面
  public function notice($msg, $url = null, $sec = 3)
    {
        if (empty($url)) {
            $url = '/';
        }
       
        $this->assign('msg', $msg);
        $this->assign('url', $url);
        $this->assign('sec', $sec);
        return $this->fetch('notice');
    }
	//判断登陆的用户名
  public function checkuser(){
      $username= $this->request->post('username');
      $result = UserModel::getByUsername($username);
      if (empty($result)) { 
          // 为空的话，这个用户名不存在    
          echo json_encode(['state'=>0]);
          exit;
      } else {
          echo json_encode(['state'=>1]);
      }
  }
  //点击登陆按钮走这个方法
  public function dologin() {

    $username= $this->request->post('username');
    $password = $this->request->post('password');
    $result = UserModel::getByUsername($username);
    //判断用户名是否为空
    if (empty($result)) {
        return  $this->notice('请填写用户名');          
    }
    // 调用自己定义的加密类
    $enctype = new Code();

    //查询密码是否匹配
    $pwd = $result->password;
    // $pwd = Db::table('mc_user')->where('username',$username)->value('password');
      if ($enctype->jiami($password) == $pwd) {
          //登陆成功判断一些列情况
          //登陆之后一些列值
          $id = $result->uid;
          $udertype = $result->udertype;
          $grade = $result->grade;
          $allowlogin = $result->allowlogin;
          // $regip = $result->regip;
          $picture = $result->picture; 
          //普通用户登陆。判断一系列状况    
          //对于普通用户判断是否关闭站点 0是关闭站点
          if ($udertype == 0) {
              $close = Db::table('mc_siteinfo')->select();
              $radio=$close[0]['radio'];
              if ($radio == 0){
                  header('location:/index/user/closeWeb');
                  exit;
              }
          }
          //是否被禁止登陆
          if ($allowlogin == 1) {
              echo "<script>alert('您的用户被锁定,请重新找回密码登陆');history.go(-1);</script>";
              exit;
          }    
          //登陆成功加 2积分
          $grade = ($grade + 2);
          Db::table('mc_user')->where('uid',$id)->update(['grade' => $grade]);
          //登陆成功后将登陆错误次数更新为0
          Db::table('mc_user')->where('uid',$id)->update(['errorcount'=>0]);
          //把用户信息放入session,以后用
          Session::set('username', $username);
          Session::set('uid',$id);
          Session::set('udertype',$udertype);
          Session::set('grade',$grade);
          Session::set('picture',$picture);
          return $this->notice('登陆成功');
      } else{
          //登陆密码错误
          $id = $result->uid;
          $error = Db::table('mc_user')->where('uid',$id)->value('errorcount');
          // dump($error);die;
          if ($error <5) {
              $error++;
              UserModel::where('uid',$id)->update(['errorcount'=>"$error"]); 
              //5次锁定账号
          }  else {
              UserModel::where('uid',$id)->update(['allowlogin'=>1]);
              echo "<script>alert('您的用户被锁定,请重新找回密码登陆');history.go(-1);</script>";
              exit;
          }
          //
          return  $this->notice('登陆失败');
      }
  }
  // 注册用户
  public function doregister()
  {
    $enctype = new Code();
    // 获取传过来的注册信息
    $username= $this->request->post('username');
    $pwd = $this->request->post('password');
    $password = $enctype->jiami($pwd);
    // $confirm = $this->request->post('confirm');
    $email = $this->request->post('email');
    $phone = $this->request->post('phone');
    // $username = 'goudan';
    // $password = 'qqq';
    // $email = 'weqweqw';
    // $phone = '12123123';
    // $phoneyzm = $this->request->post('phoneyzm');
    $ret = Db::name('user')->insertGetId(['username'=>$username, 'password'=>$password,'email'=>$email,'phone'=>$phone]);
    if ($ret) {
        $result = Db::table('mc_user')->where('uid',$ret)->select();
        Session::set('username',$result[0]['username']);
        Session::set('uid',$result[0]['uid']);
        Session::set('udertype',$result[0]['udertype']);
        Session::set('grade',$result[0]['grade']);
        Session::set('picture',$result[0]['picture']);
        // $_SESSION['username']=$result[0]['username'];
        // $_SESSION['uid']=$result[0]['uid'];
        // $_SESSION['udertype']=$result[0]['udertype'];
        // $_SESSION['grade']=$result[0]['grade'];
        // $_SESSION['picture']=$result[0]['picture'];
        // dump(Session::get());
        Db::table('codepay_user')->insert(['user'=>$result[0]['username']]);
      return  $this->notice('注册成功');
    } else {
        return  $this->notice('注册失败');
    }

  }
  // ajax检测用户名
  public function username()
  {
    $username= $this->request->post('username');
    if(empty($username) || $username == '用户名'){
      echo json_encode(['state'=>2]);
      exit;
    }
    $result = UserModel::getByUsername($username);
    if (!empty($result)) { 
        // 1的时候用户名已注册     
        echo json_encode(['state'=>1]);
        exit;
    } 
    echo json_encode(['state'=>0]);
  }
  // ajax 检查密码
  public function password()
  {
    $password = $this->request->post('password');
    //密码不能少于6 位，不能为为纯数字
    if(strlen($password) < 6 || is_numeric($password)){
        echo json_encode(['pwd'=>1]);
        exit;
    }
    echo json_encode(['pwd'=>0]);
  }
  //  ajax检查邮箱
  public function email()
  {
    $email = $this->request->post('email');
    if(empty($email) || $email == '邮箱'){
      echo json_encode(['state'=>2]);
      exit;
    }
    // 检查邮箱格式
    $pattern="/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    if(!preg_match($pattern, $email)){
      echo json_encode(['state'=>1]);
      exit;
    } 
    echo json_encode(['state'=>0]);
  }
  //  ajax检查手机号码
  public function phone()
  {
    $phone = $this->request->post('phone');
    if(empty($phone) || $phone == '手机号'){
      echo json_encode(['state'=>2]);
      exit;
    }
    // 检查邮箱格式
    $pattern="/^((13[0-9])|(14[5|7])|(15([0-3]|[5-9]))|(18[0,5-9]))\\d{8}$/";
    if(!preg_match($pattern, $phone)){
      echo json_encode(['state'=>1]);
      exit;
    }
    echo json_encode(['state'=>0]);
  }
  //  ajax检查手机验证码
  public function code()
  {
    $code = $this->request->post('phoneyzm');
    $code1 = session('code');
    // dump($code1 );
    if($code != $code1 || empty($code) || $code == '手机验证码'){
      echo json_encode(['state'=>1]);
      exit;
    }
    echo json_encode(['state'=>0]);
  }
  // 调用短信验证的方法
  public function phonecode()
  {
    //初始化必填
    $options['accountsid']='f5a5eff6b78e17057151ebf0b714a770';
    $options['token']='6ec37db2576cf98d8c6f8ce529021e9d';

    //初始化 $options必填
    $ucpass = new UcpaasModel($options);

    //开发者账号信息查询默认为json或xml
    header("Content-Type:text/html;charset=utf-8");


    //封装验证码
    $str = '1234567890123567654323894325789';
    $code = substr(str_shuffle($str),0,5);
    // $_SESSION['code']=$code;
    //短信验证码（模板短信）,默认以65个汉字（同65个英文）为一条（可容纳字数受您应用名称占用字符影响），超过长度短信平台将会自动分割为多条发送。分割后的多条短信将按照具体占用条数计费。
    $appId = "460e3b21dddd457a8396c585a1815b4f";
    //给那个手机号发送
    $to = $_GET['phone'];
    //模板id
    $templateId = "260885";
    //这就是验证码
    $param=$code;
    Session::set('code',$param);
    echo $ucpass->templateSMS($appId,$to,$templateId,$param);
  }
  // 退出
  public function tuichu()
  {
    session(null);
    return  $this->notice('退出成功','/index/index/index');  
  }
  // 个人中心页面
  public function selfinfo()
  {
    // 防跳墙
    if(empty(Session::get('username'))){
      return $this->notice('请先登录', '/index/index/index');
    }
    // 查询数据
    $username = Session::get('username');
    $result = UserModel::getByUsername($username);
    $this->assign('data',$result);
    return $this->fetch();
  }
  // 修改资料
  public function updateinfo()
  {
    $uid = Session::get('uid');

    $data = $this->request->post();
    $data1['username'] = $data['username'];
    $data1['sex'] = $data['sex'];
    $data1['birthday'] = $data['birthday'];
    $data1['phone'] = $data['phone'];
    $data1['place'] = $data['place'];
    $data1['autograph'] = $data['autograph'];
    
    $user = new UserModel;
    $result = $user->save($data1,['uid'=>$uid]);
    if(!empty($result)){
      echo json_encode(['state'=>1]);
      exit;
    }
    echo json_encode(['state'=>0]);
  }
  // 头像上传
  public function upload(){
    $uid = Session::get('uid');
    // 获取表单上传文件 例如上传了001.jpg
    $file = request()->file('image');
    if(empty($file)){
      echo "<script>alert('请选择文件'); history.go(-1)</script>";
    }
    $user = new UserModel;
    // dump($file);
    // die;
    // 移动到框架应用根目录/public/uploads/ 目录下
    $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'user');
    if($info){
      // 成功上传后 获取上传信息

      // 输出 jpg
      // echo $info->getExtension();
      // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
      // echo $info->getSaveName();
      // 输出 42a79759f284b767dfcb2a0197904287.jpg
      // echo $info->getFilename();
      // 上传成功之后把路径存入数据库
      $path = '/static/uploads/user/' . $info->getSaveName();
      $result = $user->save(['picture'=>$path],['uid'=>$uid]);
      if (!empty($result)) {
        Session::set('picture',$path);
        echo "<script>alert('修改成功'); history.go(-1)</script>";
      } else {
        echo "<script>alert('修改失败'); history.go(-1)</script>";
      }
      }else{
      // 上传失败获取错误信息
      echo "<script>alert('" . $file->getError() . "'); history.go(-1)</script>";
    }
  }
  public function tx()
  {
    // 防跳墙
    if(empty(Session::get('username'))){
      return $this->notice('请先登录', '/index/index/index');
    }
    // 查询数据
    $username = Session::get('username');
    $result = UserModel::getByUsername($username);
    $this->assign('data',$result);
    return $this->fetch();
  }
  // 修改密码
  public function xiugai()
  {
    $password = $this->request->post('password');
    $confirm = $this->request->post('confirm');
    if($password != $confirm){
      echo json_encode(['state'=>2]);
      exit;
    }
    // 修改数据库
    $uid = Session::get('uid');
    if(empty($uid)){
      $this->notice('请先登录','/index/index/index');
    }
    // 更改数据
    $user = new UserModel;
    $code = new Code();
    $result = $user->save(['password'=>$code->jiami($password)],['uid'=>$uid]);
    if(!empty($result)){
      echo json_encode(['state'=>1]); 
      exit;
    }
    echo json_encode(['state'=>0]);
  }

  // 用户歌单管理页面
  public function mymenu()
  {
    // 防跳墙
    if(empty(Session::get('username'))){
      return $this->notice('请先登录', '/index/index/index');
    }
    // 大板块小版块
    $type = new GenreModel();
    $big = $type->all(['parentid'=>0]);
    $this->assign('big',$big);
    $small = $type::where('parentid', '>', 0)->select();
    $this->assign('small', $small);

    return $this->fetch();
  }
  // 用户创建歌单
  public function cmenu()
  {
    $uid = Session::get('uid');
    if(empty($uid)){
      return $this->notice('请先登录', '/index/index/index');
    }
    // 查询该用户所有歌单
    $count = Db::name('menu')->where("user_id=$uid")->count();

    $page = new Page(3,$count);
    $limit = $page->limit();
    $data['allPage'] = $page->allPage();
    $data['result'] = Db::name('menu')->where("user_id=$uid")->limit($limit)->select();
    if(!empty($data)){
      return json_encode($data);
    }
  }
  // 单个歌单信息详情
  public function menuinfo()
  {
    $menu_id = $this->request->get('id');
    if(empty($menu_id)){
      echo json_encode(['state'=>0]);
      exit; 
    }
    // 查询歌单详情及歌单对应标签
    $result['gd'] = Db::name('menu')->where('id',$menu_id)->find();
    $reslut['genre'] = Db::name('menu_genre')->where('menu_id',$menu_id)->select();
    if(empty($result)){
      echo json_encode(['state'=>0]);
      exit;
    }
    echo json_encode($result);
  }
  // 删除我的歌单
  public function del()
  {
    $menu_id = $this->request->get('id');
    if(empty($menu_id)){
      echo json_encode(['state'=>0]);
      exit; 
    }
    // 删除歌单表 歌单歌曲关系表 歌单标签关系表 收藏歌单用户关系表
    //删除用户歌单表，用户收藏的歌单
    Db::table('mc_user_menu')->where('menu_id',$menu_id)->delete();
    //删除歌单歌曲表
    Db::table('mc_menu_songs')->where('menu_id',$menu_id)->delete();
    //删除歌单类型表
    Db::table('mc_menu_genre')->where('menu_id',$menu_id)->delete();


    //删除歌单
    $del = Db::table('mc_menu')->where('id',$menu_id)->delete();
    if(!empty($del)){
      echo json_encode(['state'=>1]);
      exit; 
    }
    echo json_encode(['state'=>0]);
  }
  // 修改歌单
  public function upgd()
  {
    // 获取form表单
    // dump($_POST);
    
    $menu_id = $this->request->post('gdid');
    $menu_name = $this->request->post('gdname');
    if(!empty($_POST['fittype'])){
      $genre = $_POST['fittype'];
    }
    
    $autograph = $this->request->post('autograph');
    
    // 获取表单上传文件 例如上传了001.jpg
    $file = request()->file('image');

    if(empty($file)){
      echo "<script>alert('请选择歌单封面'); history.go(-1)</script>";
      exit;
    }

    $user = new UserModel;
    
    // 移动到框架应用根目录/public/uploads/ 目录下
    $info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'user');
    if($info){
      // 成功上传后 获取上传信息

      // 输出 jpg
      // echo $info->getExtension();
      // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
      // echo $info->getSaveName();
      // 输出 42a79759f284b767dfcb2a0197904287.jpg
      // echo $info->getFilename();
      // 上传成功之后把路径存入数据库
      $path = '/static/uploads/user/' . $info->getSaveName();

      // 更新数据 歌单表 歌单标签关系表
      $result = Db::name('menu')->where('id',$menu_id)->update(['name'=>$menu_name,'menustruect'=>$autograph,'cover_url'=>$path]);
    
      if(!empty($genre)){
        
        // 删除歌单对应标签
        $menu_genre = Db::name('menu_genre')->where('menu_id',$menu_id)->delete();
        // 歌单添加标签
        foreach($genre as $val){
          $data = ['menu_id'=>$menu_id,'genre_id'=>$val];
          $rel = Db::name('menu_genre')->insert($data);
        }
        
      }
    
      if (!empty($result || $rel)) {
        echo "<script>alert('修改成功'); history.go(-1)</script>";
      } else {
        echo "<script>alert('修改失败'); history.go(-1)</script>";
      }
    }else{
      // 上传失败获取错误信息
      echo "<script>alert('" . $file->getError() . "'); history.go(-1)</script>";
    }
  }
  // 我的收藏歌单
  public function myselect()
  {
    // 防跳墙
    if(empty(Session::get('username'))){
      return $this->notice('请先登录', '/index/index/index');
    }
    $uid = Session::get('uid');
    
    // 分页效果
    $count = Db::name('user_menu')->where('uid',$uid)->count();
    $page = new Page(3,$count);
    $limit = $page->limit();
    $allPage = $page->allPage();
     // 查询所有歌单
    $result = Db::name('menu')->limit($limit)->select();
    $this->assign('allPage',$allPage);
    
    // 查询我收藏的歌单
    $result = Db::table('mc_menu m,mc_user_menu um')->where('um.uid',$uid)->where('m.id=um.menu_id')->field('m.name,m.id mid')->order('m.id')->limit($limit)->select();
    
    $this->assign('result',$result);
    return $this->fetch();
  }
  // 删除收藏歌单
  public function delselect()
  {
    $menu_id = $this->request->get('id');
    if(empty($menu_id)){
      echo json_encode(['state'=>0]);
      exit; 
    }
    // 删除关系歌单
    $uid = Session::get('uid');
    $result = Db::name('user_menu')->where('uid',$uid)->where('menu_id',$menu_id)->delete();
    //删除歌单
    $del = Db::table('mc_menu')->where('id',$menu_id)->delete();
    if(!empty($del)){
      echo json_encode(['state'=>1]);
      exit; 
    }
    echo json_encode(['state'=>0]);
  }
  // 找回密码页面
  public function findmm()
  {
    return $this->fetch();
  }
  // 发送邮件
  public function postemail()
  {
    include 'mail/mail.php';
    $email = $this->request->post('email');
    // 判断你邮箱格式
    if(empty($email)){
      echo json_encode(['state'=>2]);
      exit;
    }
    // 检查邮箱格式
    $pattern="/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
    if(!preg_match($pattern, $email)){
      echo json_encode(['state'=>1]);
      exit;
    }
    // 生成验证码
    $code = mt_rand(100000 , 999999);
    session('codeEmail' , $code);
    // 获取发送邮箱地址
    $to = $email; 
    // 邮件标题
    $title = '玄机音乐-密码找回'; 
    // 邮件内容
    $content = "尊敬的用户，您好！您正在使用玄机音乐忘记密码”功能修改密码，本次修改密码的验证码为：  " . $code . "  如本次并非本人操作，请及时修改个人信息，确保账户安全。"; 
    $result = sendMails($to,$title,$content);
    if ($result) {
        echo json_encode(['state'=>0]);
        exit;
    } else {
        echo json_encode(['state'=>3]);
        exit;
    } 
  }
  // 验证邮箱以及修改密码
  public function zhmm()
  {

    // dump($_POST);
    // 判断用户名在数据库中是否存在
    $username= $this->request->post('username');
    $email = $this->request->post('email');
    $result = UserModel::getByUsername($username);
    if(empty($result)){
      echo "<script>alert('没有该用户'); history.go(-1)</script>";
      exit;
    }
    // 判断该用户是否为第三方登录
    if($result->udertype == 3){
      echo "<script>alert('去应该找的地方去找回密码'); history.go(-1)</script>";
      exit;
    }
    // dump($result);
    // 判断邮箱是否为数据库的邮箱
    if($email != $result->email){
      echo "<script>alert('邮箱并非该用户的邮箱'); history.go(-1)</script>";
      exit;
    }
    // 邮箱验证码是否正确
    $emailyzm = $this->request->post('emailyzm');
    $codeEmail = Session::get('codeEmail');
    // dump($codeEmail);
    if($emailyzm != $codeEmail){
      echo "<script>alert('验证码错误'); history.go(-1)</script>";;
      exit;
    }
    // 正式修改密码
    $password = $this->request->post('pwd');
    $confirm = $this->request->post('repwd');
    
    if($password != $confirm){
      echo "<script>alert('两次密码不一样'); history.go(-1)</script>";;
      exit;
    }
    // 更新密码到数据库
    $enctype = new Code();
    $password = $enctype->jiami($password);
    $result = Db::name('user')->where('username',$username)->update(['password'=>$password]);
    // dump($result);
    if(empty($result)){
      echo "<script>alert('重置密码失败'); history.go(-1)</script>";;
      exit;
    }else{
      echo "<script>alert('重置密码成功，请登录');top.location='/';</script>";
            exit;
    }
  }
}