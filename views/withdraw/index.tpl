<div class="hbox stretch wulaui layui-hide" id="task-list">
    <aside class="aside aside-xs b-r">
        <div class="vbox">
            <header class="bg-light header b-b">
                <p>状态</p>
            </header>
            <section class="scrollable m-t-xs">
                <ul class="nav nav-pills nav-stacked no-radius" id="task-status">
                    <li class="active">
                        <a href="javascript:;"> 全部 </a>
                    </li>
                    {foreach $groups as $gp=>$name}
                        <li>
                            <a href="javascript:;" rel="{$gp}" title="{$name}"> {$name}</a>
                        </li>
                    {/foreach}
                </ul>
            </section>
        </div>
    </aside>
    <section class="vbox">
        <header class="header bg-light clearfix b-b">
            <div class="row m-t-sm">

                <div class="col-xs-12 text-right m-b-xs">
                    <form data-table-form="#table" id="search-form" class="form-inline">
                        <input type="hidden" name="status" id="status"/>
                        <div data-datepicker class="input-group date" data-end="#time1">
                            <input id="time" type="text" style="width: 100px;" class="input-sm form-control"
                                   name="start_time" placeholder="开始时间"/>
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <div data-datepicker class="input-group date" data-start="#time">
                            <input id="time1" type="text" style="width: 100px;" class="input-sm form-control"
                                   name="end_time" placeholder="结束时间"/>
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                        </div>
                        <div class="input-group input-group-sm">
                            <input id="search" type="text" name="q" class="input-sm form-control" placeholder="UID"
                                   autocomplete="off"/>
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
                <table id="table" data-auto data-table="{'wallet/withdraw/data'|app}/{$currency}" data-sort="id,d"
                       style="min-width: 800px">
                    <thead>
                    <tr>
                        <th width="10"></th>
                        <th width="10">
                            <input type="checkbox" class="grp"/>
                        </th>
                        <th width="60" data-sort="id,d">编号</th>
                        <th width="80">会员</th>
                        <th width="80">币种</th>
                        <th width="80">提现金额</th>
                        <th width="80">提现平台</th>
                        <th width="100">平台帐号</th>
                        <th width="100">用户实名</th>
                        <th width="60">提现状态</th>
                        <th width="100" data-sort="create_time,d">创建时间</th>
                        <th width="80">操作</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </section>
        <footer class="footer b-t">
            <div data-table-pager="#table" data-limit="10"></div>
        </footer>
    </section>

    <a class="hidden edit-task" id="for-edit-task"></a>
</div>
<script>
	layui.use(['jquery', 'bootstrap', 'wulaui'], function ($) {
		var group = $('#task-status'), table = $('#table');
		group.find('a').click(function () {
			var me = $(this), mp = me.closest('li');
			if (mp.hasClass('active')) {
				return;
			}
			group.find('li').not(mp).removeClass('active');
			mp.addClass('active');
			$('#status').val(me.attr('rel'));
			$('#search-form').submit();
			return false;
		});

		$('#task-list').on('before.dialog', '.new-task', function (e) { // 增加编辑用户
			e.options.btn = ['创建', '取消'];
			e.options.yes = function () {
				if ($('#task-select').val()) {
					$('#new-task-form').data('dialogId', layer.index).submit();
				}
				return false;
			};
		}).on('before.dialog', '.edit-task', function (e) {
			e.options.btn = ['保存', '取消'];
			e.options.yes = function () {
				$('#edit-refuse-form').data('dialogId', layer.index).submit();
				return false;
			};
		}).on('before.dialog', '.edit-pay', function (e) {
			e.options.btn = ['立即支付', '取消'];
			e.options.yes = function () {
				$('#edit-refuse-form').data('dialogId', layer.index).submit();
				return false;
			};
		}).removeClass('layui-hide');

		$('body').on('ajax.success', '#edit-refuse-form', function () {
			layer.closeAll();
			table.reload();
		});
		$('#btn-reload').click(function () {
			table.reload();
		});
	})
</script>