<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>
            <input type="checkbox" value="{$row.user_id}" class="grp"/>
        </td>
        <td>{$row.user_id}</td>
        <td>{$row.amount}</td>
        <td>{$row.balance}</td>
        <td>{$row.balance1}</td>
        <td>{$row.frozen}</td>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td class="text-right">
            {if $canDeposit}
                <a data-cls="layui-icon" data-tab="&#xe857;" data-title="收入详情:{$row.currency}"
                   href="{'wallet/deposit'|app}/{$row.currency}/{$row.user_id}" class="btn btn-xs btn-danger">
                    <i class="fa fa-bar-chart"></i>
                </a>
            {/if}
            {if $canOut}
                <a data-cls="layui-icon" data-tab="&#xe857;" data-title="支出详情:{$row.currency}"
                   href="{'wallet/out'|app}/{$row.currency}/{$row.user_id}" class="btn btn-xs btn-primary">
                    <i class="fa fa-line-chart"></i>
                </a>
            {/if}
        </td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="8" class="text-center">暂无相关数据!</td>
    </tr>
{/foreach}
</tbody>