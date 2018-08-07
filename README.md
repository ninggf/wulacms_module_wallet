## 用户钱包

管理会员的充值，提现，消费，转账等等功能。支持多币种（法币，积分，代币等)。

### 配置

* 1.钱包配置文件:`conf/wallet_config.php`
    
    ```php
       return [
           'dbMap'=> function($user_id){ return 'default'; },
           'tableMap'=> function($table,$currency,$user_id){ return $table; },
           'subjects'=>[
               'deposit'=>[
                   'name'=>'充值'
               ]
           ]
       ];
    ```
    > 配置说明:
    > * 1.`dbMap`: 根据用户id分库
    > * 2.`tableMap`: 根据币种，用户id分表
    > * 3.`subjects`: 钱包支持的业务
    >   * a.键值为业务ID
    >   * b.`name`: 业务名称
    >   * c.*特别说明*,系统需要`deposit`(充值)、`withdraw`(提现)、`exchange`(兑换)三个业务.
    
* 2.币种配置文件: `conf/currency_config.php`
    
    ```php
       return [
          'EOS'=>[
            'name' => 'EOSx',
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
            ]
          ]
       ];
    ```
    > 配置说明:
    > * 1.键值为币种ID
    > * 2.`name`: 币种名称
    > * 3.`symbol`: 标识
    > * 4.`withdraw`: 是否可提现
    > * 5.`decimals`: 精度
    > * 6.`scale`: 多少位小数
    > * 7.`rate`: 与默认参考币的汇率（锚定）
    > * 8.`types`: 收入类型
    >   * a.键值为收类型ID
    >   * b.`name`: 收入名
    >   * c.`withdraw`:是否可提现 
    >   * d.*特别说明*,如果支持从其它币种兑换，请新增一个收入类型:`fromXXX`,`XXX`为币种ID。
    
### 脚本

* 1.对账脚本(每隔30秒对账一次账户)

`# php artisan cron -i30 modules/wallet/restoration.php`