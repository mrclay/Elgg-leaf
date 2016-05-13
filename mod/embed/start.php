<?php
/**
 * Elgg media embed plugin
 *
 * @package ElggEmbed
 */


elgg_register_event_handler('init', 'system', 'embed_init');

/**
 * Init function
 */
function embed_init() {
	elgg_extend_view('elgg.css', 'embed/css');
	elgg_extend_view('admin.css', 'embed/css');

	if (elgg_is_logged_in()) {
		elgg_register_plugin_hook_handler('register', 'menu:longtext', 'embed_longtext_menu');
	}
	elgg_register_plugin_hook_handler('register', 'menu:embed', 'embed_select_tab', 1000);

	// Page handler for the modal media embed
	elgg_register_page_handler('embed', 'embed_page_handler');
	
	$embed_js = elgg_get_simplecache_url('embed/embed.js');
	elgg_register_js('elgg.embed', $embed_js, 'footer');

	elgg_register_plugin_hook_handler('entity:icon:url', 'object', 'embed_set_thumbnail_url', 1000);

	elgg_register_plugin_hook_handler('elgg.data', 'site', 'embed_set_js_data');
}

/**
 * Set data to be used by ajax-loaded embeds
 *
 * @param string $hook   "elgg.data"
 * @param string $type   "site"
 * @param array  $data   elgg.data client-side data
 * @param array  $params Hook params
 * @return array
 * @private
 */
function embed_set_js_data($hook, $type, $data, $params) {
	$externals = $GLOBALS['_ELGG']->externals_map;
	$data['embed'] = [
		'embed_js' => $externals['js']['elgg.embed']->url,
		'lightbox_css' => $externals['css']['lightbox']->url,
	];
	return $data;
}

/**
 * Add the embed menu item to the long text menu
 *
 * @param string $hook
 * @param string $type
 * @param array $items
 * @param array $vars
 * @return array
 */
function embed_longtext_menu($hook, $type, $items, $vars) {

	if (elgg_get_context() == 'embed') {
		return $items;
	}
	
	$id = elgg_extract('id', $vars);
	if ($id === null) {
		return;
	}

	$url = 'embed';

	$page_owner = elgg_get_page_owner_entity();
	if (elgg_instanceof($page_owner, 'group') && $page_owner->isMember()) {
		$url = 'embed?container_guid=' . $page_owner->getGUID();
	}

	elgg_require_js('jquery.form');
	elgg_load_js('elgg.embed');

	$text = elgg_echo('embed:media');

	// if loaded through ajax (like on /activity), pull in JS libs manually
	// hack for #6422 because we haven't converted everything to amd yet
	if (elgg_in_context('ajax')) {
		ob_start();
?>
<script>
!function () {
	// decent test for whether the CSS is likely to be loaded
	var css_loaded = !!(window.$ && $.colorbox);

	require(['elgg', 'jquery.form', 'elgg/lightbox'], function (elgg) {
		if (!css_loaded) {
			$('<link rel="stylesheet">')
				.prop('href',  elgg.data.embed.lightbox_css)
				.appendTo('head');
		}
		if (typeof elgg.embed === 'undefined') {
			$.getScript(elgg.data.embed.embed_js);
		}
	});
}();
</script>
<?php
		$text .= ob_get_clean();
	}

	$items[] = ElggMenuItem::factory(array(
		'name' => 'embed',
		'href' => 'javascript:void()',
		'data-colorbox-opts' => json_encode([
			'href' => elgg_normalize_url($url),
		]),
		'text' => $text,
		'rel' => "embed-lightbox-{$id}",
		'link_class' => "elgg-longtext-control elgg-lightbox embed-control embed-control-{$id}",
		'priority' => 10,
	));
	
	return $items;
}

/**
 * Select the correct embed tab for display
 *
 * @param string $hook
 * @param string $type
 * @param array $items
 * @param array $vars
 */
function embed_select_tab($hook, $type, $items, $vars) {

	// can this ba called from page handler instead?
	$page = get_input('page');
	$tab_name = array_pop(explode('/', $page));
	foreach ($items as $item) {
		if ($item->getName() == $tab_name) {
			$item->setSelected();
			elgg_set_config('embed_tab', $item);
		}
	}

	if (!elgg_get_config('embed_tab') && count($items) > 0) {
		$items[0]->setSelected();
		elgg_set_config('embed_tab', $items[0]);
	}
}

/**
 * Serves the content for the embed lightbox
 *
 * @param array $page URL segments
 */
function embed_page_handler($page) {

	$container_guid = (int)get_input('container_guid');
	if ($container_guid) {
		$container = get_entity($container_guid);

		if (elgg_instanceof($container, 'group') && $container->isMember()) {
			// embedding inside a group so save file to group files
			elgg_set_page_owner_guid($container_guid);
		}
	}

	set_input('page', $page[1]);

	echo elgg_view('embed/layout');

	// exit because this is in a modal display.
	exit;
}

/**
 * A special listing function for selectable content
 *
 * This calls a custom list view for entities.
 *
 * @param array $entities Array of ElggEntity objects
 * @param array $vars     Display parameters
 * @return string
 */
function embed_list_items($entities, $vars = array()) {

	$defaults = array(
		'items' => $entities,
		'list_class' => 'elgg-list-entity',
	);

	$vars = array_merge($defaults, $vars);

	return elgg_view('embed/list', $vars);
}

/**
 * Set the options for the list of embedable content
 *
 * @param array $options
 * @return array
 */
function embed_get_list_options($options = array()) {

	$container_guids = array(elgg_get_logged_in_user_guid());
	if (elgg_get_page_owner_guid()) {
		$page_owner_guid = elgg_get_page_owner_guid();
		if ($page_owner_guid != elgg_get_logged_in_user_guid()) {
			$container_guids[] = $page_owner_guid;
		}
	}

	$defaults = array(
		'limit' => 6,
		'container_guids' => $container_guids,
		'item_class' => 'embed-item',
	);

	$options = array_merge($defaults, $options);

	return $options;
}

/**
 * Substitutes thumbnail's inline URL with a permanent URL
 * Registered with a very late priority of 1000 to ensure we replace all previous values
 * 
 * @param string $hook   "entity:icon:url"
 * @param string $type   "object"
 * @param string $return URL
 * @param array  $params Hook params
 * @return string
 */
function embed_set_thumbnail_url($hook, $type, $return, $params) {

	if (!elgg_in_context('embed')) {
		return;
	}
	
	$entity = elgg_extract('entity', $params);
	$size = elgg_extract('size', $params);

	$thumbnail = $entity->getIcon($size);
	if (!$thumbnail->exists()) {
		return;
	}

	return elgg_get_embed_url($entity, $size);
}