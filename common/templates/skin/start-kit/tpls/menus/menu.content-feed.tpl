<ul class="nav nav-pills mb-30 content-menu">
    <li {if $sMenuSubItemSelect=='feed'}class="active"{/if}>
        <a href="{R::GetLink("feed")}">{$aLang.subscribe_menu}</a>
    </li>
    <li {if $sMenuSubItemSelect=='track'}class="active"{/if}>
        <a href="{R::GetLink("feed")}track/">{$aLang.subscribe_tracking_menu}</a>
    </li>
    {if $iUserCurrentCountTrack}
        <li {if $sMenuSubItemSelect=='track_new'}class="active"{/if}>
            <a href="{R::GetLink("feed")}track/new/">{$aLang.subscribe_tracking_menu_new} +{$iUserCurrentCountTrack}</a>
        </li>
    {/if}
</ul>