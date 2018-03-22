<?php
namespace app\index\controller;

use think\Controller;

class Notice extends Controller
{
	public function notice($msg)
  {
      return $this->fetch($msg);
  }
}