<?php
namespace app\index\controller;

use think\Controller;// 获取控制器综合的类------->很多用途
use think\Request;//获取当前的请求信息的类
use think\Db;//使用Db类

use app\index\model\User as UserModel;
use think\Session;
use app\index\model\Songs as SongsModel;//使用歌曲表
use app\index\model\Menu as MenuModel;// 使用歌单表
use app\index\model\Genre as GenreModel; //使用类型管理表
use app\index\controller\Page;
class Songs extends Controller
{
    	
    // 歌单页面
    public function music()
    {
        $songs = new SongsModel();
        $result = $songs::all();
        // dump($result);
        $this->assign('result', $result);
        // // dump($data);
        return $this->fetchtch();
    }
    // 歌单详情页面
    public function single()
    {
        
        $uid = session('uid');
        
        $gd = $this->request->get();
        // 判断传参是否为空
        if(!empty($gd['gdid'])){
            $gdid = $gd['gdid'];
            $result = MenuModel::get(['id'=>$gdid]);
            // 查询该歌单的标签
            $title = Db::table('mc_menu_genre mg,mc_genre g')->where('mg.menu_id',$gdid)->where('mg.genre_id=g.id')->select();

            $this->assign('title',$title);
            $this->assign('menu',$result);
            // 查询该歌单是否已被该用户收藏
            if(!empty($uid)){
                $rel = Db::name('user_menu')->where("menu_id=$gdid")->where("uid=$uid")->find();
            }
            if(!empty($rel)){
                $this->assign('isselect',true);
            }else{
                $this->assign('isselect',false);
            }

            // 通过menu_songs表查询个歌单下的歌曲
            $songs = Db::table('mc_menu_songs ms,mc_songs songs,mc_artist a')->where("ms.menu_id=$gdid")->where('ms.songs_id=songs.id')->where('songs.artist_id=a.id')->field('songs.name sname,a.name,songs.id,a.id arid,songs.score')->select();
            $this->assign('songs',$songs);
            //要播放的歌曲
             $re = Db::table('mc_menu_songs ms,mc_songs songs,mc_artist a')->where("ms.menu_id=$gdid")->where('ms.songs_id=songs.id')->where('songs.artist_id=a.id')->where('songs.score= 0')->where('songs.gold = 0')->field('songs.name sname,a.name aname,songs.id,a.id arid,songs.score,songs.music_url,songs.songword')->select();
            $this->assign('result',$re);
            
            // 查询创建者
            $user = UserModel::get(['id',$result->user_id]);
            $this->assign('user',$user);
            // // 获取歌单中的歌曲数目
            $songscount = count($songs);
            $this->assign('count',$songscount);
        }
        // 查询热门歌单
        $hot = Db::name('menu')->order('isselect','desc')->limit('3')->select();
        $this->assign('hot', $hot);
       
        
    	return $this->fetch();
    }
    // 用户收藏歌单
    public function shoucang()
    {
        $gdid = $this->request->get('gdid');
        $uid = Session::get('uid');
        $result = Db::name( 'user_menu')->insert(['uid'=>$uid,'menu_id'=>$gdid]);
        if(!empty($result)){
            // 收藏成功歌单收藏量+1n

            Db::table('mc_menu')->where('id', $gdid)->setInc('isselect');
            echo json_encode(['state'=>1]);
            exit;
        }
        echo json_encode(['state'=>0]);
    }
   


    // 歌曲收藏到歌单
    public function tomenu()
    {
        $sid = $this->request->get('sid');
        $mid = $this->request->get('mid');
        // 首先判断个歌单中是否有该歌曲
        $result = Db::name('menu_songs')->where('menu_id',$mid)->where('songs_id',$sid)->select();
        if(!empty($result)){
            echo "<script>alert('该歌曲早已添加到个该歌单'); history.go(-1)</script>";
            exit;
        }
        // 插入数据库
        $data = ['menu_id'=>$mid,'songs_id'=>$sid];
        $result = Db::name('menu_songs')->insert($data);
        if(!empty($result)){
            echo "<script>alert('添加成功'); history.go(-1)</script>";
        }else{
            echo "<script>alert('添加失败'); history.go(-1)</script>";
        }
    }
    public function allsong()
    {
        // 查询该用户创建的歌单
        $uid = Session::get('uid');
        $result = MenuModel::all(['user_id'=>$uid]);
        $this->assign('mygd',$result);

        // 查询当前用户的收藏的歌单
        if(!empty($uid)){
            $result = Db::table('mc_user_menu um,mc_menu menu')->where("um.uid=$uid")->where('menu.id=um.menu_id')->select();
            $this->assign('menus',$result);
           
        }
        // 查询最新专辑
        $album = Db::name('album')->order('id','desc')->limit(10)->select();
        $this->assign('album',$album);
        // 分页效果
        $count = Db::name('songs')->count();
        $page = new Page(12,$count);
        $limit = $page->limit();
        $allPage = $page->allPage();
        $this->assign('allPage',$allPage);
        // 查询所有歌曲
        $songs = Db::name('songs')->limit($limit)->select();
        $this->assign('songs',$songs);
        
        return $this->fetch();
    }

    // 歌曲播放页面
    public function songsdetail()
    {
        $uid = Session::get('uid');
        // 查询歌曲详情
        $songs = $this->request->get();
        if(empty($songs['sid'])){
             $this->assign('result','');
            $this->assign('status',1);
             //金钱
            $this->assign('st',1);
            return $this->fetch();
        }    
            $sid = $songs['sid'];
            $result = Db::name('songs')->where('id',$sid)->find();
            // 插叙歌曲所属歌手
            $artist = Db::name('artist')->where('id',$result['artist_id'])->find();
            $album = Db::name('album')->where('id',$result['album_id'])->find();
            
            $this->assign('songs',$result);
            $this->assign('album',$album);
            $this->assign('artist',$artist);


             // 查询用户创建歌单
            $results = Db::name('menu')->where('user_id',$uid)->select();
            $this->assign('mymenu',$results);

            //展示所有评论
            $post = Db::table('mc_songpost post,mc_user user')->where('post.sid',$sid)->where('post.uid = user.uid')->order('post.create_time desc')->field('post.create_time,user.username,post.content,user.picture,post.pid,post.id')->select();
            $huifu = $this->getTree4($post);
            // var_dump($huifu);
            $this->assign('huifu',$huifu);

            //判断积分
             $score = $result['score'];
            //先判断金钱，金钱里面在判断积分
            $gold = $result['gold'];
          
            if ($gold != 0) {

                if (empty(session('uid'))) {
                    //还没购买
                    if ($score == 0) {
                        //积分
                        $this->assign('status',1);
                    }else{
                        //积分
                        $this->assign('status',0);
                    }
                     $this->assign('result','');
                    
                     //金钱
                    $this->assign('st',0);
                     return $this->fetch();
                }
                
                //不需要积分
                if ($score == 0) {

                    //进行金币购买
                    $uid = session('uid');
                    $username = session('username');
                    // var_dump($username);
                    $data = Db::table('codepay_order')->where("pay_id= '$username' and param=$sid")->select();
                    // var_dump($data);
                    //已经购买了
                    if ($data) {
                        $re = Db::name('songs')->where('id',$sid)->select();
                        $this->assign('result',$re);
                        $this->assign('status',1);
                         //金钱
                        $this->assign('st',1);
                         return $this->fetch();

                    } else {
                        //还没购买
                         $this->assign('result','');
                         $this->assign('status',1);
                          //金钱
                        $this->assign('st',0);
                         return $this->fetch();
                    }
                } else{
                    ////既需要金币，又需要积分
                     //进行金币购买
                    $uid = session('uid');
                    $username = session('username');
                    //金币购买了
                    $data = Db::table('codepay_order')->where("pay_id = '$username' and param = $sid")->select();
                    //积分购买了
                    $da = Db::table('mc_order')->where("uid = $uid and sid = $sid and ispay = 1")->select();
                    if ($data && $da) {
                        $re = Db::name('songs')->where('id',$sid)->select();
                        $this->assign('result',$re);
                        $this->assign('status',1);
                         //金钱
                        $this->assign('st',1);
                         return $this->fetch();
                    }else{
                        if ($data) {
                            //金钱
                            $this->assign('st',1);
                             $this->assign('status',0);
                        } else if ($da) {
                           $this->assign('status',1);
                            $this->assign('st',0);
                        } else {
                            $this->assign('status',0);
                            $this->assign('st',0);
                        }
                        //还没购买
                         $this->assign('result','');      
                         return $this->fetch();
                    }
                }            
            } else {
               //不许要金币购买 
                //判断积分
                $score = $result['score'];
                if ($score == 0) {
                    //不需要积分的
                    $re = Db::name('songs')->where('id',$sid)->select();
                    $this->assign('result',$re);
                    $this->assign('status',1);
                    $this->assign('st',1);

                    return $this->fetch();
                }else{
                    if (empty(session('uid'))) {
                        //还没购买
                         $this->assign('result','');
                         $this->assign('status',0);
                           $this->assign('st',1);
                         return $this->fetch();
                    }
                    //需要积分的，先判断是否购买
                    $uid = session('uid');
                    $data = Db::table('mc_order')->where("uid = $uid and sid = $sid and ispay = 1")->select();
                    //已经购买了
                    if ($data) {
                        $re = Db::name('songs')->where('id',$sid)->select();
                        $this->assign('result',$re);
                        $this->assign('status',1);
                        $this->assign('st',1);
                        return $this->fetch();
                    } else {
                        //还没购买
                         $this->assign('result','');
                         $this->assign('status',0);
                        $this->assign('st',1);
                        return $this->fetch();
                    }
                }

            }
    }
    //购买歌曲
    public function buy(){
        $id = request()->get('sid');
        if (empty(session('uid'))) {
            echo "<script>alert('请登录');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
        $score = Db::table('mc_songs')->where('id',$id)->value('score');
        $uid = session('uid');
         // var_dump(session('uid'));
        //用户积分
        $grade = Db::table('mc_user')->where('uid',$uid)->value('grade');
        if ($grade < $score) {
            echo "<script>alert('积分不足');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
        $gra = $grade - $score;
        Db::table('mc_user')->where('uid',$uid)->update(['grade'=>$gra]);

        Session::set('grade',$gra);
        //在order表增加
        $res = Db::table('mc_order')->insert(['uid'=>$uid,'sid'=>$id,'score'=>$score,'addtime'=>time(),'ispay'=>1]);
        if ($res) {
             echo "<script>alert('购买成功');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
    }

    //遍历回复
      function getTree4($list, $pid=0, $level=1)
    {
        static $newlist = array();
        foreach($list as $key => $value)
            {
            if($value['pid']==$pid)
                {
                $value['level'] = $level;
                $newlist[] = $value;
                unset($list[$key]);
                $this->getTree4($list, $value['id'], $level+1);
                }
            }
        return $newlist;
    }
    
    //发表回复
    public function addpost()
    {
         $id = input('get.sid');
        // $id = request()->post('sid');
        // var_dump($id);die;
       if (empty(session('uid'))) {
            echo "<script>alert('请登录');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
        $content = request()->post('content');
        if (empty($content)) {
            echo "<script>alert('请填写内容');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
        $uid = session('uid');
        if(!empty(input('param.id'))){
            $pid=input('param.id');
        }else{
            $pid=0;
        }
        $res = Db::table('mc_songpost')->insert(['content'=>$content,'pid'=>$pid,'uid'=>$uid,'sid'=>$id,'create_time'=>time()]);
        if ($res) {
            // 评论成功加积分
            // 获取当前与用户积分
            $grade = Db::name('user')->where('uid',$uid)->value('grade');
            $grade = $grade + 2;
            // 更新到数据库
            $rel = Db::name('user')->where('uid',$uid)->update(['grade'=>$grade]);
           Session::set('grade',$grade);

            echo "<script>alert('发表成功');top.location='/index/songs/songsdetail?sid=$id';</script>";
            exit;
        }
    }

}
