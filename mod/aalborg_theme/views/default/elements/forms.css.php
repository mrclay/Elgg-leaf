/* <style> /**/
/**
 * CSS form/input elements
 */

/* ***************************************
	Form Elements
*************************************** */
fieldset > div, .elgg-field {
	margin-bottom: 15px;
}
fieldset > div:last-child {
	margin-bottom: 0;
}
.elgg-form-alt > fieldset > .elgg-foot {
	border-top: 1px solid #DCDCDC;
	padding: 10px 0;
}
label, .elgg-field-label {
	font-weight: bold;
	color: #333;
	font-size: 110%;
}
.elgg-field-label {
	display: block;
}
label.elgg-state-disabled, .elgg-field-label.elgg-state-disabled {
	opacity: 0.6;
}
.elgg-required-indicator {
	font-size: 110%;
	font-weight: bold;
	color: #C24000;
	display: inline;
	padding: 0 5px;
}
input, textarea {
	border: 1px solid #DCDCDC;
	color: #666;
	font: 100% Arial, Helvetica, sans-serif;
	padding: 7px 6px;
	width: 100%;
	border-radius: 3px;

	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
input[type=email]:focus,
input[type=password]:focus,
input[type=text]:focus,
input[type=url]:focus,
textarea:focus {
	border: solid 1px #C2C2C2;
	background: #F0F0F0;
	/* We remove outlines from specific input types so we can leave the browser
	   defaults (like glows) for everything else */
	outline: 0 none;
}
.elgg-longtext-control {
	float: right;
	margin-left: 14px;
	font-size: 80%;
	cursor: pointer;
}
.elgg-input-access {
	margin: 5px 0 0 0;
}

input[type="checkbox"],
input[type="radio"] {
	margin: 0 3px 0 0;
	padding: 0;
	border: none;
	width: auto;
}
.elgg-input-checkboxes.elgg-horizontal li,
.elgg-input-radios.elgg-horizontal li {
	display: inline;
	padding-right: 10px;
}
select {
	max-width: 100%;
	padding: 4px; 
}
.elgg-form-account {
	margin-bottom: 15px;
}
.elgg-form-login, .elgg-form-account {
	max-width: 475px;
	margin: 0 auto;
}

/* ***************************************
	FRIENDS PICKER
*************************************** */
.elgg-friendspicker-list {
	max-height: 200px;
	overflow-y: auto;
	border: 1px solid #DCDCDC;
}
.elgg-friendspicker-list > .elgg-item {
	display: inline-block;
	vertical-align: top;
	border: none;
}
.elgg-friendspicker-list > .elgg-item label {
	display: block;
	margin: 5px;
}
.elgg-friendspicker-checkbox,
.elgg-friendspicker-icon,
.elgg-friendspicker-name {
	display: inline-block;
	line-height: 25px;
	vertical-align: middle;
	margin-right: 5px;
}
.elgg-friendspicker-name {
	width: 170px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.elgg-friendspicker-footer {
	display: block;
	margin: 15px 5px 5px;
}

/* ***************************************
	AUTOCOMPLETE
*************************************** */
<?php //autocomplete will expand to fullscreen without max-width ?>
.ui-autocomplete {
	position: absolute;
	cursor: default;
	z-index: 10000;
}
.elgg-autocomplete-item .elgg-body {
	max-width: 600px;
}
.ui-autocomplete {
	background-color: #FFF;
	border: 1px solid #DCDCDC;
	overflow: hidden;
	border-radius: 3px;
}
.ui-autocomplete .ui-menu-item {
	padding: 0px 4px;
	border-radius: 3px;
}
.ui-autocomplete .ui-menu-item:hover {
	background-color: #EEE;
}
.ui-autocomplete a:hover {
	text-decoration: none;
	color: #5097CF;
}
.ui-autocomplete a.ui-state-hover {
	background-color: #EEE;
	display: block;
}
.ui-helper-hidden-accessible {
	border: 0;
	clip: rect(0 0 0 0);
	height: 1px;
	margin: -1px;
	overflow: hidden;
	padding: 0;
	position: absolute;
	width: 1px;
}

/* ***************************************
	USER PICKER
*************************************** */
.elgg-user-picker-list li:first-child {
	border-top: 1px dotted #ccc;
	margin-top: 5px;
}
.elgg-user-picker-list > li {
	border-bottom: 1px dotted #ccc;
}
.elgg-user-picker.elgg-state-disabled > input,
.elgg-user-picker.elgg-state-disabled > label {
	display: none;
}
.elgg-user-picker-remove {
	cursor: pointer;
}

/* ***************************************
	DATE PICKER
**************************************** */
.ui-datepicker {
	display: none;

	margin-top: 3px;
	background-color: #FFF;
	border: 1px solid #0054A7;
	border-radius: 3px;
	overflow: hidden;
	box-shadow: 4px 4px 4px rgba(0, 0, 0, 0.5);
}
.ui-datepicker-inline {
	box-shadow: none;
	max-width: 225px;
}

.ui-datepicker-header {
	position: relative;
	background: #5097CF;
	color: #FFF;
	padding: 2px 0;
	border-bottom: 1px solid #0054A7;
}
.ui-datepicker-header a {
	color: #FFF;
}
.ui-datepicker-prev, .ui-datepicker-next {
	position: absolute;
	top: 3px;
	cursor: pointer;
	border: 1px solid #fff;
	border-radius: 3px;
	padding: 1px 7px;
}
.ui-datepicker-prev:hover,
.ui-datepicker-next:hover {
	text-decoration: none;
}
.ui-datepicker-prev {
	left: 3px;
}
.ui-datepicker-next {
	right: 3px;
}
.ui-datepicker-title {
	line-height: 1.8em;
	margin: 0 30px;
	text-align: center;
	font-weight: bold;
}
.ui-datepicker-calendar {
	margin: 4px;
}
.ui-datepicker th {
	color: #0054A7;
	border: none;
	font-weight: bold;
	padding: 5px 6px;
	text-align: center;
}
.ui-datepicker td {
	padding: 1px;
}
.ui-datepicker td span, .ui-datepicker td a {
	display: block;
	padding: 2px;
	line-height: 1.2em;
	text-align: right;
	text-decoration: none;
}
.ui-datepicker-calendar .ui-state-default {
	border: 1px solid #DCDCDC;
	color: #5097CF;;
	background: #FAFAFA;
}
.ui-datepicker-calendar .ui-state-hover {
	border: 1px solid #AAA;
	color: #0054A7;
	background: #EEE;
}
.ui-datepicker-calendar .ui-state-active,
.ui-datepicker-calendar .ui-state-active.ui-state-hover {
	font-weight: bold;
	border: 1px solid #0054A7;
	color: #0054A7;
	background: #E4ECF5;
}

