<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\pay;

use wallet\classes\model\WalletPayAccount;
use wulaphp\form\FormTable;

/**
 * 支付渠道基类.
 *
 * @package wallet\pay
 */
abstract class PayChannel {
	protected $error   = null;
	protected $account = [];

	public function last_error(): ?string {
		return $this->error;
	}

	/**
	 * 支付渠道ID.
	 *
	 * @return string
	 */
	public abstract function getId(): string;

	/**
	 * 支付通道名称
	 * @return string
	 */
	public abstract function getName(): string;

	/**
	 * 根据渠道获取一个账号信息
	 * @return array
	 */
	public function getAccounts(string $account): array {
		$id       = $this->getId();
		$model    = new WalletPayAccount();
		$accounts = $model->find(['channel' => $id, 'account' => $account, 'status' => 1, 'deleted' => 0])->ary();
		$accounts = $accounts ? $accounts : [];
		if ($accounts) {
			$this->account = @json_decode($accounts['options'], true);
		}

		return $accounts;
	}

	public function getConfigForm(): ?FormTable {
		return null;
	}

	public abstract function pay(string $account, array $withdraw_info): string;

	public abstract function validate($account): bool;
}