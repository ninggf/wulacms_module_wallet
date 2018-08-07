<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>{$row.id}</td>
        <td>{$row.user_id}</td>
        <td>{$row.from_currency}</td>
        <td>{$row.to_currency}</td>
        <td>1:{$row.rate2/$row.rate1}</td>
        <td>{$row.amount}</td>
        <td>{$row.discount}</td>
        <td>{$row.total}</td>
        <td>{$row.amount1}</td>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="10" class="text-center">暂无数据!</td>
    </tr>
{/foreach}
</tbody>