<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace wallet\deposit;

use wallet\classes\Currency;
use wallet\classes\exception\WalletException;
use wallet\classes\model\WalletDepositOrder;
use wallet\classes\Wallet;
use wulaphp\util\RedisLock;

class Restoration {

    /**
     * 对账
     * @return int
     */
    public static function restoration(): int {
        return -1;
    }

    /**
     * 对账
     *
     * @param $order_id
     * @param $amount
     * @param $channel
     * @param $tx_id
     *
     * @return bool
     * @throws
     */
    public static function payConfirm($order_id, $amount, $channel, $tx_id): bool {
        $lock_key = 'deposit_confirm_lock_' . $order_id;
        $lock     = RedisLock::ulock($lock_key);
        if ($lock) {
            try {
                $depositModel = new WalletDepositOrder();
                $order_info   = $depositModel->get(['order_id' => $order_id])->ary();
                if (!$order_info) {
                    log_error($order_id . '订单号不存在', 'confirm_deposit_order');

                    return false;
                }
                $currency  = Currency::init($order_info['currency']);
                $wallet    = Wallet::connect($order_info['user_id']);
                $wallet->open($order_info['currency']);
                $pay_order = $wallet->payDepositOrder($currency, $order_info['id'], $amount, $channel, $tx_id);
                if (!$pay_order) {
                    log_error($order_id . '订单号充值失败', 'confirm_deposit_order');

                    return false;
                }
                $confirm_order = $wallet->confirmDepositOrder($currency, $order_info['id'], $amount);
                if (!$confirm_order) {
                    log_error($order_id . '订单号对账失败', 'confirm_deposit_order');

                    return false;
                }

                return true;
            }catch (WalletException $e){
                log_error($e->getMessage(),'confirm_log');
            } finally {
                RedisLock::uunlock($lock_key);
            }
        }
        log_error($order_id . '锁定', 'confirm_deposit_order');
        return false;

    }

    /**
     * 取消订单
     *
     * @param string $order_id
     *
     * @return bool
     * @throws
     */
    public static function failOrder(string $order_id): bool {
        $lock_key = 'pay_confirm_lock_' . $order_id;
        $lock     = RedisLock::ulock($lock_key);
        if ($lock) {
            try {
                $depositModel = new WalletDepositOrder();
                $order_info   = $depositModel->get(['order_id' => $order_id])->ary();
                if (!$order_info) {
                    log_error($order_id . '订单号不存在', 'confirm_deposit_order');

                    return false;
                }
                $currency  = Currency::init($order_info['currency']);
                $wallet    = Wallet::connect($order_info['user_id']);
                $pay_order = $wallet->cancelDepositOrder($currency, $order_id);
                if (!$pay_order) {
                    log_error($order_id . '订单号取消失败', 'cancle_deposit_order');

                    return false;
                }

                return true;
            } finally {
                RedisLock::uunlock($lock_key);
            }
        }

        return false;
    }
}