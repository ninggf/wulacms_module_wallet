<?php

namespace wallet;

use backend\classes\DashboardUI;
use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\auth\AclResourceManager;

/**
 * wallet
 *
 * @package wallet
 */
class WalletModule extends CmfModule {
	public function getName() {
		return '用户钱包';
	}

	public function getDescription() {
		return '管理会员的充值，提现，消费，转账等等功能。支持多币种（法币，积分，代币等），具体见说明文档。';
	}

	public function getHomePageURL() {
		return 'https://www.wulacms/modules/wallet';
	}

	public function getVersionList() {
		$v['1.0.0'] = '开始啦';

		return $v;
	}

	public function getAuthor() {
		return 'Leo Ning';
	}

	/**
	 * @param \backend\classes\DashboardUI $ui
	 *
	 * @bind dashboard\initUI
	 */
	public static function initUI(DashboardUI $ui) {
		$apps     = $ui->getMenu('apps');
		$passport = whoami('admin');
		if ($passport->cando('m:wallet')) {
			$app            = $apps->getMenu('wallet', '用户钱包');
			$app->icon      = '&#xe659;';
			$app->iconCls   = 'layui-icon';
			$app->iconStyle = 'color: orange';

			$page1              = $app->getMenu('stat', '账户总览', 1);
			$page1->icon        = '&#xe770;';
			$page1->iconCls     = 'layui-icon';
			$page1->data['url'] = App::url('wallet');

			$page2              = $app->getMenu('out', '支出记录', 2);
			$page2->icon        = '&#xe6b2;';
			$page2->iconCls     = 'layui-icon';
			$page2->iconStyle     = 'color:green';
			$page2->data['url'] = App::url('wallet/out');

			$page3              = $app->getMenu('deposit', '收入记录', 3);
			$page3->icon        = '&#xe6b2;';
			$page3->iconCls     = 'layui-icon';
			$page3->iconStyle     = 'color:red';
			$page3->data['url'] = App::url('wallet/deposit');

			$page4              = $app->getMenu('with', '提现管理', 3);
			$page4->icon        = '&#xe65e;';
			$page4->iconCls     = 'layui-icon';
			$page4->iconStyle     = 'color:red';
			$page4->data['url'] = App::url('wallet/withdraw');
		}
	}

	/**
	 * @param \wulaphp\auth\AclResourceManager $mgr
	 *
	 * @bind rbac\initAdminManager
	 */
	public static function initAcl(AclResourceManager $mgr) {
		$res = $mgr->getResource('wallet', '钱包', 'm');
		$res->addOperate('set', '通道管理');
		$res->addOperate('out', '支出记录');
		$res->addOperate('deposit', '收入记录');
		$cur = $mgr->getResource('wallet/currency', '币种管理', 'm');
		$cur->addOperate('set', '配置');

		$res = $mgr->getResource('wallet/deposit', '充值管理', 'm');
		$res->addOperate('check', '手工入账');

		$res = $mgr->getResource('wallet/withdraw', '提现管理', 'm');
		$res->addOperate('approve', '审核');
		$res->addOperate('pay', '支付');
		$res->addOperate('refuse', '拒绝');
	}
}

App::register(new WalletModule());
// end of bootstrap.php