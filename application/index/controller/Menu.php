<?php
namespace app\index\controller;

use think\Controller;// 获取控制器综合的类------->很多用途
use think\Request;//获取当前的请求信息的类
use think\Db;//使用Db类

use app\index\model\User as UserModel;
use think\Session;
use app\index\model\Songs as SongsModel;
use app\index\model\Menu as MenuModel;// 使用歌单表
use app\index\model\Genre as GenreModel; //使用类型管理表
use app\index\controller\Page;
class Menu extends Controller
{
    	
    // 歌单页面
    public function menu()
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

        // 查询类型 再页面遍历
        
        $type = new GenreModel();
        $big = $type->all(['parentid'=>0]);
        $this->assign('big',$big);
        $small = $type::where('parentid', '>', 0)->select();
        $this->assign('small', $small);
        

         // 查询特定类型下的数据
        $menu = new MenuModel();
        // 获取通过a传过来的值
        if(!empty(input('get.genre_id'))){
            $genre_id = input('get.genre_id');

            // 查歌单下的类型值
            $result = Db::name('genre')->where('id',$genre_id)->find();
            $this->assign('leixing',$result);
            // 查询类型所在大板块
            $bigname = Db::name('genre')->where('id',$result['parentid'])->find();
            $this->assign('bigname',$bigname); 

            // 通过url传参获取相应得歌单
            $gedan = Db::table('mc_menu m,mc_menu_genre mg')->where("mg.menu_id=m.id and mg.genre_id=$genre_id")->field('m.name,m.id,m.cover_url')->select();
           
            if(!empty($gedan)){
                $this->assign('gedan',$gedan);
            }
        } else {
            // 分页效果
            $count = Db::name('menu')->count();
            $page = new Page(3,$count);
            $limit = $page->limit();
            $allPage = $page->allPage();
             // 查询所有歌单
            $result = Db::name('menu')->limit($limit)->select();
            $this->assign('allPage',$allPage);
            $this->assign('gedan', $result);
            

           
            // $all = $menu->all();
            // $this->assign('gedan', $all);
        }
        // 查询最新专辑
        $album = Db::name('album')->order('id','desc')->limit(10)->select();
        $this->assign('album',$album);
        
        return $this->fetch();
    }
    
}
