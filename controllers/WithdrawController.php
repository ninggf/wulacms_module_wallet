<?php

namespace wallet\controllers;

use backend\classes\IFramePageController;
use backend\form\BootstrapFormRender;
use wallet\classes\Currency;
use wallet\classes\exception\WalletException;
use wallet\classes\form\AccountForm;
use wallet\classes\form\WithdrawRefuseForm;
use wallet\classes\model\WalletPayAccount;
use wallet\classes\model\WalletWithdrawOrder;
use wallet\classes\Wallet;
use wallet\pay\Pay;
use wulaphp\io\Ajax;

/**
 * 默认控制器.
 * @acl m:wallet/withdraw
 */
class WithdrawController extends IFramePageController {
	/**
	 * 默认控制方法.
	 */
	private $groups = ['P' => '申请中', 'R' => '拒绝', 'A' => '审核通过', 'D' => '已付款'];

	/**
	 * @param string $currency
	 *
	 * @return \wulaphp\mvc\view\View
	 * @throws \Exception
	 */
	public function index(string $currency) {
		$data['groups']   = $this->groups;
		$data['currency'] = $currency;

		return $this->render($data);
	}

	public function data($currency) {
		$table = new WalletWithdrawOrder();
		$query = $table->select()->page()->sort();

		$where            = [];
		$where['deleted'] = 0;
		$q                = rqst('q');
		$count            = rqst('count');
		$status           = rqst('status', '');
		if ($status) {
			$where['status'] = $status;
		}
		$start_time = rqst('start_time');
		if ($start_time) {
			$where['create_time >'] = strtotime($start_time);
		}
		$end_time = rqst('end_time');
		if ($end_time) {
			$where['create_time <'] = strtotime($end_time);
		}
		$where['currency'] = $currency;
		if ($q) {
			$where['user_id'] = $q;
		}
		$query->where($where);
		$rows = $query->toArray();
		$cur  = Currency::init($currency);
		foreach ($rows as &$row) {
			$row['status_th'] = $this->groups[ $row['status'] ];

			$row['amount'] = $cur->fromUint($row['amount']);
		}
		//权限设置
		$data['canApprove'] = $this->passport->cando('approve:wallet/withdraw');
		$data['canPay']     = $this->passport->cando('pay:wallet/withdraw');
		$data['canRefuse']  = $this->passport->cando('refuse:wallet/withdraw');
		$data['items']      = $rows;
		$data['total']      = $count ? $query->total('id') : '';

		return view($data);
	}

	/**
	 * @param string $opt
	 * @param int    $id
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 * @throws \Exception
	 */
	public function change($opt = '', $id = 0) {
		//权限控制
		$canApprove = $this->passport->cando('approve:wallet/withdraw');
		$canPay     = $this->passport->cando('pay:wallet/withdraw');
		if (!$canApprove) {
			return Ajax::error('抱歉，你没有审核权限');
		}
		if (!$canPay) {
			return Ajax::error('抱歉，你没有支付权限');
		}
		$wid = (int)$id;
		$op  = trim($opt);
		if (!$wid || !$op) {
			return Ajax::error('参数错误,请联系开发人员!');
		}
		$mod = new WalletWithdrawOrder();
		$row = $mod->get($wid)->ary();
		if (!$row['amount']) {
			return Ajax::error('记录不存,请刷新后重试!');
		}
		$op_list = ['pass' => '通过', 'refuse' => '拒绝', 'pay' => '支付'];
		if (!isset($op_list[ $op ])) {
			return Ajax::error('操作异常,请刷重试!');
		}
		$op_zh     = $op_list[ $op ];
		$op_status = ['pass' => 'A', 'refuse' => 'R', 'pay' => 'D'];
		//初始化钱包 币种
		$wallet   = Wallet::connect($row['user_id']);
		$currency = $wallet->open($row['currency']);

		$status = $op_status[ $op ];
		$ret    = false;
		//审核信息
		if ($status == 'A') {
			$ret = $wallet->approve($currency, $row['id'], $status, $this->passport->uid);
		}
		//支付
		if ($status == 'D') {
			//根据渠道进行支付
			$ret = $wallet->pay($currency, $row['id'], $this->passport->uid, '', '');
		}
		if ($ret) {
			return Ajax::reload('#table', $op_zh . '操作成功');
		} else {
			return Ajax::error($op_zh . '操作失败!');
		}
	}

	public function account($id) {
		$wid = (int)$id;
		$mod = new WalletWithdrawOrder();
		$row = $mod->get($wid)->ary();
		if ($id) {
			$model                = new WalletPayAccount();
			$data['account']      = $model->findAll(['channel' => $row['channel']], 'id,account')->toArray();
			$Pay                  = Pay::getChannel($row['channel']);
			$data['channel_name'] = $Pay->getName();
		}
		$data['id'] = $id;

		return view($data);
	}

	/**
	 * @param $id
	 * @param $account
	 *
	 * @return \wulaphp\mvc\view\JsonView
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function pay($id, $account) {
		//权限控制
		$canPay = $this->passport->cando('pay:wallet/withdraw');
		if (!$canPay) {
			return Ajax::error('抱歉，你没有支付权限');
		}
		$wid = (int)$id;
		if (!$wid || !$account) {
			return Ajax::error('参数错误,请联系开发人员!');
		}
		$mod = new WalletWithdrawOrder();
		$row = $mod->get($wid)->ary();
		if (!$row['amount']) {
			return Ajax::error('记录不存在,请刷新后重试!');
		}
		//初始化钱包 币种
		$wallet   = Wallet::connect($row['user_id']);
		$currency = $wallet->open($row['currency']);
		try {
			$rst = $wallet->pay($currency, $id, $row['user_id'], $row['channel'], $account);
			if ($rst) {
				return Ajax::reload('#table', '支付操作成功');
			}
		} catch (WalletException $e) {
			return Ajax::error($e->getMessage());
		}

	}

	/**
	 * @param int $id
	 *
	 * @return  mixed
	 */
	public function refuse($id = 0) {
		$wid = (int)$id;
		if (!$wid) {
			return Ajax::error('参数错误,请联系开发人员!');
		}
		$mod = new WalletWithdrawOrder();
		$row = $mod->get($wid)->ary();
		if (!$row['amount']) {
			return Ajax::error('记录不存,请刷新后重试!');
		}
		$form         = new WithdrawRefuseForm(true);
		$data['id']   = $wid;
		$data['form'] = BootstrapFormRender::v($form);

		return view($data);
	}

	/**
	 * @return \wulaphp\mvc\view\JsonView
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function save_refuse() {
		$canRefuse = $this->passport->cando('refuse:wallet/withdraw');
		if (!$canRefuse) {
			return Ajax::error('抱歉,你没有拒绝权限');
		}
		$wid = (int)rqst('id', 0);
		if (!$wid) {
			return Ajax::error('参数错误,请联系开发人员!');
		}
		$mod = new WalletWithdrawOrder();
		$row = $mod->get($wid)->ary();
		if (!$row['amount']) {
			return Ajax::error('记录不存,请刷新后重试!');
		}
		$wallet   = Wallet::connect($row['user_id']);
		$currency = $wallet->open($row['currency']);
		$ret      = $wallet->approve($currency, $wid, 'R', $this->passport->uid, trim(rqst('msg')));

		if ($ret) {
		    fire('wallet\refuseWithdraw',$wid,$row['user_id'],$row['amount']);
			return Ajax::success(['message' => '操作成功']);
		} else {
			return Ajax::error('操作失败!');
		}
	}
}