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
use app\index\controller\Page;
class Artist extends Controller
{
    	
    // 歌单页面
    public function artist()
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

        // 查询类型 再页面遍历
        // 地区
        $type = new GenreModel();
        $result = $type->all(['parentid'=>12]);
        $this->assign('type', $result);
         // 查询特定类型下的数据
        $artist = new ArtistModel();
        // 获取通过url传过来的值
        if(!empty(input('get.genrename'))){
            $genrename = input('get.genrename');
            $this->assign('leixing',$genrename);// 穿歌单下的类型值
            // dump($genrename);
            // die;
            $geshou = $artist->all(['genre_name'=>$genrename]);
            if(!empty($geshou)){
                $this->assign('geshou',$geshou);
            }
        } else {
            
            $all = $artist->all();
            $this->assign('geshou', $all);
            
        }

        return $this->fetch();
    }
    // 歌手详情页面
    public function artistdetail()
    {
        // 歌手详情.
        $artist = $this->request->get();
        if(!empty($artist['arid'])){
            $arid = $artist['arid'];
            $result = Db::name('artist')->where('id',$arid)->find();
            $this->assign('artist',$result);
            
            // 歌手所有歌曲及所属专辑加分页
            // $songs = Db::table('mc_songs s,mc_album a')->where('s.artist_id',$result['id'])->where('s.album_id=a.id')->field('s.name sname,s.id,a.name alname,s.score,a.id alid')->select();
            // $this->assign('songs',$songs);

            // 分页效果
            $count = Db::name('songs')->where('artist_id',$result['id'])->count();
            $page = new Page(3,$count);
            $limit = $page->limit();
            $allPage = $page->allPage();
            $this->assign('allPage',$allPage);
            // 查询歌手所有歌曲加分页
            $songs = Db::name('songs')->where('artist_id',$result['id'])->limit($limit)->select();
            // 歌手所有专辑
            $album = Db::name('album')->where('artist_id',$result['id'])->select();
            
            $this->assign('album',$album);
            $this->assign('songs',$songs);
        }
       
        return $this->fetch();
    }
   
}
