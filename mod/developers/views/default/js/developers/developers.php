<?php
/**
 * Elgg developers tool JavaScript
 */
?>

elgg.provide('elgg.dev');

elgg.dev.init = function() {
	$('.developers-form-inspect').live('submit', elgg.dev.inspectSubmit);
};

/**
 * Submit the inspect form through Ajax
 *
 * Requires the jQuery Form Plugin.
 *
 * @param {Object} event
 */
elgg.dev.inspectSubmit = function(event) {

	$("#developers-inspect-results").hide();
	$("#developers-ajax-loader").show();
	
	$(this).ajaxSubmit({
		dataType : 'json',
		success  : function(response) {
			if (response) {
				$("#developers-inspect-results").html('');
				$("#developers-inspect-results").show();
				$("#developers-ajax-loader").hide();
				elgg.dev.renderInspectTree(response.output);
			}
		}
	});

	event.preventDefault();
};

/**
 * Render the inspection data as a tree
 *
 * Requires the JIT SpaceTree library.
 *
 * @param {Object} data
 */
elgg.dev.renderInspectTree = function(data) {
	var width = $('#developers-inspect-results').width();
	var height = $('#developers-inspect-results').height();

	var st = new $jit.ST({
		injectInto    : 'developers-inspect-results',
		duration      : 200,
		transition    : $jit.Trans.Quart.easeInOut,
		levelDistance : 50,
		levelsToShow  : 1,

		Navigation: {
			enable: true,
			panning: true
		},
		Node: {
			height: 20,
			width: 160,
			type: 'rectangle',
			color: '#aaa',
			overridable: true
		},
		Edge: {
			type: 'line',
			overridable: true
		},

		onCreateLabel: function(label, node) {
			// this is required for tool tips to work
			$(label).attr('id', node.id);

			$(label).html(node.name);
			$(label).addClass('developers-tree-label');

			$(label).click(function() {
				// this prevents the tree from being recentered on the selected node
				var options = {
					Move: {enable: false}
					/*
					onComplete: function() {
						var canvas = st.canvas;
						canvas.resize(2000, 2000);
						//re-select the node to replot the tree
						st.select(st.clickedNode);
					}
					*/
				};
				st.onClick(node.id, options);
			});
		},

		Tips: {
			enable: true,
			type: 'auto',
			offsetX: 10,
			offsetY: 10,
			onShow: elgg.dev.renderInspectTooltip
		}
	});

	st.loadJSON(data);
	st.compute();
	st.switchAlignment("left");
	st.onClick(st.root);
};

/**
 * Render a tree toolip
 *
 * @param {Object} tip
 * @param {Object} node
 */
elgg.dev.renderInspectTooltip = function(tip, node) {
	if (!node.data) {
		return;
	}

	$(tip).addClass("developers-tooltip");

	// @todo client side template
	var templates = {
		tr : '<tr>#{row}</tr>',
		td : '<td>#{cell}</td>'
	};
	var table = '<table class="developers-tooltip-table">';
	$.each(node.data, function (key, val) {
		table += '<tr>';
		table += '<td>' + key + ':</td><td>' + val + '</td>';
		table += '</tr>';
	});
	table += '</table>';

	$(tip).html(table);
};

elgg.register_hook_handler('init', 'system', elgg.dev.init);