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

use wallet\classes\PayChannelManager;
use wulaphp\app\App;

class Restoration {
    /**
     * 对账
     * @return int
     */
    public static function restoration(): int {
        try {
            $db   = App::db();
            $info = $db->queryOne("SELECT * FROM {wallet_deposit_order} WHERE  status = %s  ORDER BY create_time DESC LIMIT 0,1", 'R');
            if (!$info) {
                return 0;
            }
            $payChannel = PayChannelManager::getChannel($info['channel']);
            if (!$payChannel) {
                throw  new \Exception($info['channel'] . '支付渠道不存在');
            }
            $config_info = explode('.', $info['spm']);
            if (!$config_info[1]) {
                throw  new \Exception('未知支付账号id,无法对账');
            }
            $rst = $payChannel->queryOrder($info['order_id'], 1, $config_info[1]);
            if (!$rst) {
                $db->cud("UPDATE  {wallet_deposit_order}  SET  status = %s  WHERE id = %d AND status = %s ", 'E', $info['id'], 'R');

                return 0;
            }
            $query_info = $payChannel->query_info;
            switch ($info['channel']) {
                case 'weixin':
                    $order_id = $query_info['out_trade_no'];
                    $amount   = $query_info['total_fee'] / 100;
                    $tx_id    = $query_info['transaction_id'];
                    break;
                default:
                    throw  new \Exception('未知渠道');
            }
            $rtn = \wallet\deposit\Restoration::payConfirm($order_id, $amount, $info['channel'], $tx_id, 1);
            if (!$rtn) {
                throw  new \Exception('对账失败');
            }

            return 0;

        } catch (\Exception $e) {

            echo $e->getMessage();

            return 2;
        }
    }
}