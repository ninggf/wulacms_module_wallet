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
 * DEC : 充值记录
 * User: David Wang
 * Time: 2018/8/7 上午10:11
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use wallet\classes\Currency;
use wallet\classes\model\WalletDepositOrder;
use wallet\classes\PayChannelManager;
use wallet\deposit\Restoration;
use wulaphp\io\Ajax;
use wulaphp\io\Response;

/**
 * 默认控制器.
 * @acl m:wallet
 */
class RechargeController extends IFramePageController {
    private $types = ['P' => '待付款', 'R' => '待对账', 'A' => '已入账', 'E' => '已失败', 'D' => '待通知', 'S' => '已完成'];

    public function index($currncy) {
        $cur = Currency::init($currncy);
        if ($cur) {
            $data['types']    = $this->types;
            $data['currency'] = $currncy;

            return $this->render($data);
        }
        Response::respond(404);

        return null;
    }

    public function data($currency, $count = '') {
        $data    = [];
        $user_id = rqst('user_id');
        if ($user_id) {
            $where['user_id'] = $user_id;
        }
        $start_time = rqst('start_time');
        if ($start_time) {
            $where['create_time >'] = strtotime($start_time);
        }
        $end_time = rqst('end_time');
        if ($end_time) {
            $where['create_time <'] = strtotime($end_time);
        }
        if ($currency) {
            $model             = new WalletDepositOrder();
            $where['currency'] = $currency;
            $ctype             = rqst('type');
            if ($ctype != '') {
                $where['status'] = $ctype;
            }
            $query = $model->select('*')->where($where)->page()->sort();
            $rows  = $query->toArray();
            $cur   = Currency::init($currency);
            foreach ($rows as &$row) {
                $row['cur_name']   = $cur->name;
                $row['amount']     = $cur->fromUint($row['amount']) . $cur->symbol;
                $row['statusType'] = $row['status'];
                $row['status']     = $this->types[ $row['status'] ];
                if (time() - $row['create_time'] >= 300) {
                    $row['can_confirm'] = 1;
                } else {
                    $row['can_confirm'] = 0;
                }

            }
            $total = '';
            if ($count) {
                $total = $query->total('id');
            }
            $data['rows']  = $rows;
            $data['total'] = $total;
            $data['types'] = $cur->types;
        }

        return view($data);
    }

    /**
     * 手工对账
     *
     * @param $id
     *
     * @return \wulaphp\mvc\view\JsonView
     * @throws
     */
    public function restoration($id) {
        if (!$id) {
            return Ajax::error('啊,我不知道对啥啊');
        }
        $model = new WalletDepositOrder();
        $info  = $model->findOne($id)->ary();
        if ($info['status'] != 'P') {
            return Ajax::error('这个状态下的对不了帐哦');
        }
        $payChannel = PayChannelManager::getChannel($info['channel']);
        if (!$payChannel) {
            return Ajax::error($info['channel'] . '支付渠道不存在');
        }
        $config_info = explode('.', $info['spm']);
        if (!$config_info[1]) {
            return Ajax::error('未知支付账号id,无法对账');
        }
        $rst = $payChannel->queryOrder($info['order_id'], 1, $config_info[1]);
        if (!$rst) {
            return Ajax::error($payChannel->error());
        }
        $query_info = $payChannel->query_info;
        if ($info['status'] == 'P') {
            $confirm_type = 0;
        } else {
            $confirm_type = 1;
        }
        switch ($info['channel']) {
            case 'weixin':
                $order_id = $query_info['out_trade_no'];
                $amount   = $query_info['total_fee'] / 100;
                $tx_id    = $query_info['transaction_id'];
                break;
            default:
                return Ajax::error('未知渠道');
        }
        if (!$amount||!$tx_id) {
            return Ajax::error('订单未支付,无法对账');
        }
        $rtn = Restoration::payConfirm($order_id, $amount, $info['channel'], $tx_id, $confirm_type);
        if (!$rtn) {
            return Ajax::error('对账失败');
        }

        return Ajax::reload('#table', '对账成功');
    }
}