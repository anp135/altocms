 {* Тема оформления Experience v.1.0  для Alto CMS      *}
 {* @licence     CC Attribution-ShareAlike   *}

Пользователь <a href="{$oUserComment->getProfileUrl()}">{$oUserComment->getDisplayName()}</a>
оставил новый комментарий к топику <b>«{$oTopic->getTitle()|escape:'html'}»</b>,
прочитать его можно перейдя по <a href="{if Config::Get('module.comment.nested_per_page')}{R::GetLink("comments")}{else}{$oTopic->getUrl()}#comment{/if}{$oComment->getId()}">этой ссылке</a><br>
{if Config::Get('sys.mail.include_comment')}
	Текст сообщения: <i>{$oComment->getText()}</i>
{/if}

{if $sSubscribeKey}
	<br><br>
	<a href="{R::GetLink("subscribe")}unsubscribe/{$sSubscribeKey}/">Отписаться от новых комментариев к этому топику</a>
{/if}

<br><br>
С уважением, администрация сайта <a href="{Config::Get('path.root.url')}">{Config::Get('view.name')}</a>