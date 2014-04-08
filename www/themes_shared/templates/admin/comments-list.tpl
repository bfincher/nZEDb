<h1>{$page->title}</h1>
{if $commentslist}
	{$pager}
	<table style="margin-top:10px;" class="data Sortable highlight">
		<tr>
			<th>user</th>
			<th>date</th>
			<th>comment</th>
			{if $comment.host}<th>host</th>{/if}
			<th>options</th>
		</tr>
		{foreach from=$commentslist item=comment}
		<tr class="{cycle values=",alt"}">
			<td><a href="{$smarty.const.WWW_TOP}/user-edit.php?id={$comment.userid}">{$comment.username}</a></td>
			<td title="{$comment.createddate}">{$comment.createddate|timeago}</td>
			{if $comment.shared == 2}
				<td style="color:#6B2447">{$comment.text|escape:"htmlall"|nl2br}</td>
			{else}
				<td>{$comment.text|escape:"htmlall"|nl2br}</td>
			{/if}
			{if $comment.host}<td>{$comment.host}</td>{/if}
			<td>
				{if $comment.guid}<a href="{$smarty.const.WWW_TOP}/../details/{$comment.guid}#comments">view</a> |{/if}
				<a href="{$smarty.const.WWW_TOP}/comments-delete.php?id={$comment.id}">delete</a>
			</td>
		</tr>
		{/foreach}
	</table>
{else}
	<p>No comments available</p>
{/if}