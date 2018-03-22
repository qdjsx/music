<?php
namespace app\index\controller;

use think\Controller;// 获取控制器综合的类------->很多用途
use think\Request;//获取当前的请求信息的类
use think\Db;//使用Db类
use think\Session;
use app\index\controller\Page;


use app\index\model\User as UserModel;
use app\index\model\Songs as SongsModel;//使用歌曲表
use app\index\model\Menu as MenuModel;// 使用歌单表
use app\index\model\Genre as GenreModel; //使用类型管理表
use app\index\model\Artist as ArtistModel; //使用歌手管理表

class Mv extends Controller
{
    // mv列表页
    public function mv(){
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

        // 查询所有MV
        $count = Db::name('mv')->count();
        $page = new Page(2,$count);
        $limit = $page->limit();
        $allPage = $page->allPage();
        $this->assign('allPage',$allPage);
        $mv = Db::name('mv')->limit($limit)->select();
        $this->assign('mv',$mv);

        return $this->fetch();
    }
    // mv播放页面
    public function mvplay()
    {
        $mv = $this->request->get();
        
        // 查询当前mv
        if(!empty($mv['mvid'])){
            $mvid = $mv['mvid'];
            $result = Db::name('mv')->where('vid',$mvid)->find();
            $this->assign('mv',$result);

            //展示所有评论
            $post = Db::table('mc_mvpost post,mc_user user')->where('post.vid',$mvid)->where('post.uid = user.uid')->order('post.create_time desc')->field('post.create_time,user.username,post.content,user.picture,post.pid,post.id')->select();
            $huifu = $this->getTree4($post);
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
                unset($list[$key]);
                $this->getTree4($list, $value['id'], $level+1);
                }
            }
        return $newlist;
    }
    
    //发表回复
    public function addpost()
    {
         $mvid = input('get.mvid');
        // $id = request()->post('sid');
        // var_dump($id);die;
       if (empty(session('uid'))) {
            echo "<script>alert('请登录');top.location='/index/mv/mvplay?mvid=$mvid';</script>";
            exit;
        }
        $content = request()->post('content');
        if (empty($content)) {
            echo "<script>alert('请填写内容');top.location='/index/mv/mvplay?mvid=$mvid';</script>";
            exit;
        }
        $uid = session('uid');
        if(!empty(input('param.id'))){
            $pid=input('param.id');
        }else{
            $pid=0;
        }
        $res = Db::table('mc_mvpost')->insert(['content'=>$content,'pid'=>$pid,'uid'=>$uid,'vid'=>$mvid,'create_time'=>time()]);
        if ($res) {
            // 评论成功加积分
            // 获取当前与用户积分
            $grade = Db::name('user')->where('uid',$uid)->value('grade');
            $grade = $grade + 2;
            // 更新到数据库
            $rel = Db::name('user')->where('uid',$uid)->update(['grade'=>$grade]);
            Session::set('grade',$grade);
           
            echo "<script>alert('发表成功');top.location='/index/mv/mvplay?mvid=$mvid';</script>";
            exit;
        }
    }
   
}
