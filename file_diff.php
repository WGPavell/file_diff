<?php

	function file_diff_errors($error_code){
		switch($error_code){
			case 1: return "Файл не найден"; break;
		}
	}

	session_start();
	if(!isset($_SESSION['conf1'])) $_SESSION['conf1'] = '';
	if(!isset($_SESSION['conf2'])) $_SESSION['conf2'] = '';
	
	if(isset($_POST['conf1'])) $_SESSION['conf1'] = $_POST['conf1'];
	if(isset($_POST['conf2'])) $_SESSION['conf2'] = $_POST['conf2'];

	if($_SESSION['conf1'] !== ''){
		if(file_exists("cfgs/".$_SESSION['conf1'])){
			$cfg1_file = file("cfgs/".$_SESSION['conf1']);
			$cfg1 = [];
			foreach($cfg1_file as $key => $param){
				$tmp = explode(" ",$param);
				$tmp_key = $tmp[0];
				unset($tmp[0]);
				$tmp_val = trim(implode(" ",$tmp));
				$cfg1[$tmp_key] = $tmp_val;
			}
		}
	}
	else {
		$cfg1['error_code'] = 1;
	}

	if($_SESSION['conf2'] !== ''){
		if(file_exists("cfgs/".$_SESSION['conf2'])){
			$cfg2_file = file("cfgs/".$_SESSION['conf2']);
			$cfg2 = array();
			foreach($cfg2_file as $key => $param){
				$tmp = explode(" ",$param);
				$tmp_key = $tmp[0];
				unset($tmp[0]);
				$tmp_val = trim(implode(" ",$tmp));
				$cfg2[$tmp_key] = $tmp_val;
			}
		}
	}
	else {
		$cfg2['error_code'] = 1;
	}
	
	if(!isset($cfg1['error_code']) && !isset($cfg2['error_code'])) {
		foreach($cfg1 as $key => $val){
			if($val !== $cfg2[$key]){
				$cfg1[$key] = "<mark>".$cfg1[$key]."</mark>";
				$cfg2[$key] = "<mark>".$cfg2[$key]."</mark>";
			}
		}
		ksort($cfg1);
		ksort($cfg2);
	}
	
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
<title>Сравнить конфиги</title>
</head>

<body>
	<div id="main">
		<table style='width:100%;'>
			<tr>
				<td class='line-numbers'></td>
				<td class='first_config'><form action="file_diff.php" method="post"><input type='text' value='<?=$_SESSION['conf1'] ?>' name='conf1' placeholder="Первый конфиг"></form></td>
				<td class='second_config'><form action="file_diff.php" method="post"><input type='text' value='<?=$_SESSION['conf2'] ?>' name='conf2' placeholder="Второй конфиг"></form></td>
			</tr>
			<?php 
				$i = 1;
			    if (!isset($cfg1['error_code'])){
					foreach($cfg1 as $key => $value){
						echo "<tr>
						<td class='line-numbers'>".$i++."</td>
						<td class='first_config'>".$key." ".$value."</td>";
						if(!isset($cfg2['error_code'])){
							echo "<td class='second_config'>".$key." ".$cfg2[$key]."</td>";
						}
						else {
							//echo "<td class='second_config'>".file_diff_errors($cfg2['error_code'])."</td>";
						}
						echo "</tr>";
					}	
				}
			?>
		</table>
	</div>
</body>
</html>