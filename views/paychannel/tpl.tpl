<div class="hbox stretch wulaui layui-hide" id="channel-list">
    <section class="vbox">
        <header class="header bg-light clearfix b-b">
            <div class="row m-t-sm">
                <div class="col-sm-6 m-b-xs">
                    {if $canAdd}
                    <a href="{'wallet/paychannel/edit'|app}/{$channel}" class="btn btn-sm btn-success edit-admin" data-ajax="dialog"
                       data-area="600px,auto" data-title="新的账号">
                        <i class="fa fa-plus"></i> 添加账号
                    </a>
                    {/if}
                    <div class="btn-group">
                        {if $canActive}
                        <a href="{'wallet/paychannel/set-status/0'|app}" data-ajax
                           data-grp="#table tbody input.grp:checked" data-confirm="你真的要禁用这些渠道吗？"
                           data-warn="请选择要禁用的渠道" class="btn btn-sm btn-warning"><i class="fa fa-square-o"></i> 禁用</a>
                        <a href="{'wallet/paychannel/set-status/1'|app}" data-ajax
                           data-grp="#table tbody input.grp:checked" data-confirm="你真的要激活这些渠道吗？"
                           data-warn="请选择要激活的渠道" class="btn btn-sm btn-primary"><i class="fa fa-check-square-o"></i>
                            激活</a>
                        {/if}
                    </div>
                </div>
                <div class="col-xs-6 text-right m-b-xs">
                    <form data-table-form="#table" id="search-form" class="form-inline">
                        <input type="hidden" name="deleted" id="deleted"/>
                        <div class="input-group input-group-sm">
                            <input id="search" data-expend="300" type="text" name="q" class="input-sm form-control"
                                   placeholder="{'Search'|t}" autocomplete="off"/>
                            <span class="input-group-btn">
                                <button class="btn btn-sm btn-info" id="btn-do-search" type="submit">Go!</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </header>
        <section class="w-f">
            <div class="table-responsive">
                <table id="table" data-auto data-table="{'wallet/paychannel/data'|app}?channel={$channel}" data-sort="id,d"
                       style="min-width: 800px">
                    <thead>
                    <tr>
                        <th width="20"><input type="checkbox" class="grp"/></th>
                        <th width="200">账号</th>
                        <th width="100">渠道ID</th>
                        <th width="150">创建时间</th>
                        <th width="60" data-sort="status,d">状态</th>
                        <th></th>
                    </tr>
                    </thead>
                </table>
            </div>
        </section>
        <footer class="footer b-t">
            <div data-table-pager="#table" data-limit="10"></div>
        </footer>
    </section>
</div>
<script>
	layui.use(['jquery', 'bootstrap', 'wulaui'], function ($, b, wui) {
		var table = $('#table');
		$('#channel-list').on('before.dialog', '.edit-admin', function (e) {
			e.options.btn = ['保存', '取消'];
			e.options.yes = function () {
				$('#core-channel-form').data('dialogId', layer.index).submit();
				return false;
			};
		}).removeClass('layui-hide');

		$('body').on('ajax.success', '#core-channel-form', function () {
			layer.closeAll();
			table.reload();
		});
		$('#btn-reload').click(function () {
			table.reload();
		});
	})
</script>