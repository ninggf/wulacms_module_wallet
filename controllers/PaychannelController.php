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
 * DEC :
 * User: David Wang
 * Time: 2018/9/12 上午9:35
 */

namespace wallet\controllers;

use backend\classes\IFramePageController;
use backend\form\BootstrapFormRender;
use wallet\classes\model\WalletPayAccount;
use wallet\pay\Pay;
use wulaphp\app\App;
use wulaphp\io\Ajax;

/**
 * 默认控制器.
 * @acl m:wallet/paychannel
 */
class PaychannelController extends IFramePageController {
	public function index() {
		$channels = Pay::channels();
		foreach ($channels as $id => $channel) {
			$data['id']   = $id;
			$data['name'] = $channel->getName();
			$datas[]      = $data;
		}

		return $this->render(['channels' => $datas]);
	}

	public function tpl($channel) {

		$data['channel'] = $channel;
		$data['canActive'] = $this->passport->cando('account_active:wallet/paychannel');
		$data['canAdd'] = $this->passport->cando('account_add:wallet/paychannel');
		return $this->render($data);
	}

	public function data($channel = '', $q = '', $count = '') {
		$model = new WalletPayAccount();
		if ($q) {
			$where['account LIKE'] = '%' . $q . '%';
		}
		$where['channel'] = $channel;
		$where['deleted'] = 0;

		$query = $model->select('*')->where($where)->page()->sort();
		$rows  = $query->toArray();
		$total = '';
		if ($count) {
			$total = $query->total('id');
		}
		$data['rows']  = $rows;
		$data['total'] = $total;
		$data['canEdit'] = $this->passport->cando('account_edit:wallet/paychannel');
		$data['canDel']     = $this->passport->cando('account_del:wallet/paychannel');

		return view($data);
	}

	public function edit($channel, $id = '') {
		$payChannel = Pay::getChannel($channel);
		$form       = $payChannel->getConfigForm();
		if ($id) {
			$model = new WalletPayAccount();
			$info  = $model->get($id)->ary();
			$form->inflateByData(@json_decode($info['options'], true));
		}
		$data['form']    = BootstrapFormRender::v($form);
		$data['rules']   = $form->encodeValidatorRule($this);
		$data['id']      = $id;
		$data['channel'] = $channel;

		return view($data);
	}

	public function del($id) {
		if (!$id) {
			return Ajax::error('参数错误啦!哥!');
		}
		$model = new WalletPayAccount();
		$res   = $model->updateData(['deleted' => 1], ['id' => $id]);

		return Ajax::reload('#table', $res ? '删除成功' : '删除失败');
	}

	public function setStatus($status, $ids = '') {
		$ids = safe_ids2($ids);
		if ($ids) {
			$status = $status === '1' ? 1 : 0;
			if ($ids) {
				try {
					App::db()->update('{wallet_pay_account}')->set(['status' => $status])->where(['id IN' => $ids])->exec();
				} catch (\Exception $e) {
					return Ajax::error($e->getMessage());
				}
			}

			return Ajax::reload('#table', $status == '1' ? '所选账号已激活' : '所选账号已禁用');
		} else {
			return Ajax::error('未指定渠道');
		}
	}

	public function savePost($channel, $id = '') {
		$model               = new WalletPayAccount();
		$payChannel          = Pay::getChannel($channel);
		$form                = $payChannel->getConfigForm();
		$optios              = $form->inflate();
		$data['account']     = $optios['account'];
		$data['priority']    = $optios['priority'];
		$data['options']     = json_encode($optios);
		$data['update_time'] = time();
		if ($id) {
			$res = $model->updateData($data, ['id' => $id]);
		} else {
			$data['channel']     = $channel;
			$data['create_uid']  = $this->passport->uid;
			$data['create_time'] = time();
			$res                 = $model->addData($data);
		}
		if ($res) {
			return Ajax::reload('#table', $id ? '修改成功' : '新账号已经成功创建');
		} else {
			return Ajax::error('操作失败了');
		}

	}
}