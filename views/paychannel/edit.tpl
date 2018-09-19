<div class="container-fluid m-t-md">
    <div class="row wulaui">
        <div class="col-sm-12">
            <form id="core-channel-form" name="SettingForm" action="{'wallet/paychannel/save'|app}"
                  data-validate="{$rules|escape}" data-ajax method="post" role="form"
                  class="form-horizontal {if $script}hidden{/if}" data-loading style="padding-top: 10px;">
                <input type="hidden" name="id" id="id" value="{$id}"/>
                <input type="hidden" name="channel" id="channel" value="{$channel}"/>
                {$form|render}
            </form>
        </div>

    </div>

</div>