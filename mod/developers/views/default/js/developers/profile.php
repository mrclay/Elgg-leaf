<?php
/**
 * @uses $vars['data']
 */

?>
<script type="text/javascript">
// <![CDATA[
elgg.provide('elgg.dev');
elgg.dev.profileData = <?php echo json_encode($vars['data']) ?>;
// ]]>
</script>