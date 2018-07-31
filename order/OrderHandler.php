<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\order;
/**
 * 订单处理器基类.
 *
 * @package wallet\order
 */
abstract class OrderHandler {
	/**
	 * 订单处理器ID.
	 *
	 * @return string
	 */
	public abstract function getId(): string;

	/**
	 * 订单处理器名称.
	 *
	 * @return string
	 */
	public abstract function getName(): string;

	/**
	 * 对账成功.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public abstract function onSuccess(array $data): bool;

	/**
	 * 取消支付.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public abstract function onCancel(array $data): bool;

	/**
	 * 对账失败.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public abstract function onFail(array $data): bool;

	/**
	 * 查看订单明细.
	 *
	 * @param array $data
	 *
	 * @return null|string
	 */
	public function viewOrder(array $data): ?string {
		return null;
	}
}