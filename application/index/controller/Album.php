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
use app\index\model\Artist as ArtistModel; //使用歌手管理表
use app\index\model\ALbum as AlbumModel; //使用专辑管理表
use app\index\controller\Page;

class Album extends Controller
{
    	
    // 专辑页面
    public function album()
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
        // 查询所有专辑
        $count = Db::name('album')->count();
        $page = new Page(12,$count);
        $limit = $page->limit();
        $data['allPage'] = $page->allPage();
        // 查询所有专辑
        $data['album'] = Db::name('album')->limit($limit)->select();
        $this->assign('data',$data);
        // 查询最新专辑
        $album = Db::name('album')->order('id','desc')->limit(10)->select();
        $this->assign('album',$album);

        return $this->fetch();
    }
    // 专辑详情页
    public function albumdetail()
    {
        $uid = session('uid');
        // 查询专辑详情
        $album = $this->request->get();
        if(!empty($album['alid'])){
            $alid = $album['alid'];
            // 查询特定专辑
            $result = Db::name('album')->where('id',$alid)->find();
            
            $this->assign('album',$result);
            // 查询歌手
            $artist = Db::name('artist')->where('id',$result['artist_id'])->find();
           
            $this->assign('artist',$artist);
            // 查询专辑下的歌曲
            $songs = Db::name('songs')->where('album_id',$alid)->select();
            $this->assign('songs',$songs);
            //查询能播放的歌曲
            $re = Db::name('songs')->where("album_id = $alid and score = 0 and gold = 0")->select();
            $this->assign('result',$re);
            //展示所有评论
            $post = Db::table('mc_albumpost post,mc_user user')->where('post.aid',$alid)->where('post.uid = user.uid')->order('post.create_time desc')->field('post.create_time,user.username,post.content,user.picture,post.pid,post.id')->select();
            $huifu = $this->getTree4($post);
            // var_dump($post);
            // var_dump($huifu);
            $this->assign('huifu',$huifu);
        }
        return $this->fetch();
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
                // var_dump($value);
                unset($list[$key]);
                $this->getTree4($list, $value['id'], $level+1);
                }
            }
        return $newlist;
    }


     //发表回复
    public function addpost()
    {
         $id = input('get.alid');


       if (empty(session('uid'))) {
            echo "<script>alert('请登录');top.location='/index/album/albumdetail?alid=$id';</script>";
            exit;
        }
        $content = request()->post('content');
        if (empty($content)) {
            echo "<script>alert('请填写内容');top.location='/index/album/albumdetail?alid=$id';</script>";
            exit;
        }
        $uid = session('uid');
        if(!empty(input('param.id'))){
            $pid=input('param.id');
        }else{
            $pid=0;
        }
        $res = Db::table('mc_albumpost')->insert(['content'=>$content,'pid'=>$pid,'uid'=>$uid,'aid'=>$id,'create_time'=>time()]);
        if ($res) {
            // 评论成功加积分
            // 获取当前与用户积分
            $grade = Db::name('user')->where('uid',$uid)->value('grade');
            $grade = $grade + 2;
            // 更新到数据库
            $rel = Db::name('user')->where('uid',$uid)->update(['grade'=>$grade]);
            Session::set('grade',$grade);
             echo "<script>alert('发表成功');top.location='/index/album/albumdetail?alid=$id';</script>";
            exit;
        }
    }
}
