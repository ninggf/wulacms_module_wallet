<?php
/**
 * //                            _ooOoo_
 * //                           o8888888o
 * //                           88" . "88
 * //                           (| -_- |)
 * //                            O\ = /O
 * //                        ____/`---'\____
 * //                      .   ' \\| |// `.
 * //                       / \\||| : |||// \
 * //                     / _||||| -:- |||||- \
 * //                       | | \\\ - /// | |
 * //                     | \_| ''\---/'' | |
 * //                      \ .-\__ `-` ___/-. /
 * //                   ___`. .' /--.--\ `. . __
 * //                ."" '< `.___\_<|>_/___.' >'"".
 * //               | | : `- \`.;`\ _ /`;.`/ - ` : | |
 * //                 \ \ `-. \_ __\ /__ _/ .-` / /
 * //         ======`-.____`-.___\_____/___.-`____.-'======
 * //                            `=---='
 * //
 * //         .............................................
 * //                  佛祖保佑             永无BUG
 * DEC : 订单脚本
 * User: David Wang
 * Time: 2018/12/3 下午5:01
 */

namespace wallet\deposit;

use wulaphp\app\App;

class ConfirmOrder {

    public static function confirm(): int {
        try {
            $db  = App::db();
            $res = $db->queryOne("SELECT * FROM {wallet_deposit_order} WHERE  status = %s AND order_time = 0 ORDER BY check_time DESC LIMIT 0,1", 'D');
            if (!$res) {
                return 0;
            }
            $time = time();
            $rst  = $db->cud("UPDATE  {wallet_deposit_order}  SET  order_time = %d  WHERE id = %d AND order_time = 0 ", $time, $res['id']);
            if (!$rst) {
                //被别人分走了
                return 0;
            }
            //通知业务逻辑处理充值情况
            $filter_res = apply_filter('wallet\onDepositOrderConfirmed', $res);
            if ($filter_res) {
                $upS = $db->cud("UPDATE  {wallet_deposit_order}  SET  status = %s  WHERE id = %d AND status=%s AND order_time = %d ", 'S', $res['id'], 'D', $time);
                if (!$upS) {
                    throw  new \Exception('完蛋,通知成功但数据库更新失败了');
                }

                return 0;
            } else {
                throw  new \Exception('通知返回失败');
            }
        } catch (\Exception $e) {
            //失败回滚
            $db->cud("UPDATE  {wallet_deposit_order}  SET  status = %s,order_time=0  WHERE id = %d ", 'D', $res['id']);

            echo $e->getMessage();

            return 2;
        }

    }
}