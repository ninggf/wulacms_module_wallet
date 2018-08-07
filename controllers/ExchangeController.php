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
 * DEC :
 * User: David Wang
 * Time: 2018/8/6 下午6:29
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\WalletExchangeLog;
use wulaphp\conf\ConfigurationLoader;

/**
 * 默认控制器.
 * @acl m:exchange
 */
class ExchangeController extends IFramePageController {

	public function index() {
		$data['currency'] = Currency::currencies();

		return $this->render($data);
	}

	public function data($count = '') {
		$data = [];
		$user_id = rqst('user_id');
		if ($user_id) {
			$where['user_id'] = $user_id;
		}
		$currency = rqst('currency');
		if ($currency) {
			$where['from_currency'] = $currency;
		}
		$start_time = rqst('start_time');
		if ($start_time) {
			$where['create_time >'] = strtotime($start_time);
		}
		$end_time = rqst('end_time');
		if ($end_time) {
			$where['create_time <'] = strtotime($end_time);
		}
		$model = new WalletExchangeLog();

		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		$total = '';
		if ($count) {
			$total = $query->total('id');
		}
		foreach ($rows as &$row) {
			$from_cur        = Currency::init($row['from_currency']);
			$to_cur          = Currency::init($row['to_currency']);
			$row['discount'] = ($row['discount']/10000*100).'%';
			$row['amount']   = $from_cur->fromUint($row['amount']).$from_cur->symbol;
			$row['total']    = $from_cur->fromUint($row['total']).$from_cur->symbol;
			$row['amount1']  = $to_cur->fromUint($row['amount1']).$to_cur->symbol;
		}
		$data['rows']  = $rows;
		$data['total'] = $total;
		return view($data);
	}
}