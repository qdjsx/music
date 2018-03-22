<?php
namespace app\index\controller;

use think\Controller;// 获取控制器综合的类------->很多用途
use think\Request;//获取当前的请求信息的类
use think\Db;//使用Db类
use app\index\model\User as UserModel;
use think\Session;
use app\index\model\Songs as SongsModel;
use app\index\model\Menu as MenuModel;
use think\Open51094;
class Index extends Controller
{
    //跳转页面
     public function notice($msg, $url = null, $sec = 3)
    {
        if (empty($url)) {
            $url = $_SERVER['HTTP_REFERER'];
        }
       
        $this->assign('msg', $msg);
        $this->assign('url', $url);
        $this->assign('sec', $sec);
        return $this->fetch('notice');
    }
    public function index()
    {
        // dump(Session::get());
        // die;
        // 查询热门歌单
        $hot = Db::name('menu')->order('isselect','desc')->limit('8')->select();
        $this->assign('hot', $hot);


        // 查询该用户创建的歌单
        $uid = Session::get('uid');
        $result = MenuModel::all(['user_id'=>$uid]);
        $this->assign('mygd',$result);

        // 查询当前用户的收藏的歌单
        if(!empty($uid)){
            $result = Db::table('mc_user_menu um,mc_menu menu')->where("um.uid=$uid")->where('menu.id=um.menu_id')->select();
            $this->assign('menus',$result);   
        }
        // 查询轮播图
        $slideshow = Db::name('slideshow')->select();
        $this->assign('slideshow',$slideshow);

        // 查询最新专辑
        $album = Db::name('album')->order('id','desc')->limit(10)->select();
        $this->assign('album',$album);

        // 查询当前时间
       $this->assign('datatime',time());
        // 查询热门歌曲
        $hotsongs = Db::name('songs')->field('id,name')->order('listens','desc')->limit(8)->select();
       $this->assign('hotsongs',$hotsongs);
        // 查询歌手榜
       $artist = Db::name('artist')->field('id,name')->order('hits','desc')->limit(8)->select();
       $this->assign('ass',$artist);
       
        return $this->fetch();
    }
    // 音乐界面
    public function music()
    {
        
        $songs = new SongsModel();
        $result = $songs::all();
        // dump($result);
        $this->assign('result', $result);
        // // dump($data);
        return $this->fetch();
    }
    //第三方登录信息存储
    public function three()
    {
        $open = new open51094();
        $code = $_GET['code'];
        $info = $open->me($code);
        
        // 先聪数据库中查询看是否用户存在
        $result = UserModel::getByUsername($info['name']);
        if(empty($result)){
            $id = Db::name('user')->insertGetId([
                    'username'=>$info['name'], 
                    'password'=>md5($info['name']),
                    'email'=>3,'phone'=>3,
                    'udertype'=>3,
                    'type'=>$info['from'],
                    'picture'=>$info['img'],
                    'sex'=>$info['sex']
                    ]);
            $result = UserModel::getByUid($id);
            Db::table('codepay_user')->insert(['user'=>$result->username]);
        }
        // var_dump( $result->username );
        // die;
        //把用户信息放入session,以后用
        Session::set('username', $result->username);
        Session::set('uid',$result->uid);
        Session::set('udertype',$result->udertype);
        Session::set('grade',$result->grade);
        Session::set('picture',$result->picture);
        Session::set('type',$result->type);

        return $this->notice('登陆成功', '/index/index/index');
    }
    // 歌单的创建
    public function addmenu()
    {
        $gdname = $this->request->post('gdname');
       
        $userId = Session::get('uid');
        $result = Db::name('menu')->insert(['name'=>$gdname,'user_id'=>$userId,'createtime'=>time()]);
        if(!empty($result)){
            echo "<script>alert('添加歌单成功');history.go(-1);</script>";
            exit;
        } else {
            echo "<script>alert('添加歌单失败');history.go(-1);</script>";
            exit;
        }

    }
    public function search()
    {
        
        $content = $this->request->post('search');
        if(empty($content)){
            echo "<script>alert('请填写搜索内容');history.go(-1);</script>";
            
        }
        // 从歌单搜索(模糊查询)
        $where['name'] = array('like','%'.$content.'%');
        $menu = Db::name('menu')->where($where)->select();
        $this->assign('menu',$menu);
        
        // 从歌曲搜索
        $where['name'] = array('like','%'.$content.'%');
        $songs = Db::name('songs')->where($where)->select();
        $this->assign('songs',$songs);
        
        // 从歌手搜索
        $where['name'] = array('like','%'.$content.'%');
        $artist = Db::name('artist')->where($where)->select();
        $this->assign('artist',$artist);
        
        // 从专辑搜索
        $where['name'] = array('like','%'.$content.'%');
        $album = Db::name('album')->where($where)->select();
        $this->assign('album',$album);

        
        return $this->fetch();
    }
}
