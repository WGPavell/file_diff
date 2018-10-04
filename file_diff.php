<?php
	
	const CONFIG_DIRECTORY = 'cfgs/';

	function file_diff_errors(int $error_code) {
		switch($error_code){
			case 1: {return "Файл не выбран"; break;}
			case 2: {return "Файл не найден"; break;}
			default: {return "Неизвестная ошибка";}
		}
	}

	function get_config($conf_file) {
		$cfg_file = file($conf_file);
		$cfg = [];
		$last_param = ""; 
		foreach($cfg_file as $key => $param) {
			if ($param[0] === '!') continue;
			elseif ($param[0] === ' ') {
				$cfg[$last_param]['subvalues'][] = $param;
			}
			else {
				$array = explode(" ", $param);
				$cfg[$array[0]]['value'] = trim(implode(" ", array_slice($array, 1)));
				$last_param = $array[0];
			}
		}
		
		return $cfg;
	}

	function make_session_config($file_name) {
		if($file_name !== ''){
			if(file_exists(CONFIG_DIRECTORY.$file_name)) {
				$cfg = get_config(CONFIG_DIRECTORY.$file_name);
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
	echo "<pre>";
	print_r($cfg1);
	exit();

	//Второй конфиг
	$cfg2 = make_session_config($_SESSION['conf2']);
	
	//Список параметров с обоих файлов
	$cfg_params = array_unique(array_keys(array_merge($cfg1, $cfg2)));
	sort($cfg_params);

	$html_cfg_diffirence = '';

	if(!array_key_exists('error_code', $cfg1) && !array_key_exists('error_code', $cfg2)) {
		$i = 1;
		foreach($cfg_params as $index => $param){
			$html_cfg_diffirence .= "<tr>
						<td class='line-numbers'>".$i++."</td>
						<td class='first_config'>";
			if(array_key_exists($param, $cfg1) && array_key_exists($param, $cfg2)) {
				if($cfg1[$param]['value'] !== $cfg2[$param]['value']) {
					$html_cfg_diffirence .= $cfg_params[$index]."&nbsp;<mark>".$cfg1[$param]['value']."</mark></td>
						<td class='second_config'>".$cfg_params[$index]."&nbsp;<mark>".$cfg2[$param]['value']."</mark></td>";
				}
				else {
					$html_cfg_diffirence .= $cfg_params[$index]."&nbsp;".$cfg1[$param]['value']."</td>
						<td class='second_config'>".$cfg_params[$index]."&nbsp;".$cfg2[$param]['value']."</td>";
				}
			}
			else {
				if(array_key_exists($param, $cfg1)) {
					$html_cfg_diffirence .= "<mark class='lone'>".$cfg_params[$index]."&nbsp;".$cfg1[$param]['value']."</mark></td><td class='second_config'></td>";
				}
				else {
					$html_cfg_diffirence .= "</td><td class='second_config'><mark class='lone'>".$cfg_params[$index]."&nbsp;".$cfg2[$param]['value']."</mark></td>";
				}
			}
			$html_cfg_diffirence .= "</tr>";
		}
	}
	else {
		$html_cfg_diffirence = "<tr><td></td><td class='first_config'>";
		if(array_key_exists('error_code', $cfg1)) {
			$html_cfg_diffirence .= file_diff_errors($cfg1['error_code']);
		}
		$html_cfg_diffirence .= "</td><td class='second_config'>";
		if(array_key_exists('error_code', $cfg2)) {
			$html_cfg_diffirence .= file_diff_errors($cfg2['error_code']);
		}
		$html_cfg_diffirence .= "</td></tr>";
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
			<?=$html_cfg_diffirence ?>
		</table>
	</div>
</body>
</html>
