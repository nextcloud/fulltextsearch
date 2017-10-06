<?php


use OCA\FullNextSearch\Api\v1\NextSearch;
use OCP\Util;

NextSearch::addJavascriptAPI();
Util::addScript(NextSearch::appName(), 'example');


?>

Full Next Search


<input id="search_input" value="">
<input id="search_submit" type="submit" value="go">

<div id="search_result"></div>

<div id="search_json"></div>


<script id="template_entry" type="text/template">
	<div class="result_entry_default">
		<div class="result_entry_left">
			<div id="title">%%id%%</div>
			<div id="line1"></div>
			<div id="line2"></div>
		</div>
		<div class="result_entry_right">
			<div id="score"></div>
		</div>
	</div>

</script>