<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\classes;

abstract class BusinessSubject {
	/**
	 * 业务ID.
	 * @return string
	 */
	public abstract function getId(): string;

	/**
	 * 业务明细.
	 *
	 * @param string $subjectId 业务ID
	 * @param bool   $isDeposit 是否是收入
	 *
	 * @return null|string
	 */
	public abstract function detail(string $subjectId, bool $isDeposit = true): ?string;
}