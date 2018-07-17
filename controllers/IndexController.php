<?php

namespace wallet\controllers;

use wulaphp\mvc\controller\Controller;
/**
 * 默认控制器.
 */
class IndexController extends Controller {
    /**
     * 默认控制方法.
     */
	public function index() {
	    $data = ['module'=>'Index'];
		// 你的代码写在这里

		return view($data);
	}
}