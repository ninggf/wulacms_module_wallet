<?php

namespace wallet;

use wula\cms\CmfModule;
use wulaphp\app\App;

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
}

App::register(new WalletModule());
// end of bootstrap.php