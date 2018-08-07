<tbody data-total="{$total}" class="wulaui">
{foreach $rows as $row}
    <tr>
        <td>{$row.create_time|date_format:'Y-m-d H:i:s'}</td>
        <td>{$row.id}</td>
        <td>{$row.user_id}</td>
        <td>{$row.amount}</td>
        <td>{$subjects[$row.subject].name}</td>
        <td>{$row.subjectid}</td>
    </tr>
    {foreachelse}
    <tr>
        <td colspan="6" class="text-center">暂无数据!</td>
    </tr>
{/foreach}
</tbody>