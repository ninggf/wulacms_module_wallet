<?php
/**
 * @desc  .
 * @author: FLY
 * @Date  : 28/02/2018 20:05
 */

namespace wallet\classes\form;

use wulaphp\form\FormTable;
use wulaphp\validator\JQueryValidator;

class WithdrawRefuseForm extends FormTable {
	public $table = null;
	use JQueryValidator;
	/**
	 * 拒绝理由
	 * @var \backend\form\TextField
	 * @type string
	 */
	public $msg;
}