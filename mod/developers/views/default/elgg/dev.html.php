<div class="developers-gear">
	<?= elgg_view_icon('settings-alt', [
		'hidden' => true,
		'title' => elgg_echo('admin:developers:settings'),
		'data-href' => elgg_normalize_url('ajax/view/developers/gear_popup'),
	]) ?>
	<?= elgg_view_icon('refresh', [
		'hidden' => true,
		'title' => elgg_echo('admin:cache:flush'),
		'data-href' => elgg_normalize_url('action/admin/site/flush_cache'),
	]) ?>
</div>