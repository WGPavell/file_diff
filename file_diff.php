<?php
	
	const CONFIG_DIRECTORY = 'cfgs/';

	function file_diff_errors($error_code){
		switch($error_code){
			case 1: {return "Файл не выбран"; break;}
			case 2: {return "Файл не найден"; break;}
		}
	}

	function get_config($conf_file){
		$cfg_file = file($conf_file);
		$cfg = [];
		foreach($cfg_file as $key => $param){
			$array = explode(" ", $param);
			$cfg[$array[0]] = trim(implode(" ", array_slice($array, 1)));
		}
		
		return $cfg;
	}

	function make_session_config($session){
		if($session !== ''){
			if(file_exists(CONFIG_DIRECTORY.$session)){
				$cfg = get_config(CONFIG_DIRECTORY.$session);
			}
			else {
				$cfg['error_code'] = 2;
			}
		}
		else {
			$cfg['error_code'] = 1;
		}
		
		return $cfg;
	}

	session_start();
	if(!isset($_SESSION['conf1'])) $_SESSION['conf1'] = '';
	if(!isset($_SESSION['conf2'])) $_SESSION['conf2'] = '';
	
	if(isset($_POST['conf1'])) $_SESSION['conf1'] = $_POST['conf1'];
	if(isset($_POST['conf2'])) $_SESSION['conf2'] = $_POST['conf2'];

	//Первый конфиг
	$cfg1 = make_session_config($_SESSION['conf1']);

	//Второй конфиг
	$cfg2 = make_session_config($_SESSION['conf2']);
	
	
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
						if (isset($cfg2['error_code'])){
							echo "<td class='second_config'>".file_diff_errors($cfg2['error_code'])."</td>";
							unset($cfg2);
						}
						elseif(isset($cfg2)){
							echo "<td class='second_config'>".$key." ".$cfg2[$key]."</td>";
						}
						echo "</tr>";
					}	
				}
				else {
					echo "<tr>
					<td></td>
					<td class='first_config'>".file_diff_errors($cfg2['error_code'])."</td>
					<td class='second_config'></td>
					</tr>
					";
				}
			?>
		</table>
	</div>
</body>
</html>