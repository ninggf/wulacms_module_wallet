<?php

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\Wallet;

/**
 * 默认控制器.
 * @acl m:wallet
 */
class IndexController extends IFramePageController {
	/**
	 * 默认控制方法.
	 */
	public function index() {
		$data['currency'] = Currency::currencies();
		$data['type'] = array_keys($data['currency'])[0];
		return $this->render($data);
	}

	public function data($type = '', $q = '', $mid = 0, $count = '') {
		$model         = new Wallet();
		if ($q) {
			$where['mname LIKE'] = '%' . $q . '%';
		}
		if ($mid) {
			$where['user_id'] = $mid;
		}
		if ($type) {
			$where['currency'] = $type;
		}
		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		$total = '';
		if ($count) {
			$total = $query->total('user_id');
		}
		foreach ($rows as &$row) {
			$cur = Currency::init($row['currency']);
			$row['amount'] = $cur->fromUint($row['amount']);
			$row['balance'] = $cur->fromUint($row['balance']);
			$row['balance1'] = $cur->fromUint($row['balance1']);
			$row['frozen'] = $cur->fromUint($row['frozen']);
		}
		$data['canOut'] = $this->passport->cando('out:wallet');
		$data['canDeposit']     = $this->passport->cando('deposit:wallet');
		$data['rows']  = $rows;
		$data['total'] = $total;

		return view($data);
	}

}