<?php
namespace app\admin\controller;
use think\Controller;
use think\Db;
use app\admin\model\Genre as GenreModel;

class Genre extends Controller
{
	// 无限极分类
    function wuxian($list, $parentid=0, $level=1)
    {
        static $newlist = array();
        foreach($list as $key => $value)
        {
	        if($value['parentid']==$parentid)
            {
	            $value['level'] = $level;
	            $newlist[] = $value;
	            unset($list[$key]);
	            $this->wuxian($list, $value['id'], $level+1);
            }
        }
        return $newlist;
    }

    //进行分类展示，调用上面无限极分类方法
	public function category(){
		$node = GenreModel::all();
		$node = $this->wuxian($node);
		// dump($node);
		$this->assign('node' , $node);

		$count = GenreModel::count('id');
		$this->assign('count',$count);
		return $this->fetch();
	}
	//增加
	public function add_cate(){
		$res = GenreModel::all();
		$this->assign('res',$res);
		// dump($res);
		return $this->fetch();
	}
	//增加页面
	public  function add_genre(){
		$name = request()->post('name');
		//父级父id
		$id = request()->post('parentname');
		if ($id == 0) {
			$data = [
			'name'=>$name,
			'parentid'=>0,
			'level'=>1,
			];
			$insert = GenreModel::insert($data);
			if ($insert) {
				return json_encode(['status'=>1]);
			}else {
				return json_encode(['status'=>0]);
			}
		}
		//父级父id
		// $id = GenreModel::where('name',$parentname)->value('id');
		//查询所有的子儿子
		$allname = GenreModel::where('parentid',$id)->select();
		$all = [];
		foreach ($allname as $key => $value) {
			$all[] = $value['name'];
		}
		if (in_array($name, $all)) {
			return json_encode(['status'=>3]);
		}
		//父级的level
		$plevel = GenreModel::where('id',$id)->value('level');
		$data = [
			'name'=>$name,
			'parentid'=>$id,
			'level'=>$plevel+1,
		];
		$insert = GenreModel::insert($data);
		if ($insert) {
			return json_encode(['status'=>1]);
		}else {
			return json_encode(['status'=>0]);
		}

	}

	//删除
	public function del_genre(){
		$id = request()->post('id');
		//以你要删除的这个为根节点进行查询他的所有子节点
		$parentid = $id;
		//遍历查询出所有的子id，删除
		$list = GenreModel::all();
		$arr = $this->getchildrenid($list,$parentid);
		if (!empty($arr)) {
			$res = GenreModel::where('id','in',$arr)->delete();
			$data = GenreModel::where('id',$id)->delete();
			if ($res && $data) {
				return json_encode(['status'=>1]);
			}else{
				return json_encode(['status'=>0]);
			}
		}
		$data = GenreModel::where('id',$id)->delete();
		if ($data) {
				return json_encode(['status'=>1]);
			}else{
				return json_encode(['status'=>0]);				
			}
		
	}
	// 无限极分类删除
   	 public function getchildrenid($data,$parentid){
      static $ret = array();
      foreach ($data as $key => $v) {
         if ($v['parentid']==$parentid) {
            $ret[] = $v['id'];
            $this->getchildrenid($data,$v['id']);
         }
      }
      return $ret;
   }




}	