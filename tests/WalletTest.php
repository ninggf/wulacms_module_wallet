<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\tests;

use PHPUnit\Framework\TestCase;
use wallet\classes\Currency;
use wallet\classes\exception\WalletException;
use wallet\classes\Wallet;
use wulaphp\conf\ConfigurationLoader;

class WalletTest extends TestCase {
	protected $backupGlobals          = false;
	protected $backupStaticAttributes = false;

	private static $db;

	public static function setUpBeforeClass() {
		$cfg = ConfigurationLoader::loadFromFile('currency');
		$cfg->setConfigs([
			'EOS' => [
				'name'     => 'EOSx',
				'symbol'   => 'eos',
				'withdraw' => 1,
				'decimals' => 3,
				'scale'    => 6,
				'rate'     => 1000,
				'types'    => [
					'test'    => [
						'name' => '签到'
					],
					'reward'  => [
						'name'     => '奖励',
						'withdraw' => true
					],
					'fromETH' => [
						'name' => '兑换'
					]
				]//收入类型
			],
			'ETH' => [
				'name'     => 'eth',
				'symbol'   => 'eth',
				'withdraw' => 1,
				'decimals' => 3,
				'scale'    => 6,
				'rate'     => 100,
				'types'    => [
					'deposit' => [
						'name'     => '充值',
						'withdraw' => 1
					],
					'fromEOS' => [
						'name' => 'EOS兑换'
					]
				]//收入类型
			]
		]);
		$wallet   = Wallet::connect(1);
		self::$db = $wallet->realdb();
	}

	/**
	 * @afterClass
	 */
	public static function tearDownAfterClass() {
		$tables = [
			'wallet',
			'wallet_meta',
			'wallet_deposit_log',
			'wallet_outlay_log',
			'wallet_withdraw_order',
			'wallet_deposit_order',
			'wallet_exchange_log'
		];
		foreach ($tables as $t) {
			self::$db->cud('delete from {' . $t . '}');
		}
		self::$db->commit();
		self::$db->commit();
	}

	public function testConnect() {
		$wallet = Wallet::connect(1);
		self::assertNotNull($wallet, 'wallet is null');
		self::assertTrue(!$wallet->isLocked(), 'wallet is locked');

		return $wallet;
	}

	/**
	 * @param $wallet
	 *
	 * @depends testConnect
	 * @return \wallet\classes\Currency
	 */
	public function testOpen(Wallet $wallet) {
		$cur = $wallet->open('EOS');
		self::assertNotNull($cur);
		$cur->setWallet($wallet);
		self::assertEquals('EOSx', $cur['name'], var_export($cur['name'], true));
		self::assertEquals('eos', $cur['symbol']);
		self::assertEquals(3, $cur['decimals']);

		$type = $cur->checkType('test');
		self::assertNotNull($type);
		self::assertEquals(0, $type['withdraw']);

		$type = $cur->checkType('reward');
		self::assertNotNull($type);
		self::assertTrue($type['withdraw']);

		return $cur;
	}

	/**
	 * @param $wallet
	 *
	 * @depends testConnect
	 * @return \wallet\classes\Currency
	 */
	public function testOpenEth(Wallet $wallet) {
		$cur = $wallet->open('ETH');
		self::assertNotNull($cur);
		$cur->setWallet($wallet);

		return $cur;
	}

	/**
	 * @param $cur1
	 * @param $cur2
	 *
	 * @depends testOpen
	 * @depends testOpenEth
	 */
	public function testExchangeTo(Currency $cur1, Currency $cur2) {
		// x = 1000eos, x = 100eth, eth = 10eos, eos=eth/10
		$eos2eth = $cur1->exchangeTo($cur2, 1);
		self::assertEquals('0.1', $cur2->fromUint($eos2eth, 1));
		$eth2eos = $cur2->exchangeTo($cur1, 1);
		self::assertEquals('10.0', $cur1->fromUint($eth2eos, 1));
	}

	/**
	 *
	 * @param \wallet\classes\Currency $cur
	 *
	 * @depends testOpen
	 */
	public function testConvert(Currency $cur) {
		self::assertEquals(1000, $cur->toUint(1));
		self::assertEquals('2.1', $cur->fromUint(2100, 1));
		self::assertNull($cur->fromUint('-1.abc'));
		self::assertNull($cur->toUint('a.1abc'));
	}

	/**
	 * @param \wallet\classes\Wallet $wallet
	 * @param                        $cur
	 *
	 * @depends testConnect
	 * @depends testOpen
	 */
	public function testGenerateId(Wallet $wallet, Currency $cur) {
		$id = $wallet->getNextTxid();
		self::assertNotNull($id, 'id is null');
		self::assertNotNull($wallet->generateDepositId($cur, 'test'), 'abc');
		self::assertNotNull($wallet->generateOutlayId($cur), 'def');
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends testConnect
	 * @depends testOpen
	 */
	public function testDepositErrorType(Wallet $wallet, Currency $cur) {
		try {
			$wallet->deposit($cur, 1.1, 'testx', 'test', '1');
		} catch (WalletException $e) {
			self::assertEquals('未知的收入类型:testx', $e->getMessage());
		}
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends testConnect
	 * @depends testOpen
	 */
	public function testDeposit(Wallet $wallet, Currency $cur) {
		$db = $wallet->realdb();

		$db->start();
		try {
			$rst = $wallet->deposit($cur, 10, 'test', 'test', time());
			self::assertTrue($rst);
			$amount = $wallet->getBalance($cur, 3);
			self::assertNotNull($amount);
			self::assertEquals($cur->toUint(10), $amount);

			$rst = $wallet->deposit($cur, 5, 'reward', 'reward', time());
			self::assertTrue($rst);
			$amount = $wallet->getBalance($cur, 3);
			self::assertNotNull($amount);
			self::assertEquals($cur->toUint(15), $amount);

			$balance = $wallet->getBalance($cur, 1);
			self::assertEquals($cur->toUint(10), $balance);
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint(5), $balance1);
			$db->commit();
		} catch (WalletException $we) {
			self::assertTrue(false, $we->getMessage());
			$db->rollback();
		}
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends testConnect
	 * @depends testOpen
	 * @depends testDeposit
	 */
	public function testOutlay(Wallet $wallet, Currency $cur) {
		$db = $wallet->realdb();
		try {
			$db->start();
			$rst = $wallet->outlay($cur, '12', 'buy', 'buy-1');
			self::assertTrue($rst);
			$amount = $wallet->getBalance($cur, 3);
			self::assertNotNull($amount);
			self::assertEquals($cur->toUint('3'), $amount);

			$balance = $wallet->getBalance($cur, 1);
			self::assertEquals($cur->toUint('0'), $balance);
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint('3'), $balance1);
			$db->commit();
		} catch (WalletException $we) {
			self::assertTrue(true, $we->getMessage());
			$db->rollback();
		}
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends testConnect
	 * @depends testOpen
	 * @depends testOutlay
	 */
	public function testWithdraw(Wallet $wallet, Currency $cur) {
		$db = $wallet->realdb();
		try {
			$db->start();
			$withdrawId = $wallet->withdraw($cur, '2', '宁广丰', 'EOS', '0x012332343434');
			self::assertNotNull($withdrawId);
			$amount = $wallet->getBalance($cur, 3);
			self::assertNotNull($amount);
			self::assertEquals($cur->toUint('3'), $amount);

			$balance = $wallet->getBalance($cur, 1);
			self::assertEquals($cur->toUint('0'), $balance);
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint('1'), $balance1);
			$frozen = $wallet->getBalance($cur, 4);
			self::assertEquals($cur->toUint('2'), $frozen);
			$wo = $db->queryOne('select * from ' . $wallet->realtb('wallet_withdraw_order', $cur) . ' where id = %s', $withdrawId);
			self::assertNotEmpty($wo);
			self::assertEquals('P', $wo['status']);
			$db->commit();
			//审核不通过
			$db->start();
			$rst = $wallet->approve($cur, $withdrawId, 'R', '1', 'nihao');
			self::assertTrue($rst, 'approve fail');
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint('3'), $balance1);
			$frozen = $wallet->getBalance($cur, 4);
			self::assertEquals($cur->toUint('0'), $frozen);
			$wo = $db->queryOne('select * from ' . $wallet->realtb('wallet_withdraw_order', $cur) . ' where id = %s', $withdrawId);
			self::assertNotEmpty($wo);
			self::assertEquals('R', $wo['status']);
			self::assertEquals('nihao', $wo['reject_msg']);
			$db->commit();
		} catch (WalletException $we) {
			$db->rollback();
			self::assertTrue(true, $we->getMessage());
		}
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends  testConnect
	 * @depends  testOpen
	 * @depends  testWithdraw
	 */
	public function testPay(Wallet $wallet, Currency $cur) {
		$db = $wallet->realdb();
		try {
			$db->start();
			$withdrawId = $wallet->withdraw($cur, '2', '宁广丰', 'EOS', '0x012332343434');
			self::assertNotNull($withdrawId);
			$amount = $wallet->getBalance($cur, 3);
			self::assertNotNull($amount);
			self::assertEquals($cur->toUint('3'), $amount);
			$db->commit();
			//审核通过
			$db->start();
			$rst = $wallet->approve($cur, $withdrawId, 'A', '1', 'nihao');
			self::assertTrue($rst, 'approve fail');
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint('1'), $balance1);
			$frozen = $wallet->getBalance($cur, 4);
			self::assertEquals($cur->toUint('2'), $frozen);
			$wo = $db->queryOne('select * from ' . $wallet->realtb('wallet_withdraw_order', $cur) . ' where id = %s', $withdrawId);
			self::assertNotEmpty($wo);
			self::assertEquals('A', $wo['status']);
			$db->commit();
			//支付
			$db->start();
			$rst = $wallet->pay($cur, $withdrawId, 1, 'mywallet', 'abc-' . time());
			self::assertTrue($rst, 'pay fail');
			$balance1 = $wallet->getBalance($cur, 2);
			self::assertEquals($cur->toUint('1'), $balance1);
			$frozen = $wallet->getBalance($cur, 4);
			self::assertEquals($cur->toUint('0'), $frozen);
			$amount = $wallet->getBalance($cur, 3);
			self::assertEquals($cur->toUint('1'), $amount);
			$wo = $db->queryOne('select * from ' . $wallet->realtb('wallet_withdraw_order', $cur) . ' where id = %s', $withdrawId);
			self::assertNotEmpty($wo);
			self::assertEquals('D', $wo['status']);
			$db->commit();
		} catch (WalletException $we) {
			$db->rollback();
			self::assertTrue(true, $we->getMessage());
		}
	}

	/**
	 * @param $wallet
	 * @param $cur
	 *
	 * @depends  testConnect
	 * @depends  testOpenEth
	 */
	public function testNewDepositOrder(Wallet $wallet, Currency $cur) {
		$trigered = false;
		bind('wallet\onDepositOrderConfirmed', function ($order) use (&$trigered) {
			$trigered = $order;
		});
		$id = $wallet->newDepositOrder($cur, '100', 'buyVip', 'buy-vip-' . time(), 'abc/def');
		self::assertNotEmpty($id);
		$db = $wallet->realdb();
		$od = $db->queryOne('select * from ' . $wallet->realtb('wallet_deposit_order', $cur) . ' where id = %s', $id);
		self::assertNotEmpty($od);
		self::assertEquals('P', $od['status']);
		self::assertEquals(1, $od['user_id']);
		//支付
		$rst = $wallet->payDepositOrder($cur, $id, '100', 'test', 'test-' . time());
		self::assertTrue($rst);
		//入账
		$rst = $wallet->confirmDepositOrder($cur, $id, '100');
		self::assertTrue($rst);
		$info    = $wallet->getInfo($cur);
		$ramount = $cur->toUint(100);
		self::assertEquals($ramount, $info['amount']);
		self::assertEquals($ramount, $info['balance1']);
		self::assertNotEmpty($trigered);
		self::assertEquals($ramount, $trigered['amount']);
	}

	/**
	 * @param \wallet\classes\Wallet   $wallet
	 * @param \wallet\classes\Currency $curEth
	 * @param \wallet\classes\Currency $curEos
	 *
	 * @depends testConnect
	 * @depends testOpenEth
	 * @depends testOpen
	 * @depends testNewDepositOrder
	 * @depends testPay
	 */
	public function testExchange(Wallet $wallet, Currency $curEth, Currency $curEos) {
		$infoEth = $wallet->getInfo($curEth);
		self::assertNotNull($infoEth);
		self::assertNotEmpty($infoEth['amount']);
		$amountEth = $infoEth['amount'];
		$infoEos   = $wallet->getInfo($curEos);
		self::assertNotNull($infoEos);
		self::assertNotEmpty($infoEos['amount']);
		$amountEos = $infoEos['amount'];

		$rst = $wallet->exchange($curEth, $curEos, 1, '0.5');
		self::assertNotNull($rst);

		$infoEth    = $wallet->getInfo($curEth);
		$amountEth1 = $infoEth['amount'];
		$infoEos    = $wallet->getInfo($curEos);
		$amountEos1 = $infoEos['amount'];

		$exAmount  = $curEth->toUint('0.5');
		$exAmount1 = $curEos->toUint('10');
		self::assertEquals($amountEth1, $amountEth - $exAmount);
		self::assertEquals($amountEos1, $amountEos + $exAmount1);

		$db = $wallet->realdb();

		$exod = $db->queryOne('select * from {wallet_exchange_log} where id = %s', $rst);
		self::assertNotEmpty($exod);
		self::assertEquals('5000', $exod['discount'], var_export($exod, true));
	}
}