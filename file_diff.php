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
		return file($conf_file);
		$cfg_file = file($conf_file);
		$cfg = [];
		$keys_count = [];
		foreach($cfg_file as $param) {
			$array = explode(" ", trim($param));
			$keys_count[$array[0]] = (array_key_exists($array[0],$keys_count)) ? $keys_count[$array[0]] + 1 : 1;
			$cfg[$array[0].$keys_count[$array[0]]]["key"] = (($param === ' ') ? " " : "").$array[0];
			$cfg[$array[0].$keys_count[$array[0]]]["value"] = trim(implode(" ", array_slice($array, 1)));
			//$cfg['full_values'][] = $param;
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
	//$cfg1_full_values = $cfg1['full_values'];
	//unset($cfg1['full_values']);

	//Второй конфиг
	$cfg2 = make_session_config($_SESSION['conf2']);
	//$cfg2_full_values = $cfg2['full_values'];
	//unset($cfg2['full_values']);
	
	//Список параметров с обоих файлов
	//$cfg_params = array_keys(array_merge($cfg1, $cfg2));
	//sort($cfg_params);

	$html_cfg_diffirence = '';
	if(!array_key_exists('error_code', $cfg1) && !array_key_exists('error_code', $cfg2)) {
		$k = 1;
		$l = 1;
		$f = 1;
		$cfg1_line = 0;
		$cfg2_line = 0;
		$html_temp_diff = '';
		$html_prev_diff = '';
		$line_limiter = 0;
		for($i = 0; $i <= max(count($cfg1) - 1, count($cfg2) - 1); $i++) {
			if($cfg1_line < count($cfg1)) { //Конец первого конфига еще не достигнут
				if($cfg2_line < count($cfg2) && $cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Найдено прямое совпадение по строкам
					$k = max($k, $l);
					$html_cfg_diffirence .= $html_temp_diff."<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
					$main_sequence = $cfg1[$cfg1_line];
					$html_temp_diff = '';
				} elseif($cfg1[$cfg1_line][0] === ' ') { //Если строка является частью блока
					if(!empty($main_sequence) && $cfg2[$cfg2_line][0] === ' ') {
						$param1 = explode(' ', trim($cfg1[$cfg1_line]));
						$param2 = explode(' ', trim($cfg2[$cfg2_line]));
						$param1_string = '';
						$param2_string = '';
						for($s = 0; $s <= min(count($param1), count($param2)); $s++){
							if($param1_string." ".$param1[$s] !== $param2_string." ".$param2[$s]) break;
							else {
								$param1_string .= $param1[$s]." ";
								$param2_string .= $param2[$s]." ";
								unset($param1[$s]);
								unset($param2[$s]);
							}
						}
						if ($s === 0) {
							$subsequence_finded = false;
							$f = $k;
							$html_prev_diff = "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
							for($j = $cfg2_line + 1; $j <= count($cfg2) - 1; $j++) {
								$param2 = explode(' ', trim($cfg2[$j]));
								$param2_string = '';
								for($s = 0; $s <= min(count($param1), count($param2)); $s++){
									if($param1_string." ".$param1[$s] !== $param2_string." ".$param2[$s]) break;
									else {
										$param1_string .= $param1[$s]." ";
										$param2_string .= $param2[$s]." ";
										unset($param1[$s]);
										unset($param2[$s]);
									}
								}
								if($s !== 0) {
									$subsequence_finded = true;
									break;
								} else {
									$html_prev_diff .= "<tr><td class='line-numbers'>".$f++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
								}
							}
							if($subsequence_finded) {
								$html_cfg_diffirence .= $html_prev_diff."<tr><td class='line-numbers'>".$k++."</td><td class='first_config'> ".$param1_string."<mark>".implode(' ', $param1)."</mark></td><td class='second_config'> ".$param2_string."<mark>".implode(' ', $param2)."</mark></td></tr>";
								$line_limiter = $cfg2_line = $j;
							}
							else {
								$f = $k;
								$html_cfg_diffirence .= "<tr><td class='line-numbers'>".--$k."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
								$k++;
								$cfg2_line--;
							}
						} else {
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'> ".$param1_string."<mark>".implode(' ', $param1)."</mark></td><td class='second_config'> ".$param2_string."<mark>".implode(' ', $param2)."</mark></td></tr>";
							//$cfg2_line++;
						}
					} else {
						if(!empty($html_temp_diff)) {
							$html_temp_diff = '';
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".--$k."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line-1]."</mark></td><td class='second_config'></td></tr>";
							$k++;
						}
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
					}
				} else {
					$l = $k;
					//$f = $k;
					$html_cfg_diffirence .= $html_temp_diff;
					$html_temp_diff = '';
					$searching_param = explode(' ', trim($cfg1[$cfg1_line]));
					$finded_param = null;
					$prev_finded = false;
					for($j = $cfg2_line - 1; $j >= $line_limiter + 1; $j--) { //Поиск прямого совпадения из предыдущих строк
						if($cfg1[$cfg1_line] === $cfg2[$j]) {
							$html_cfg_diffirence .= $html_temp_diff."<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$j]."</td></tr>";
							$prev_finded = true;
							$cfg2_line = $j;
							$main_sequence = $cfg1[$cfg1_line];
							$html_prev_diff = '';
							break;
						}
					}
					if(!$prev_finded) { //Если прямое совпадение из предыдущих строк не найдено
						for($j = $cfg2_line; $j <= count($cfg2) - 1; $j++) {
							if($cfg1[$cfg1_line] === $cfg2[$j]) { //Найдено прямое совпадение в следующих строках
								$k = max($f, $l);
								//$f = max($k, $l, $f);
								$html_cfg_diffirence .= $html_prev_diff.$html_temp_diff."<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$j]."</td></tr>";
								$html_prev_diff = '';
								$html_temp_diff = '';
								//$k = max($k, $l);
								$l = max($k, $l);
								$main_sequence = $cfg1[$cfg1_line];
								$finded_param = null;
								break;
							} elseif($finded_param !== null) { //Если уже имеется частичное совпадение параметров
								$comprasion_param = explode(' ', trim($cfg2[$j]));
								$comprasion_sequence = implode(' ', array_slice($comprasion_param, 0, -count($comprasion_param) + $s));
								for($d = $s; $d < min(count($searching_param), count($comprasion_param)) - 1; $s++){
									if($cfg1_sequence." ".$searching_param[$d] !== $comprasion_sequence." ".$comprasion_param[$d]) break;
									else {
										$cfg1_sequence .= $searching_param[$d]." ";
										$comprasion_sequence .= $comprasion_param[$d]." ";
										unset($searching_param[$d]);
										unset($comprasion_param[$d]);
										$finded_param = $comprasion_param;
										$cfg2_sequence = $comprasion_sequence;
										$s = $d + 1;
										$cfg2_line = $j;
									}
								}
								$html_prev_diff .= "<tr><td class='line-numbers'>".$f++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
							} elseif($finded_param === null && $searching_param[0] === explode(' ', trim($cfg2[$j]))[0]) { //Найдено частичное совпадение
								$finded_param = explode(' ', trim($cfg2[$j]));
								$cfg1_sequence = '';
								$cfg2_sequence = '';
								for($s = 0; $s < min(count($searching_param), count($finded_param)) - 1; $s++){
									if($cfg1_sequence." ".$searching_param[$s] !== $cfg2_sequence." ".$finded_param[$s]) break;
									else {
										$cfg1_sequence .= $searching_param[$s]." ";
										$cfg2_sequence .= $finded_param[$s]." ";
										unset($searching_param[$s]);
										unset($finded_param[$s]);
									}
								}
								$cfg2_line = $j;
								$f = $k = $l;
								$html_prev_diff .= "<tr><td class='line-numbers'>".$f++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
							} elseif($finded_param === null && $cfg2_line < count($cfg2) - 1) { //Нет совпадений для второго конфига
								$html_temp_diff .= "<tr><td class='line-numbers'>".$l++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
							}
						}
						if ($k !== $l || ($cfg2_line >= count($cfg2) - 1 && $finded_param === null)) { //Нет совпадений для первого конфига
							$f = max($k, $l, $f);
							if($cfg2_line >= count($cfg2) - 1) 
								$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
							else 
								$html_temp_diff = "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
							$l = $k;
							$cfg2_line--;
							$i--;
							$main_sequence = '';
							$html_prev_diff = '';
						} elseif($finded_param !== null) { //Есть частичное совпадение со вторым конфигом
							$html_temp_diff .= "<tr><td class='line-numbers'>".$k++."</td></td><td class='first_config'>".(($cfg1[$cfg1_line][0] === ' ') ? " " : "").trim($cfg1_sequence)." <mark>".implode(" ", $searching_param)."</mark></td><td class='second_config'>".(($cfg2[$cfg2_line][0] === ' ') ? " " : "").trim($cfg2_sequence)." <mark>".implode(" ", $finded_param)."</mark></td></tr>";
							if($cfg2_line < count($cfg2) - 1)
								$cfg2_line = min($j, count($cfg2) - 1) - 1;
							$main_sequence = '';
						} else
							$line_limiter = $cfg2_line = min($j, count($cfg2) - 1);
					}
				}
			} elseif($cfg2_line < count($cfg2)) { //Конец второго конфига еще не достигнут
				$html_cfg_diffirence .= $html_temp_diff."<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
				$html_temp_diff = '';
			} else break;
			$cfg1_line += ($cfg1_line < count($cfg1)) ? 1 : 0;
			$cfg2_line += ($cfg2_line < count($cfg2)) ? 1 : 0;
			//echo $cfg1_line." ".$cfg2_line." ";
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
