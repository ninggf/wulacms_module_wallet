<?php
/**
 * //                            _ooOoo_
 * //                           o8888888o
 * //                           88" . "88
 * //                           (| -_- |)
 * //                            O\ = /O
 * //                        ____/`---'\____
 * //                      .   ' \\| |// `.
 * //                       / \\||| : |||// \
 * //                     / _||||| -:- |||||- \
 * //                       | | \\\ - /// | |
 * //                     | \_| ''\---/'' | |
 * //                      \ .-\__ `-` ___/-. /
 * //                   ___`. .' /--.--\ `. . __
 * //                ."" '< `.___\_<|>_/___.' >'"".
 * //               | | : `- \`.;`\ _ /`;.`/ - ` : | |
 * //                 \ \ `-. \_ __\ /__ _/ .-` / /
 * //         ======`-.____`-.___\_____/___.-`____.-'======
 * //                            `=---='
 * //
 * //         .............................................
 * //                  佛祖保佑             永无BUG
 * DEC : 充值记录
 * User: David Wang
 * Time: 2018/8/7 上午10:11
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\WalletDepositOrder;
use wulaphp\io\Response;

/**
 * 默认控制器.
 * @acl m:wallet
 */
class RechargeController extends IFramePageController {
	private $types = ['P' => '待付款', 'R' => '待对账', 'A' => '已入账', 'E' => '失败', 'C' => '关闭',];

	public function index($currncy) {
		$cur = Currency::init($currncy);
		if ($cur) {
			$data['types']    = $this->types;
			$data['currency'] = $currncy;

			return $this->render($data);
		}
		Response::respond(404);

		return null;
	}

	public function data($currency, $count = '') {
		$data = [];
		$user_id = rqst('user_id');
		if ($user_id) {
			$where['user_id'] = $user_id;
		}
		$start_time = rqst('start_time');
		if ($start_time) {
			$where['create_time >'] = strtotime($start_time);
		}
		$end_time = rqst('end_time');
		if ($end_time) {
			$where['create_time <'] = strtotime($end_time);
		}
		if ($currency) {
			$model             = new WalletDepositOrder();
			$where['currency'] = $currency;
			$ctype             = rqst('type');
			if ($ctype != '') {
				$where['status'] = $ctype;
			}
			$query = $model->select('*')->where($where)->page()->sort();
			$rows  = $query->toArray();
			$cur   = Currency::init($currency);
			foreach ($rows as &$row) {
				$row['cur_name'] = $cur->name;
				$row['amount'] = $cur->fromUint($row['amount']).$cur->symbol;
				$row['status'] = $this->types[$row['status']];
			}
			$total = '';
			if ($count) {
				$total = $query->total('id');
			}
			$data['rows']     = $rows;
			$data['total']    = $total;
			$data['types']    = $cur->types;
		}

		return view($data);
	}
}