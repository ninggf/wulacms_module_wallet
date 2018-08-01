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

use wulaphp\conf\ConfigurationLoader;

class Currency implements \ArrayAccess {
	protected static $currencyConf;
	protected static $currencies = [];
	protected        $id;
	protected        $decimals;//精度
	protected        $realdec;//
	protected        $scale      = 6;//最大面值数位精度
	protected        $myConf;

	/**
	 * @param string $currency
	 *
	 * @return null|\wallet\classes\Currency
	 */
	public static function init(string $currency): ?Currency {
		if (isset(self::$currencies[ $currency ])) {
			return self::$currencies[ $currency ];
		}
		if (!self::$currencyConf) {
			self::$currencyConf = ConfigurationLoader::loadFromFile('currency')->toArray();
		}
		if (!isset(self::$currencyConf[ $currency ])) {
			return null;
		}
		self::$currencies[ $currency ] = new Currency($currency, self::$currencyConf[ $currency ]);

		return self::$currencies[ $currency ];
	}

	/**
	 * 币种实例列表.
	 *
	 * @return \wallet\classes\Currency[]
	 */
	public static function currencies(): array {
		if (!self::$currencyConf) {
			self::$currencyConf = ConfigurationLoader::loadFromFile('currency')->toArray();
		}

		foreach (self::$currencyConf as $cur => $cfg) {
			self::init($cur);
		}

		return self::$currencies;
	}

	/**
	 * Currency constructor.
	 *
	 * @param string $currency
	 * @param array  $cnf
	 */
	public function __construct(string $currency, array $cnf) {
		$this->id                 = $currency;
		$this->myConf             = array_merge([
			'name'     => $currency,
			'symbol'   => strtolower($currency),
			'withdraw' => 0,
			'decimals' => 3,
			'scale'    => 6,
			'rate'     => 0,
			'types'    => []//收入类型
		], $cnf);
		$this->myConf['id']       = $currency;
		$this->decimals           = intval($this->myConf['decimals']);
		$this->scale              = intval($this->myConf['scale']);
		$this->realdec            = bcpow(10, $this->decimals);
		$this->myConf['decimals'] = $this->decimals;
	}

	/**
	 * 从最大面值单位转为最小面值单位.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public function toUint(string $value): ?string {
		if (!preg_match('/^(0|[1-9]\d*)(\.\d+)?$/', $value)) return null;

		return bcmul($value, $this->realdec);
	}

	/**
	 * 从最小面值单位转为最大面值单位.
	 *
	 * @param string   $value
	 * @param int|null $scale
	 *
	 * @return string
	 */
	public function fromUint(string $value, int $scale = null): string {
		if (!$this->realdec) return $value;
		if (!preg_match('/^([1-9]\d*)$/', $value)) return '0';
		$scale = $scale ?? $this->scale;
		$amount =  bcdiv($value, $this->realdec, $scale);
		if (strpos($amount, '.') > 0) {
			$amount = rtrim(rtrim($amount, '0'), '.');
		}
		return $amount;
	}

	/**
	 * 检查收入类型是否可用.
	 *
	 * @param string $type
	 *
	 * @return null|array
	 */
	public function checkType(string $type): ?array {
		if (empty($type)) return null;
		$cfg = $this->myConf['types'][ $type ] ?? false;

		return $cfg && is_array($cfg) && isset($cfg['name']) ? array_merge(['withdraw' => 0], $cfg) : null;
	}

	/**
	 * 兑换金额.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $amount
	 *
	 * @return null|string
	 */
	public function exchangeAmount(Currency $currency, string $amount): ?string {
		$fromId = 'from' . $this->id;
		$froms  = $currency['types'];
		if ($this->myConf['rate'] > 0 && $currency->myConf['rate'] > 0 && isset($froms[ $fromId ])) {
			//先把本币除以兑换比例换成中间币X，然后把中间币X乘以目标币兑换比例得到目标币数量
			return bcdiv(bcmul($this->toUint($amount), $currency['rate']), $this->myConf['rate'], 0);
		}

		return null;
	}

	public function offsetExists($offset) {
		return isset($this->myConf[ $offset ]);
	}

	public function offsetGet($offset) {
		return $this->myConf[ $offset ] ?? null;
	}

	public function offsetSet($offset, $value) {
		//cannot set runtime
	}

	public function offsetUnset($offset) {
		//cannot unset runtime
	}

	public function __toString() {
		return $this->id;
	}
}