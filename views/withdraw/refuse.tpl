<div class="container-fluid wulaui m-t-sm">
    <form id="edit-refuse-form" name="TaskEditForm" data-validate="{$rules|escape}"
          action='{"wallet/withdraw/save_refuse"|app}' data-ajax method="post" data-loading>
        <input type="hidden" name="id" value="{$id}"/>
        {$form|render}
    </form>
</div>
