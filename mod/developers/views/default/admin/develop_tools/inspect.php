<?php
/**
* Inspect View
*
* Inspect global variables of Elgg
*/

elgg_load_js('jit.spacetree');

echo elgg_view_form('developers/inspect', array('class' => 'developers-form-inspect'));

echo '<div id="developers-inspect-wrapper">';
echo '<div id="developers-inspect-results"></div>';
echo '</div>';

echo elgg_view('graphics/ajax_loader', array('id' => 'developers-ajax-loader'));
