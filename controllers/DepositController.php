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
 * Time: 2018/7/23 下午1:49
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\WalletDepositLog;
use wulaphp\conf\ConfigurationLoader;

/**
 * 默认控制器.
 * @acl m:wallet
 */
class DepositController extends IFramePageController {
	public function index($currncy, $user_id) {
		$data['currency'] = $currncy;
		$data['user_id']  = $user_id;
		$cfg                   = ConfigurationLoader::loadFromFile('currency');
		$data['currency_list'] = $cfg->toArray();

		return $this->render($data);
	}

	public function data($type = '', $user_id = 0, $count = '') {
		$model         = new WalletDepositLog();
		if ($user_id) {
			$where['user_id'] = $user_id;
		}
		if ($type) {
			$where['currency'] = $type;
		}
		$ctype = rqst('ctype');
		if ($ctype != '') {
			$where['type'] = $ctype;
		}
		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		foreach ($rows as &$row) {
           $cur = Currency::init($row['currency']);
           $row['amount'] = $cur->fromUint($row['amount']);
		}
		$total = '';
		if ($count) {
			$total = $query->total('id');
		}
		$data['rows']  = $rows;
		$data['total'] = $total;

		return view($data);
	}
}