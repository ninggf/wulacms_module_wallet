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

use wallet\classes\exception\WalletCheckException;
use wallet\classes\exception\WalletException;
use wallet\classes\exception\WalletLockedException;
use wulaphp\app\App;
use wulaphp\conf\Configuration;
use wulaphp\conf\ConfigurationLoader;
use wulaphp\db\DatabaseConnection;
use wulaphp\db\TableLocker;
use wulaphp\io\Request;

/**
 * 用户钱包.
 *
 * @package wallet\classes
 */
class Wallet {
	/**@var Configuration */
	protected static $walletConf;
	protected static $subjects;
	protected        $uid;
	/**@var DatabaseConnection */
	protected $walletdb;
	protected $locked      = 0;//是否锁定
	private   $tableLocked = false;//当前账户是否通过for update锁定.

	/**
	 * Wallet constructor.
	 *
	 * @param int $userid 用户ID
	 *
	 * @throws \Exception
	 */
	private function __construct(int $userid) {
		$this->uid = $userid;
		//连接数据库
		$this->walletdb = $this->realdb($userid);
	}

	/**
	 * 项目
	 * @return array
	 */
	public static function subjects(): array {
		if (!self::$subjects) {
			self::$walletConf = ConfigurationLoader::loadFromFile('wallet');
			$cfg              = self::$walletConf->toArray();
			$subjects         = $cfg['subjects'] ?? [];
			if (!is_array($subjects)) {
				$subjects = [
					'despoit'  => [
						'name'   => '充值',
						'income' => 1,
						'outlay' => 0
					],
					'withdraw' => [
						'name'   => '提现',
						'outlay' => 1,
						'income' => 0
					],
					'exchange' => [
						'name'   => '兑换',
						'outlay' => 1,
						'income' => 1
					]
				];
			}
			self::$subjects = $subjects;
		}

		return self::$subjects;
	}

	/**
	 * 连接用户钱包.
	 *
	 * @param int $userid 用户ID
	 *
	 * @return \wallet\classes\Wallet
	 * @throws \wallet\classes\exception\WalletException
	 * @throws \Exception
	 */
	public static function connect(int $userid): Wallet {
		static $wallets = [];
		if (self::$walletConf === null) {
			self::subjects();
		}

		if (!isset($wallets[ $userid ])) {
			$wallet = new Wallet($userid);
			$wallet->_connect();
			$wallets[ $userid ] = $wallet;
			if ($wallet->locked) {
				log_info($userid . ' wallet is locked!', 'wallet');
			}
		}

		return $wallets[ $userid ];
	}

	/**
	 * 钱包是否被锁定.
	 *
	 * @return bool
	 */
	public function isLocked(): bool {
		return $this->locked == 1;
	}

	/**
	 * 打开特定币种账户
	 *
	 * @param string $currency
	 *
	 * @return null|\wallet\classes\Currency
	 */
	public function open(string $currency): ?Currency {
		$curr = Currency::init($currency);
		if ($curr) {
			//打开wallet中的currency账户.
			$rst = $this->walletdb->trans(function (DatabaseConnection $db) use ($curr, $currency) {
				$account = $db->queryOne('SELECT user_id FROM {wallet} WHERE user_id=%d AND currency=%s', $this->uid, $currency);
				if ($account) {
					return true;
				} else {
					$rst = $db->cudx('INSERT INTO {wallet} (user_id,currency) VALUES (%d,%s)', $this->uid, $currency);
					if ($rst) {
						return true;
					}
					throw new \Exception('Cannot create ' . $currency . ' wallet account for user ' . $this->uid);
				}
			}, $err);

			if ($rst) {
				return $curr;
			} else if ($err) {
				log_error('error occurred while opening current:' . $err, 'wallet');
			}
		}

		return null;
	}

	/**
	 * 收入.
	 *
	 * @param \wallet\classes\Currency $currency  币种
	 * @param string                   $amount    数量
	 * @param string                   $type      类型
	 * @param string                   $subject   主题，最多16个字符（数字，字母，下划线，中划线）
	 * @param string                   $subjectid 主题ID，最多48个字符（数字，字母，下划线，中划线）
	 *
	 * @return bool
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function deposit(Currency $currency, string $amount, string $type, string $subject, string $subjectid): bool {
		//检查类型
		$typeCfg = $currency->checkType($type);
		if (!$typeCfg) throw new WalletException('未知的收入类型:' . $type);

		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) throw new WalletException('充值金额不正确:' . $amount);
		//检查主题
		if (!preg_match('/^[a-z][\w\d\-_]{0,15}$/i', $subject)) throw new WalletException('subject格式不正确:' . $subject);
		if (!preg_match('/^[a-z0-9][a-z\d\-_]{0,47}$/i', $subject)) throw new WalletException('subjectid格式不正确:' . $subjectid);
		if (!isset(self::$subjects[ $subject ])) throw new WalletException('未定义的主题:' . $subject);

		try {
			if (!$this->walletdb->start()) {
				return false;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户");
			}
			//收入数据
			$deposit['id']           = $this->generateDepositId($currency, $type);
			$deposit['user_id']      = $this->uid;
			$deposit['currency']     = $currency['id'];
			$deposit['type']         = $type;
			$deposit['withdrawable'] = $typeCfg['withdraw'] ? 1 : 0;
			$deposit['amount']       = $ramount;
			$deposit['subject']      = $subject;
			$deposit['subjectid']    = $subjectid;
			$deposit['ip']           = Request::getIp();
			$deposit['create_time']  = $deposit['update_time'] = time();
			$deposit['create_uid']   = $deposit['update_uid'] = 0;

			//真正收入表
			$depositTable = $this->realtb('wallet_deposit_log', $currency['id']);
			$sql          = $this->walletdb->insert($deposit)->into($depositTable);
			$rst          = $sql->exec(true);
			if (!$rst) {
				throw new WalletException('无法更新数据库表:' . $sql->lastError());
			}
			//汇总数据
			$wallet['amount'] = imv('amount+' . $ramount);
			if ($deposit['withdrawable']) {
				//增加可提现余额
				$wallet['balance1'] = imv('balance1+' . $ramount);
			} else {
				//增加其它余额
				$wallet['balance'] = imv('balance+' . $ramount);
			}
			$wallet['update_time'] = $deposit['create_time'];
			$rst                   = $this->walletdb->update('{wallet}')->set($wallet)->where([
				'user_id'  => $this->uid,
				'currency' => $currency['id']
			])->exec(true);

			if (!$rst) {
				throw new WalletException("无法更新数据库表(wallet)");
			}

			return $this->walletdb->commit();
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 消费.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $amount
	 * @param string                   $subject
	 * @param string                   $subjectid
	 *
	 * @return bool
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function outlay(Currency $currency, string $amount, string $subject, string $subjectid): bool {
		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) throw new WalletException('消费金额不正确:' . $amount);
		//检查主题
		if (!preg_match('/^[a-z][\w\d\-_]{0,15}$/i', $subject)) throw new WalletException('subject格式不正确:' . $subject);
		if (!preg_match('/^[a-z0-9][a-z\d\-_]{0,47}$/i', $subject)) throw new WalletException('subjectid格式不正确:' . $subjectid);
		if (!isset(self::$subjects[ $subject ])) throw new WalletException('未定义的主题:' . $subject);
		try {
			if (!$this->walletdb->start()) {
				return false;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户");
			}
			if ($this->locked) {
				throw new WalletLockedException("用户钱包被锁定");
			}
			$info = $this->getInfo($currency);
			if (!$info) {
				throw new WalletException("用户账户不存在");
			}
			$balance  = $info['balance'];
			$balance1 = $info['balance1'];
			$rbalance = bcadd($balance, $balance1);
			if ($rbalance < $ramount) {
				throw new WalletException("余额不足");
			}

			//更新余额 ($balance - $ramount)
			$sub = bcsub($balance, $ramount);
			if ($sub >= 0) {
				$rbalance  = imv('balance - ' . $ramount);//有的剩
				$rbalance1 = null;//可提现金额无需修改
			} else {
				$rbalance  = imv('balance - ' . $balance);//没的剩
				$rbalance1 = imv('balance1 - ' . bcmul($sub, '-1', 0));
			}
			$outlay['id']          = $this->generateOutlayId($currency);
			$outlay['user_id']     = $this->uid;
			$outlay['currency']    = $currency['id'];
			$outlay['amount']      = $ramount;
			$outlay['subject']     = $subject;
			$outlay['subjectid']   = $subjectid;
			$outlay['ip']          = Request::getIp();
			$outlay['create_time'] = $outlay['update_time'] = time();
			$outlay['create_uid']  = $outlay['update_uid'] = 0;
			//真正的支出记录表
			$outlayTable = $this->realtb('wallet_outlay_log', $currency['id']);
			$sql         = $this->walletdb->insert($outlay)->into($outlayTable);
			$rst         = $sql->exec(true);
			if (!$rst) {
				throw new WalletException("无法更新数据库表($outlayTable):" . $sql->lastError());
			}
			//汇总数据
			$wallet['amount']  = imv('amount - ' . $ramount);
			$wallet['balance'] = $rbalance;
			if ($rbalance1) {
				$wallet['balance1'] = $rbalance1;
			}
			$wallet['update_time'] = $outlay['create_time'];
			$rst                   = $this->walletdb->update('{wallet}')->set($wallet)->where([
				'user_id'  => $this->uid,
				'currency' => $currency['id']
			])->exec(true);
			if (!$rst) {
				throw new WalletException("无法更新数据库表");
			}

			return $this->walletdb->commit();
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 申请提现.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $amount
	 * @param string                   $user_name
	 * @param string                   $bank_name
	 * @param string                   $bank_account
	 *
	 * @return string 提现编号
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function withdraw(Currency $currency, string $amount, string $user_name, string $bank_name, string $bank_account): ?string {
		if (!$currency['withdraw']) {
			throw new WalletException('不可提现的币种:' . $currency['id']);
		}
		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) throw new WalletException('提现金额不正确:' . $amount);
		if (!isset(self::$subjects['withdraw'])) throw new WalletException('未定义的主题:withdraw');
		try {
			if (!$this->walletdb->start()) {
				return null;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户");
			}
			if ($this->locked) {
				throw new WalletLockedException("用户钱包被锁定");
			}
			$info = $this->getInfo($currency);
			if (!$info) {
				throw new WalletException("用户账户不存在");
			}
			$balance1 = $info['balance1'];
			if ($balance1 < $ramount) {
				throw new WalletException("可提现余额不足");
			}
			$withdraw['user_id']      = $this->uid;
			$withdraw['currency']     = $currency['id'];
			$withdraw['deleted']      = 0;
			$withdraw['amount']       = $ramount;
			$withdraw['status']       = 'P';
			$withdraw['user_name']    = $user_name;
			$withdraw['bank_account'] = $bank_account;
			$withdraw['bank_name']    = $bank_name;
			$withdraw['create_uid']   = 0;
			$withdraw['create_time']  = time();
			$withdraw['ip']           = Request::getIp();
			//SQL
			$withdrawTable = $this->realtb('wallet_withdraw_order', $currency['id']);
			$sql           = $this->walletdb->insert($withdraw)->into($withdrawTable);
			$rst           = $sql->exec(true);
			if (!$rst) {
				throw new WalletException("提现失败:[{$this->uid}] " . $sql->lastError());
			}
			$withdrawId = $sql->lastId('id');
			//更新总表
			$wallet['balance1']    = imv('balance1-' . $ramount);
			$wallet['frozen']      = imv('frozen+' . $ramount);
			$wallet['update_time'] = time();
			$rst                   = $this->walletdb->update('{wallet}')->set($wallet)->where([
				'user_id'  => $this->uid,
				'currency' => $currency['id']
			])->exec(true);

			if (!$rst) {
				throw new WalletException("无法更新数据库表(wallet):[{$this->uid}]");
			}

			return $this->walletdb->commit() ? $withdrawId : null;
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 审核提现.
	 *
	 * @param \wallet\classes\Currency $currency   币种
	 * @param string                   $withdrawId 提现ID
	 * @param string                   $status     审核状态：R-拒绝；A-同意
	 * @param string                   $uid        审核人
	 * @param string                   $rejectErr  拒绝原因
	 *
	 * @return bool
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function approve(Currency $currency, string $withdrawId, string $status, string $uid, string $rejectErr = ''): bool {
		if ($status != 'R' && $status != 'A') {
			return false;
		}
		try {
			if (!$this->walletdb->start()) {
				return false;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户:[{$this->uid}]");
			}
			if ($this->locked) {
				throw new WalletLockedException("用户钱包被锁定");
			}
			//正真的提现表
			$withdrawTable = $this->realtb('wallet_withdraw_order', $currency['id']);
			$withdraw      = $this->walletdb->queryOne('SELECT id,status,amount FROM ' . $withdrawTable . ' WHERE id = %s AND status = %s', $withdrawId, 'P');
			if (!$withdraw) {
				throw new WalletException('提现申请记录不存在:' . $withdrawId);
			}
			$amount              = $withdraw['amount'];
			$data['status']      = $status;
			$data['approve_uid'] = intval($uid);
			if ($rejectErr) {
				$data['reject_msg'] = $rejectErr;
			} else {
				$data['reject_msg'] = '';
			}
			$data['approve_time'] = time();

			$sql = $this->walletdb->update($withdrawTable)->set($data)->where($withdraw);
			$rst = $sql->exec(true);
			if (!$rst) {
				throw new WalletException('提现申请已变更:' . $withdrawId);
			}
			if ($status == 'R') {//拒绝时解锁冻结.
				$wallet['frozen']      = imv('frozen - ' . $amount);
				$wallet['balance1']    = imv('balance1 + ' . $amount);
				$wallet['update_time'] = time();
				$rst                   = $this->walletdb->update('{wallet}')->set($wallet)->where([
					'user_id'  => $this->uid,
					'currency' => $currency['id']
				])->exec(true);

				if (!$rst) {
					throw new WalletException("无法更新数据库表(wallet):[{$this->uid}]");
				}
			}

			return $this->walletdb->commit();
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 提现付款.
	 *
	 * @param \wallet\classes\Currency $currency   币种
	 * @param string                   $withdrawId 提现订单号
	 * @param string                   $uid        支付用户ID
	 * @param string                   $channel    支付通道
	 * @param string                   $txid       支付流水号
	 *
	 * @return bool
	 * @throws \wallet\classes\exception\WalletLockedException
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function pay(Currency $currency, string $withdrawId, string $uid, string $channel, string $txid): bool {
		try {
			if (!$this->walletdb->start()) {
				return null;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户:[{$this->uid}]");
			}
			if ($this->locked) {
				throw new WalletLockedException("用户钱包被锁定");
			}
			$withdrawTable = $this->realtb('wallet_withdraw_order', $currency['id']);
			$withdraw      = $this->walletdb->queryOne('SELECT amount FROM ' . $withdrawTable . ' WHERE id = %s AND status = %s', $withdrawId, 'A');
			if (!$withdraw) {
				throw new WalletException('可支付的提现申请记录不存在:' . $withdrawId);
			}
			$data['status']   = 'D';
			$data['channel']  = $channel;
			$data['tx_id']    = $txid;
			$data['pay_uid']  = intval($uid);
			$data['pay_time'] = time();
			$sql              = $this->walletdb->update($withdrawTable)->set($data)->where([
				'id'     => $withdrawId,
				'status' => 'A'
			]);
			$rst              = $sql->exec(true);
			if (!$rst) {
				throw new WalletException('提现申请已变更:' . $withdrawId);
			}
			//更新总表
			$wallet['amount']      = imv('amount - ' . $withdraw['amount']);
			$wallet['frozen']      = imv('frozen - ' . $withdraw['amount']);
			$wallet['update_time'] = time();
			$rst                   = $this->walletdb->update('{wallet}')->set($wallet)->where([
				'user_id'  => $this->uid,
				'currency' => $currency['id']
			])->exec(true);
			if (!$rst) {
				throw new WalletException("无法更新数据库表(wallet):[{$this->uid}]");
			}
			//添加一笔提现支出
			$outlay['id']          = $this->generateOutlayId($currency);
			$outlay['user_id']     = $this->uid;
			$outlay['currency']    = $currency['id'];
			$outlay['amount']      = $withdraw['amount'];
			$outlay['subject']     = 'withdraw';
			$outlay['subjectid']   = $withdrawId;
			$outlay['ip']          = Request::getIp();
			$outlay['create_time'] = $outlay['update_time'] = time();
			$outlay['create_uid']  = $outlay['update_uid'] = 0;
			//真正的支出记录表
			$outlayTable = $this->realtb('wallet_outlay_log', $currency['id']);
			$sql         = $this->walletdb->insert($outlay)->into($outlayTable);
			$rst         = $sql->exec(true);
			if (!$rst) {
				throw new WalletException("无法更新数据库表($outlayTable):" . $sql->lastError());
			}

			return $this->walletdb->commit();
		} catch (WalletLockedException $le) {
			$this->walletdb->rollback();
			throw $le;
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 币种间兑换
	 *
	 * @param \wallet\classes\Currency $currencyForm 原币种
	 * @param \wallet\classes\Currency $currencyTo   新币种
	 * @param string                   $amount       金额
	 * @param float                    $discount     折扣
	 *
	 * @return null|string
	 * @throws
	 */
	public function exchange(Currency $currencyForm, Currency $currencyTo, string $amount, float $discount = 1): ?string {
		$ramount = $currencyForm->exchangeAmount($currencyTo, $amount);
		if (!$ramount || $discount > 1 || $discount <= 0) {
			return null;
		}
		if (!isset(self::$subjects['exchange'])) throw new WalletException('未定义的主题:exchange');
		try {
			if (!$this->walletdb->start()) {
				return null;
			}
			//锁用户账户
			$walletMeta = $this->lock();
			if (!$walletMeta) {
				throw new WalletException("无法锁定用户账户:[{$this->uid}]");
			}
			if ($this->locked) {
				throw new WalletLockedException("用户钱包被锁定");
			}
			$amount1  = $currencyForm->toUint($amount);
			$discount = bcmul($discount, 10000, 0);
			$total    = bcdiv(bcmul($amount, $discount), 10000, 5);

			//创建兑换记录
			$exchange['id']            = $this->generateExchangeId();
			$exchange['user_id']       = $this->uid;
			$exchange['from_currency'] = $currencyForm['id'];
			$exchange['to_currency']   = $currencyTo['id'];
			$exchange['amount']        = $amount1;
			$exchange['discount']      = $discount;
			$exchange['total']         = $currencyForm->toUint($total);
			$exchange['amount1']       = $ramount;
			$exchange['rate1']         = $currencyForm['rate'];
			$exchange['rate2']         = $currencyTo['rate'];
			$exchange['create_time']   = time();
			$exchange['create_uid']    = 0;
			$sql                       = $this->walletdb->insert($exchange)->into('{wallet_exchange_log}');
			$rst                       = $sql->exec(true);
			if (!$rst) {
				throw new WalletLockedException("写入兑换表出错:" . $sql->lastError());
			}
			$subjectId = $exchange['id'];
			//写一笔记收入
			$ramount = $currencyTo->fromUint($ramount, 0);
			$rst     = $this->deposit($currencyTo, $ramount, 'from' . $currencyForm['id'], 'exchange', $subjectId);
			//写一笔支出
			$rst = $rst && $this->outlay($currencyForm, $total, 'exchange', $subjectId);
			if ($rst && $this->walletdb->commit()) {
				return $subjectId;
			} else {
				$this->walletdb->rollback();

				return null;
			}
		} catch (WalletLockedException $le) {
			$this->walletdb->rollback();
			throw $le;
		} catch (WalletException $we) {
			$this->walletdb->rollback();
			throw $we;
		} catch (\Exception $e) {
			$this->walletdb->rollback();
			throw new WalletException($e);
		}
	}

	/**
	 * 获取可用余额.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param int                      $type 0:可用余额;1:不可提现金额;2:可提现余额;3:账户总额;4:冻结
	 *
	 * @return string|null
	 * @throws \wallet\classes\exception\WalletCheckException
	 */
	public function getBalance(Currency $currency, int $type = 0): ?string {
		$acct = $this->getInfo($currency);
		if ($acct) {
			$amount   = $acct['amount'];
			$balance  = $acct['balance'];
			$balance1 = $acct['balance1'];
			$frozen   = $acct['frozen'];
			switch ($type) {
				case 0:
					return bcadd($balance, $balance1);
				case 1:
					return $balance;
				case 2:
					return $balance1;
				case 3:
					return $amount;
				default:
					return $frozen;
			}
		} else {
			return null;
		}
	}

	/**
	 * 获取钱包账户列表
	 * @return array
	 */
	public function getBalances(): array {
		$accts = $this->walletdb->query('SELECT amount,balance,balance1,frozen FROM {wallet} WHERE user_id = %d ORDER BY currency ASC', $this->uid);

		return $accts;
	}

	/**
	 * 获取账户信息.
	 *
	 * @param \wallet\classes\Currency $currency
	 *
	 * @return array|null
	 * @throws \wallet\classes\exception\WalletCheckException
	 */
	public function getInfo(Currency $currency): ?array {
		$acct = $this->walletdb->queryOne('SELECT amount,balance,balance1,frozen,account FROM {wallet} WHERE user_id = %d AND currency = %s', $this->uid, $currency['id']);
		if ($acct) {
			$amount   = $acct['amount'];
			$balance  = $acct['balance'];
			$balance1 = $acct['balance1'];
			$frozen   = $acct['frozen'];
			$camount  = bcadd(bcadd($balance, $balance1), $frozen);
			if ($amount != $camount) {
				log_warn($this->uid . " check fail:b($balance)+b1($balance1)+f($frozen)!=a($camount)", 'wallet');

				throw new WalletCheckException("b($balance)+b1($balance1)+f($frozen)!=a($camount)");
			}
			$acct['currency'] = $currency;
			$acct['user_id']  = $this->uid;

			return $acct;
		} else {
			return null;
		}
	}

	/**
	 * 新充值订单.
	 *
	 * @param \wallet\classes\Currency $currency   币种
	 * @param string                   $amount     金额
	 * @param string                   $order_type 业务订单类型
	 * @param string                   $order_id   订单编号
	 * @param string                   $spm        充值来源追踪
	 *
	 * @return null|string 充值订单号
	 * @throws \wallet\classes\exception\WalletException
	 */
	public function newDepositOrder(Currency $currency, string $amount, string $order_type, string $order_id, string $spm = ''): ?string {
		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) throw new WalletException('充值金额不正确:' . $amount);
		if (!isset(self::$subjects['deposit'])) throw new WalletException('未定义的主题:deposit');
		$data['user_id']     = $this->uid;
		$data['currency']    = $currency['id'];
		$data['amount']      = $ramount;
		$data['order_type']  = $order_type;
		$data['order_id']    = $order_id;
		$data['status']      = 'P';//付付款
		$data['channel']     = '';
		$data['spm']         = $spm;
		$data['create_time'] = time();
		$data['create_uid']  = 0;
		$data['ip']          = Request::getIp();
		$depositTable        = $this->realtb('wallet_deposit_order', $currency['id']);
		$sql                 = $this->walletdb->insert($data)->into($depositTable);
		$rst                 = $sql->exec(true);
		if ($rst) {
			return $sql->lastId('id');
		}
		log_warn($sql->lastError(), 'wallet.pay');

		return null;
	}

	/**
	 * 支付，一般用在收到第三方充值系统回调时确认.
	 *
	 * @param \wallet\classes\Currency $currency  币种
	 * @param string                   $depositId 充值订单编号
	 * @param string                   $amount    充值金额
	 * @param string                   $channel   充值通道
	 * @param string                   $tx_id     第三方流水号（用于对账）
	 *
	 * @return bool
	 */
	public function payDepositOrder(Currency $currency, string $depositId, string $amount, string $channel, string $tx_id): bool {
		if (empty($channel) || empty($tx_id) || empty($depositId)) {
			return false;
		}
		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) return false;
		$depositTable = $this->realtb('wallet_deposit_order', $currency['id']);
		$order        = $this->walletdb->queryOne('SELECT * FROM ' . $depositTable . ' WHERE id = %s AND status = %s', $depositId, 'P');
		if (!$order || $order['amount'] != $ramount) {
			return false;
		}
		$data['channel']         = $channel;
		$data['tx_id']           = $tx_id;
		$data['status']          = 'R';
		$data['pay_time']        = time();//支付时间
		$data['next_check_time'] = $data['pay_time'] + 30;//30秒后开始对账
		$data['check_count']     = 30;//剩余对账次数
		$sql                     = $this->walletdb->update($depositTable)->set($data)->where([
			'id'     => $depositId,
			'status' => 'P'
		]);
		$rst                     = $sql->exec(true);
		if ($rst) {
			return true;
		}
		log_warn($sql->lastError(), 'wallet.pay');

		return false;
	}

	/**
	 * 支付对账完成,用于第三方订单可查询时调用.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $depositId 订单编号
	 * @param string                   $amount    金额
	 *
	 * @return bool
	 */
	public function confirmDepositOrder(Currency $currency, string $depositId, string $amount): bool {
		//转换到最小面值单位
		$ramount = $currency->toUint($amount);
		if ($ramount === null) return false;
		$depositTable = $this->realtb('wallet_deposit_order', $currency['id']);
		$order        = $this->walletdb->queryOne('SELECT * FROM ' . $depositTable . ' WHERE id = %s AND status = %s', $depositId, 'R');
		if (!$order || $order['amount'] != $ramount) {
			return false;
		}
		if (!$this->walletdb->start()) {
			return false;
		}
		try {
			$lock = $this->lock();
			if (!$lock) {
				throw new WalletException('无法锁定账户');
			}

			//添加收入记录
			$rst = $this->deposit($currency, $amount, 'deposit', 'deposit', $depositId);
			if (!$rst) {
				throw new WalletException('无法添加收入记录');
			}

			//修改充值记录状态
			$data['status']     = 'A';//已入账
			$data['check_time'] = time();//入账时间
			$sql                = $this->walletdb->update($depositTable)->set($data)->where([
				'id'     => $depositId,
				'status' => 'R'
			]);
			$rst                = $sql->exec(true);
			if (!$rst) {
				throw new WalletException('订单已入账');
			}

			//通知业务逻辑处理充值情况
			fire('wallet\onDepositOrderConfirmed', $order);

			return $this->walletdb->commit();
		} catch (\Exception $e) {
			$this->walletdb->rollback();

			return false;
		}
	}

	/**
	 * 取消支付订单.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $depositId
	 *
	 * @return bool
	 */
	public function cancelDepositOrder(Currency $currency, string $depositId): bool {
		$depositTable = $this->realtb('wallet_deposit_order', $currency['id']);
		$order        = $this->walletdb->queryOne('SELECT * FROM ' . $depositTable . ' WHERE id = %s AND status = %s', $depositId, 'P');
		if (!$order) {
			return false;
		}
		//修改充值记录状态
		$data['status']     = 'C';//已取消
		$data['check_time'] = time();//取消时间
		$sql                = $this->walletdb->update($depositTable)->set($data)->where([
			'id'     => $depositId,
			'status' => 'P'
		]);
		$rst                = $sql->exec(true);
		if ($rst) {
			return true;
		}

		return false;
	}

	/**
	 * 生成交易流水号.
	 *
	 * @param \wallet\classes\Currency $currency
	 * @param string                   $type
	 *
	 * @return string|null 交易流水号
	 */
	public function generateDepositId(Currency $currency, string $type): ?string {
		$ids[] = date('YmdHi');
		$ids[] = $currency['id'];
		if (!$type) {
			return null;
		}
		$txid = $this->getNextTxid();
		if (!$txid) {
			return null;
		}
		$ids[] = $type;
		$ids[] = base_convert($this->uid, 10, 36);
		$ids[] = $txid;
		$id    = implode('-', $ids);

		return strlen($id) <= 44 ? $id : null;
	}

	/**
	 * 生成支出流水号.
	 *
	 * @param \wallet\classes\Currency $currency
	 *
	 * @return null|string
	 */
	public function generateOutlayId(Currency $currency): ?string {
		$ids[] = date('YmdHi');
		$ids[] = $currency['id'];
		$ids[] = base_convert($this->uid, 10, 36);
		$txid  = $this->getNextTxid();
		if (!$txid) {
			return null;
		}
		$ids[] = $txid;
		$id    = implode('-', $ids);

		return strlen($id) <= 34 ? $id : null;
	}

	/**
	 * 生成兑换ID
	 *
	 * @return null|string
	 */
	public function generateExchangeId(): ?string {
		$ids[] = date('YmdHi');
		$ids[] = base_convert($this->uid, 10, 36);
		$txid  = $this->getNextTxid();
		if (!$txid) {
			return null;
		}
		$ids[] = $txid;
		$id    = implode('-', $ids);

		return strlen($id) <= 25 ? $id : null;
	}

	/**
	 * 钱包表所在数据库.
	 *
	 * @param int|null $userid 用户id.
	 *
	 * @return \wulaphp\db\DatabaseConnection
	 * @throws \Exception
	 */
	public function realdb(?int $userid = null): DatabaseConnection {
		static $dbs = [];
		$userid = $userid ?? $this->uid;
		if (isset($dbs[ $userid ])) {
			return $dbs[ $userid ];
		}
		$db    = null;
		$dbMap = self::$walletConf->get('dbMap', 'default');
		if ($dbMap) {
			if ($dbMap instanceof \Closure) {
				$db = $dbMap(...[$userid]);
			} else if (is_string($dbMap)) {
				$db = $dbMap;
			}
			if ($db) {
				$dbs[ $userid ] = App::db($db);

				return $dbs[ $userid ];
			}
		}

		throw new \Exception('Cannot connect to wallet database!');
	}

	/**
	 * 币种记录所在真实的表
	 *
	 * @param string   $table    表名
	 * @param string   $currency 币种
	 * @param int|null $userid   用户ID（默认使用当前钱包的用户ID）
	 *
	 * @return string
	 */
	public function realtb(string $table, string $currency, int $userid = null): string {
		$userid   = $userid ?? $this->uid;
		$rtable   = $table;
		$tableMap = self::$walletConf->get('tableMap');
		if ($tableMap && $tableMap instanceof \Closure) {
			$table  = $tableMap(...[$table, $currency, $userid]);
			$rtable = $table ?? $rtable;
		}

		return '{' . $rtable . '}';
	}

	/**
	 * 获取账户锁(基于MySql的select for update机制).
	 *
	 * @return array|false
	 */
	public function lock() {
		if ($this->walletdb->inTrans()) {
			if ($this->tableLocked) {
				return $this->tableLocked;
			}
			$query             = $this->walletdb->select('locked,txid')->from('wallet_meta')->where(['user_id' => $this->uid]);
			$this->tableLocked = (new TableLocker($query))->lock();
			if ($this->tableLocked) {
				return $this->tableLocked;
			}
		}

		return false;
	}

	/**
	 * 获取下一个流水号.
	 *
	 * @return int|null
	 */
	public function getNextTxid(): ?int {
		$txid = $this->walletdb->queryOne('SELECT txid FROM {wallet_meta} WHERE user_id = %d AND locked = 0', $this->uid);
		if ($txid) {
			$id = $txid['txid'];
			if ($id > 999999) {
				$id = 0;
			}
			$id  += 1;
			$rst = $this->walletdb->cud('UPDATE {wallet_meta} SET txid = %d,update_time= %d WHERE user_id = %d AND txid = %d', $id, time(), $this->uid, $txid['txid']);
			if ($rst) {
				return $id;
			}
		}

		return null;
	}

	/**
	 * 连接钱包
	 *
	 * @return bool
	 * @throws \wallet\classes\exception\WalletException
	 */
	private function _connect(): bool {
		$rst = $this->walletdb->trans(function (DatabaseConnection $db) {
			$walletMata = $db->queryOne('SELECT locked FROM {wallet_meta} WHERE user_id = ' . $this->uid);
			if ($walletMata) {
				return $walletMata;
			} else {
				$rst = $db->cudx('INSERT INTO {wallet_meta} (user_id,locked,txid,update_time) VALUES (%d,0,0,%d)', $this->uid, time());
				if ($rst) {
					return ['locked' => 0];
				} else {
					throw new \Exception('Cannot init wallet meta for user ' . $this->uid);
				}
			}
		}, $error);

		if (!$rst) {
			throw new WalletException($error ?? 'Cannot connect to wallet!');
		}
		$this->locked = $rst['locked'];

		return true;
	}
}