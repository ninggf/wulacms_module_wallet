<?php
/*
 * This file is part of wulacms.
 *
 * (c) Leo Ning <windywany@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

# 对账脚本（建义每隔15秒运行一次）
include __DIR__ . '/../../../bootstrap.php';

exit(\wallet\pay\Restoration::restoration());
# end