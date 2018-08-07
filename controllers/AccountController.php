<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\Wallet;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\io\Response;

class AccountController extends IFramePageController {
	public function index($cur) {
		$cfg              = ConfigurationLoader::loadFromFile('currency');
		$curs             = $cfg->toArray();
		$data['currency'] = $cur;
		if (!isset($curs[ $cur ])) {
			Response::respond(404, $cur . ' not found');
		}

		return $this->render($data);
	}

	public function data($type = '', $q = '', $mid = 0, $count = '') {
		$model = new Wallet();
		if ($q) {
			$where['mname LIKE'] = '%' . $q . '%';
		}
		if ($mid) {
			$where['user_id'] = $mid;
		}
		$where['currency'] = $type;

		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		$total = '';
		if ($count) {
			$total = $query->total('user_id');
		}
		foreach ($rows as &$row) {
			$cur             = Currency::init($row['currency']);
			$row['amount']   = $cur->fromUint($row['amount']);
			$row['balance']  = $cur->fromUint($row['balance']);
			$row['balance1'] = $cur->fromUint($row['balance1']);
			$row['frozen']   = $cur->fromUint($row['frozen']);
		}
		$data['canOut']     = $this->passport->cando('out:wallet');
		$data['canDeposit'] = $this->passport->cando('deposit:wallet');
		$data['rows']       = $rows;
		$data['total']      = $total;

		return view($data);
	}

}