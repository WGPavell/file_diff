<?php
	$cfg1_file = file("cfgs/conf1.cfg");
	$cfg2_file = file("cfgs/conf2.cfg");
	$cfg1 = array();
	$cfg2 = array();
	foreach($cfg1_file as $key => $param){
		$tmp = explode(" ",$param);
		$tmp_key = $tmp[0];
		unset($tmp[0]);
		$tmp_val = trim(implode(" ",$tmp));
		$cfg1[$tmp_key] = $tmp_val;
	}
	foreach($cfg2_file as $key => $param){
		$tmp = explode(" ",$param);
		$tmp_key = $tmp[0];
		unset($tmp[0]);
		$tmp_val = trim(implode(" ",$tmp));
		$cfg2[$tmp_key] = $tmp_val;
	}
	foreach($cfg1 as $key => $val){
		if($val !== $cfg2[$key]){
			$cfg1[$key] = "<mark>".$cfg1[$key]."</mark>";
			$cfg2[$key] = "<mark>".$cfg2[$key]."</mark>";
		}
	}
	ksort($cfg1);
	ksort($cfg2);
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
	<style>
		html, body, div, span, applet, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, a, abbr, acronym, address, big, cite, code, del, dfn, em, img, ins, kbd, q, s, samp, small, strike, strong, sub, sup, tt, var, b, u, i, center, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, embed, figure, figcaption, footer, header, menu, nav, output, ruby, section, summary, time, mark, audio, video, input, textarea {
			margin: 0;
			padding: 0;
			border: 0;
			font: inherit;
			font-size: 100%;
			vertical-align: baseline;	
		}
		#main {
			margin:0 auto;
			min-height:300px;
			background:#eeeeee;
			width: 1200px;
			padding:30px;
		}
		.line-numbers,.first_config,.second_config{
			display: inline-block;
			margin: 0 auto;
			vertical-align: top;
			padding:5px;
			font-family:Consolas, "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", Monaco, "Courier New", "monospace";
			font-size: 16px;
		}
		.first_config,.second_config {
			border: 1px solid #B8B8B8;
		}
		.line-numbers {
			width:2%;
		}
		.first_config {
			width:47%;
			background: #FFFFFF;
		}
		.second_config {
			width:47%;
			background: #FFFFFF;
		}
	</style>
<title>Сравнить конфиги</title>
</head>

<body>
	<div id="main">
		<div class='line-numbers'></div>
		<div class='first_config'>
			<?php 
				foreach($cfg1 as $key => $value){
					echo $key." ".$value."<br>";
				}
			?>
		</div>
		<div class='second_config'>
			<?php 
				foreach($cfg2 as $key => $value){
					echo $key." ".$value."<br>";
				}
			?>
		</div>
	</div>
</body>
</html>