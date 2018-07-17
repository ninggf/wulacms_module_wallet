<?php
@defined('APPROOT') or header('Page Not Found', true, 404) || die();

$tables ['1.0.0'] [] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet_meta` (
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户编号',
    `locked` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否锁定',
    `txid` SMALLINT UNSIGNED NOT NULL COMMENT '流水号',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后交易时间',
    PRIMARY KEY (`user_id`)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='钱包信息'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet` (
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `currency` VARCHAR(6) NOT NULL COMMENT '货币（币种）',
    `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总额（amount = balance + blance1 + frozen）',
    `balance` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '余额（优先使用）',
    `balance1` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '可提现金额',
    `frozen` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '提现冻结金额',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `create_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建用户',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后更新时间',
    `update_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '最后更新用户',
    `account` VARCHAR(64) NULL COMMENT '账户地址',
     PRIMARY KEY (`user_id` ASC , `currency` ASC)
)  ENGINE=INNODB DEFAULT CHARACTER SET={encoding} COMMENT='钱包'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet_deposit_order` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `currency` VARCHAR(6) NOT NULL COMMENT '币种',
    `amount` BIGINT UNSIGNED NOT NULL COMMENT '金额',
    `order_type` VARCHAR(10) NOT NULL COMMENT '订单类型，对账成功后通过订单处理器',
    `order_id` VARCHAR(32) NOT NULL COMMENT '业务订单编号',
    `channel` VARCHAR(16) NOT NULL COMMENT '充值渠道',
    `tx_id` VARCHAR(128) NULL COMMENT '交易ID',
    `status` ENUM('P', 'R', 'A', 'E', 'C') NOT NULL COMMENT 'P:待付款；R:待对账；A：已入账；E：失败；C:关闭',
    `next_check_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '下次对账时间',
    `check_count` SMALLINT NOT NULL DEFAULT 0 COMMENT '对账次数',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `create_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建用户',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付时间',
    `check_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '对账时间',
    `check_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '对账用户ID',
    `spm` VARCHAR(64) NULL COMMENT '订单来源追踪',
    `ip` VARCHAR(64) NOT NULL COMMENT '业务发生时IP',
    `error_msg` TEXT NULL COMMENT '支付失败原因',
    PRIMARY KEY (`id`),
    INDEX `IDX_USER_CUR` (`status` ASC , `currency` ASC , `user_id` ASC),
    INDEX `IDX_NCT` (`next_check_time` ASC)
)  ENGINE=INNODB CHARACTER SET={encoding} COMMENT='存款订单'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet_withdraw_order` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '提现流水号',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `currency` VARCHAR(6) NOT NULL COMMENT '币种',
    `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '取现金额',
    `deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除',
    `status` ENUM('P', 'R', 'A', 'D') NOT NULL COMMENT 'P:申请中；R:拒绝；A:通过；D:已付款',
    `user_name` VARCHAR(32) NOT NULL COMMENT '真实用户名',
    `bank_account` VARCHAR(64) NOT NULL COMMENT '提取到账户',
    `bank_name` VARCHAR(32) NOT NULL COMMENT '提取到银行的银行名称',
    `reject_msg` VARCHAR(256) NULL COMMENT '被拒原因',
    `approve_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核时间',
    `approve_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '审核用户',
    `channel` VARCHAR(16) NULL COMMENT '支付通道',
    `tx_id` VARCHAR(128) NULL COMMENT '交易流水号',
    `pay_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '付款时间',
    `pay_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '付款用户',
    `create_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建用户',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `ip` VARCHAR(64) NOT NULL COMMENT '提现发起IP',
    PRIMARY KEY (`id`),
    INDEX `IDX_SCU` (`deleted` ASC , `status` ASC , `currency` ASC , `user_id` ASC)
)  ENGINE=INNODB CHARACTER SET={encoding} COMMENT='取款订单'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet_deposit_log` (
    `id` CHAR(44) NOT NULL COMMENT '收入流水号:日期时分:12-币种:6-类型:9-用户ID:7-交易序号:6',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `currency` VARCHAR(6) NOT NULL COMMENT '币种',
    `type` VARCHAR(9) NOT NULL COMMENT '收入类型',
    `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '金额',
    `subject` VARCHAR(24) NOT NULL COMMENT '业务主题',
    `subjectid` VARCHAR(48) NULL COMMENT '订单编号(业务编号)',
    `deleted` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除',
    `withdrawable` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否可提现',
    `create_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建时间',
    `create_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '创建用户',
    `update_time` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新时间',
    `update_uid` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '更新用户',
    `ip` VARCHAR(64) NOT NULL COMMENT '业务发生时的IP',
    PRIMARY KEY (`id`),
    INDEX `IDX_DCUT` (`deleted` ASC , `currency` ASC , `user_id` ASC , `type` ASC),
    UNIQUE INDEX `UDX_SSID` (`subject` ASC , `subjectid` ASC)
)  ENGINE=INNODB CHARACTER SET={encoding} COMMENT='存款记录'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `{prefix}wallet_outlay_log` (
    `id` CHAR(34) NOT NULL COMMENT '流水号:日期时分:12-币种:6-用户ID:7-交易序号:6',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户ID',
    `currency` VARCHAR(6) NOT NULL COMMENT '币种',
    `amount` BIGINT UNSIGNED NOT NULL COMMENT '金额',
    `subject` VARCHAR(16) NOT NULL COMMENT '消费主题',
    `subjectid` VARCHAR(48) NULL COMMENT '主题ID',
    `create_time` INT UNSIGNED NOT NULL,
    `create_uid` INT UNSIGNED NOT NULL,
    `update_time` INT UNSIGNED NOT NULL,
    `update_uid` INT UNSIGNED NOT NULL,
    `ip` VARCHAR(64) NOT NULL COMMENT '支付发生IP',
    PRIMARY KEY (`id`),
    INDEX `IDX_CU` (`currency` ASC , `user_id` ASC),
    UNIQUE INDEX `UDX_SSID` (`subject` ASC , `subjectid` ASC)
)  ENGINE=INNODB CHARACTER SET={encoding} COMMENT='支出记录'";

$tables['1.0.0'][] = "CREATE TABLE IF NOT EXISTS `wallet_exchange_log` (
    `id` CHAR(25) NOT NULL COMMENT '日期时分:12-用户ID:7-交易序号:6',
    `user_id` INT UNSIGNED NOT NULL COMMENT '用户编号',
    `from_currency` VARCHAR(9) NOT NULL COMMENT '原币种',
    `to_currency` VARCHAR(9) NOT NULL COMMENT '目标币种',
    `rate1` INT NOT NULL COMMENT '原币种换算比例',
    `rate2` INT NOT NULL COMMENT '目标币种换算比例',
    `amount` BIGINT NOT NULL COMMENT '金额',
    `discount` SMALLINT NOT NULL DEFAULT 10000 COMMENT '折扣',
    `total` BIGINT NOT NULL COMMENT '折扣后金额',
    `amount1` BIGINT NOT NULL COMMENT '实际兑换金额',
    `create_time` INT NOT NULL COMMENT '创建时间',
    `create_uid` INT NOT NULL COMMENT '创建用户',
    PRIMARY KEY (`id`)
)  ENGINE=INNODB CHARACTER SET={encoding} COMMENT='兑换记录表'";