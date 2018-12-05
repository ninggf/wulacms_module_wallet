<?php

namespace wallet;

use backend\classes\DashboardUI;
use jwsy\classes\OrderHandle;
use wallet\classes\Currency;
use wallet\classes\WalletSetting;
use wallet\deposit\Restoration;
use wula\cms\CmfModule;
use wulaphp\app\App;
use wulaphp\auth\AclResourceManager;

/**
 * wallet
 *
 * @package wallet
 */
class WalletModule extends CmfModule {
    public function getName() {
        return '用户钱包';
    }

    public function getDescription() {
        return '管理会员的充值，提现，消费，转账等等功能。支持多币种（法币，积分，代币等），具体见说明文档。';
    }

    public function getHomePageURL() {
        return 'https://www.wulacms/modules/wallet';
    }

    public function getVersionList() {
        $v['1.0.0'] = '开始啦';
        $v['1.0.1'] = '支付渠道&账号添加';

        return $v;
    }

    public function getAuthor() {
        return 'Leo Ning';
    }

    /**
     * @param array $setts
     *
     * @filter backend/settings
     * @return array
     */
    public static function sets($setts) {
        $setts['wallet'] = new WalletSetting();

        return $setts;
    }

    public function bind() {
        bind('get_pay_channel', '&\wallet\deposit\weixin\WeixinDeposit');
    }

    /**
     * @param array $info
     *
     * @filter wallet\on_wx_deposit_success
     * @return bool
     */
    public static function onWxDepositSuccess($info) {
        return Restoration::payConfirm($info['out_trade_no'], $info['total_fee'] / 100, 'weixin', $info['transaction_id']);
    }

    /**
     * @param array $info
     *
     * @filter wallet\on_wx_deposit_fail
     * @return bool
     */
    public static function onWxDepositFail($info) {
        return Restoration::failOrder($info['out_trade_no']);
    }

    /**
     * @param \backend\classes\DashboardUI $ui
     *
     * @bind dashboard\initUI
     */
    public static function initUI(DashboardUI $ui) {
        $passport = whoami('admin');
        if ($passport->cando('m:wallet')) {
            $app            = $ui->getMenu('wallet', '财务', 50);
            $app->icon      = '&#xe61e;';
            $app->iconCls   = 'alicon';
            $app->iconStyle = 'color: orange';
            $currencies     = Currency::currencies();
            foreach ($currencies as $cur => $cfg) {
                $name          = $cfg->name;
                $cnav          = $app->getMenu($cur, $cfg->name);
                $cnav->iconCls = 'alicon';
                $cnav->icon    = '&#xe60c;';
                //账户列表
                $page1              = $cnav->getMenu('stat', $name . '账户', 1);
                $page1->icon        = '&#xe626;';
                $page1->iconCls     = 'alicon';
                $page1->data['url'] = App::url('wallet/account/' . $cur);
                //支出记录
                $page2              = $cnav->getMenu('out', $name . '支出', 2);
                $page2->icon        = '&#xe69c;';
                $page2->iconCls     = 'layui-icon';
                $page2->iconStyle   = 'color:orange';
                $page2->data['url'] = App::url('wallet/out/' . $cur);
                //收入记录
                $page3              = $cnav->getMenu('deposit', $name . '收入', 3);
                $page3->icon        = '&#xe6af;';
                $page3->iconCls     = 'layui-icon';
                $page3->iconStyle   = 'color:green';
                $page3->data['url'] = App::url('wallet/deposit/' . $cur);
                if ($cfg->withdraw) {
                    $page4              = $cnav->getMenu('with', $name . '提现', 4);
                    $page4->icon        = '&#xe627;';
                    $page4->iconCls     = 'alicon';
                    $page4->iconStyle   = 'color:orange';
                    $page4->data['url'] = App::url('wallet/withdraw/' . $cur);
                }
                //收入类型
                $types = $cfg->types;
                if (isset($types['deposit'])) {
                    $page5              = $cnav->getMenu('rec', $name . '充值', 5);
                    $page5->icon        = '&#xe60d;';
                    $page5->iconCls     = 'alicon';
                    $page5->iconStyle   = 'color:green';
                    $page5->data['url'] = App::url('wallet/recharge/' . $cur);
                }
            }

            $page6              = $app->getMenu('exch', '兑换记录');
            $page6->icon        = '&#xe76a;';
            $page6->iconCls     = 'alicon';
            $page6->iconStyle   = 'color:orange';
            $page6->data['url'] = App::url('wallet/exchange');

            $page7              = $app->getMenu('paychannel', '支付渠道');
            $page7->icon        = '&#xe624;';
            $page7->iconCls     = 'alicon';
            $page7->iconStyle   = 'color:red';
            $page7->data['url'] = App::url('wallet/paychannel');
        }
    }

    /**
     * @param \wulaphp\auth\AclResourceManager $mgr
     *
     * @bind rbac\initAdminManager
     */
    public static function initAcl(AclResourceManager $mgr) {
        $res = $mgr->getResource('wallet', '钱包', 'm');
        $res->addOperate('set', '通道管理');
        $res->addOperate('out', '支出记录');
        $res->addOperate('deposit', '收入记录');
        $res->addOperate('exchange', '兑换记录');
        $cur = $mgr->getResource('wallet/currency', '币种管理', 'm');
        $cur->addOperate('set', '配置');

        $res = $mgr->getResource('wallet/deposit', '充值管理', 'm');
        $res->addOperate('check', '手工入账');

        $res = $mgr->getResource('wallet/withdraw', '提现管理', 'm');
        $res->addOperate('approve', '审核');
        $res->addOperate('pay', '支付');
        $res->addOperate('refuse', '拒绝');
        $res = $mgr->getResource('wallet/paychannel', '支付渠道', 'm');
        $res->addOperate('account_edit', '修改账号');
        $res->addOperate('account_del', '删除账号');
        $res->addOperate('account_active', '激活账号');
        $res->addOperate('account_add', '添加账号');
    }
}

App::register(new WalletModule());
// end of bootstrap.php