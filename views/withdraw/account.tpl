<div class="container-fluid wulaui m-t-sm">
    <form id="edit-refuse-form" name="TaskEditForm" data-validate="{$rules|escape}"
          action='{"wallet/withdraw/pay"|app}' data-ajax method="post" data-loading>
        <input type="hidden" name="id" value="{$id}"/>
        <div class="col-xs-6">
            <label>渠道</label><br>
            <input id="name" type="text" name="name" value="{$channel_name}" class="form-control parsley-success" aria-required="true"
                   aria-invalid="false" readonly>
        </div>
        <div class="col-xs-6">
            <label>选择账号</label><br>
            <select id="show_type" class="form-control parsley-success" name="account" aria-invalid="false">
                {foreach $account as $ac}
                    <option value="{$ac['account']}">{$ac['account']}</option>
                {/foreach}
            </select>
        </div>
    </form>
</div>