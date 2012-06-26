<?php
/**
 * Admin CSS for Elgg Developers plugin
 */
?>
/*** Elgg Developer Tools ***/
#developers-iframe {
	width: 100%;
	height: 600px;
	border: none;
}
#developer-settings-form label {
	margin-right: 5px;
}
.developers-log {
	background-color: #EBF5FF;
	border: 1px solid #999;
	color: #666;
	padding: 20px;
}
/* this is extra CSS is here because the JavaScript InfoVis Toolkit insists on
   putting the origin in the middle canvas. The margin puts the center at the
   top left and the padding is based on the size of the nodes in the tree.
  */
#developers-inspect-wrapper {
	width: 100%;
	height: 600px;
	overflow: hidden;
}
#developers-inspect-results {
	position: relative;
	width: 200%;
	margin-left: -100%;
	padding-left: 50px;
	height: 1200px;
	margin-top: -600px;
	padding-top: 20px;
	overflow: hidden;
}


.developers-tooltip {
	color: #fff;
	background-color: #333;
	opacity: 0.85;
}
.developers-tooltip-table {
	padding: 3px;
}
.developers-tooltip-table td {
	padding: 0 5px;
}

.developers-tree-label {
	width: 160px;
	height: 17px;
	cursor: pointer;
	color: #333;
	font-size: 0.8em;
	text-align: center;
	padding-top: 3px;
}