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
 * Time: 2018/7/23 下午1:46
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\WalletOutlayLog;
use wallet\classes\Wallet;

/**
 * 默认控制器.
 * @acl m:wallet
 */
class OutController extends IFramePageController {

	public function index($currncy = '', $user_id = '') {
		$data['currency'] = $currncy;
		$data['user_id']  = $user_id;
		$data['subjects'] = Wallet::subjects();

		return $this->render($data);
	}

	public function data($currency = '', $user_id = 0, $count = '') {
		$data = [];
		if ($user_id) {
			$where['user_id'] = $user_id;
		}
		if ($currency) {
			$model             = new WalletOutlayLog();
			$where['currency'] = $currency;
			$subject           = rqst('subject');
			if ($subject != '') {
				$where['subject'] = $subject;
			}
			$query = $model->select('*')->where($where)->page()->sort();
			$rows  = $query->toArray();
			$total = '';
			if ($count) {
				$total = $query->total('id');
			}
			$cur = Currency::init($currency);
			foreach ($rows as &$row) {
				$row['amount'] = $cur->fromUint($row['amount']);
			}
			$data['rows']     = $rows;
			$data['total']    = $total;
			$data['subjects'] = Wallet::subjects();
		}

		return view($data);
	}
}