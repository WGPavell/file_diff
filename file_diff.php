<?php
	$cfg1_file = file("cfgs/conf1.cfg");
	$cfg2_file = file("cfgs/conf2.cfg");
	$cfg1 = array();
	$cfg2 = array();
	foreach($cfg1_file as $key => $param){
		$tmp = explode(" ",$param);
		$tmp_key = $tmp[0];
		unset($tmp[0]);
		$tmp_val = implode(" ",$tmp);
		$cfg1[$tmp_key] = $tmp_val;
	}
	foreach($cfg2_file as $key => $param){
		$tmp = explode(" ",$param);
		$tmp_key = $tmp[0];
		unset($tmp[0]);
		$tmp_val = implode(" ",$tmp);
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
<title>Документ без названия</title>
</head>

<body>
<pre>
<?php print_r($cfg1); ?>
</pre>
<pre>
<?php print_r($cfg2); ?>
</pre>
</body>
</html>