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
/**
 * 支付渠道基类.
 *
 * @package wallet\pay
 */
abstract class PayChannel {
	/**
	 * 充值渠道ID.
	 *
	 * @return string
	 */
	public abstract function getId(): string;

	/**
	 * 充值通道名称
	 * @return string
	 */
	public abstract function getName(): string;

	/**
	 * 对账.
	 *
	 * @param array $order
	 *
	 * @return bool
	 */
	public abstract function check(array $order): bool;

	/**
	 * 获取支付URL.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public abstract function getPayURL(array $data): string;

	/**
	 * 是否有效
	 * @return bool
	 */
	public abstract function isValid(): bool;
}