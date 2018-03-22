<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Slideshow as SlideshowModel;
use app\admin\controller\Page;
class Slideshow extends Controller
{
	public function slideshowlist(){
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
		if (!in_array('/admin/slideshow/slideshowlist', $mmvc)) {
			echo "<script>alert('你没有权限登陆');top.location='/admin/permission/admin_index';</script>";
			exit;
		}


		return $this->fetch();
	}
	//ajax传值，遍历
	public function list(){
		$count = Db::table('mc_slideshow')->count('sid');
		$page = new Page(1,$count);
		$data =	Db::table('mc_slideshow')->limit($page->limit())->order('sid')->select();
		$value['data'] = $data;
		$value['allPage'] = $page->allPage();
		$value['count'] = $count;
		return    json_encode($value);
	}
	//修改，编辑页面
	public function slideshowedit(){
		$sid = request()->get('sid');
		$res = Db::table('mc_slideshow')->where('sid',$sid)->select();
		$this->assign('res',$res);
		// dump($res);
		return $this->fetch();
	}
	//保存
	public function slidexiu(){
		$sid = request()->get('sid');
		$url = request()->post('url');
		$des = request()->post('des');
		// 获取文件
		$file = request()->file('files');
		if (!empty($file)) {
			// dump($file);
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $file->move(ROOT_PATH . 'public' . DS . 'static' . DS . 'uploads' . DS . 'slideshow' );
			$data = '/static/uploads/slideshow/' . $info->getSaveName();
			//获取别的值
		}else {
			$data = Db::table('mc_slideshow')->where('sid',$sid)->value('cover_url');
		}
		$res = SlideshowModel::where('sid',$sid)->update(['cover_url'=>$data,'url'=>$url,'des'=>$des]);
		if ($res) {
			// echo "<script>alert('修改成功');parent.layer.close(parent.layer.getFrameIndex(window.name)); window.parent.location.reload(); parent.layer.closeAll('iframe'); </script>";
			echo "<script>alert('修改成功'); window.location.href = '/admin/slideshow/slideshowlist'; </script>";
			exit;
		}else {
			echo "<script>alert('修改失败');window.location.href = '/admin/slideshow/slideshowlist'; </script>";
			exit;
		}
			
	}
	//删除成功
	public function del_slide(){
		$sid = request()->get('sid');
		$res = Db::table('mc_slideshow')->where('sid',$sid)->delete();
		if($res){
			echo json_encode(['status'=>1]);
		}
		// echo json_encode($sid);
	}
}
