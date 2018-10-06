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
		$cfg1_size = count($cfg1); //Количество строк 1 конфига
		$cfg2_size = count($cfg2); //Количество строк 2 конфига
		//$cfg1_line = 0; //Текущая строка 1 конфига
		$cfg2_line = 0; //Текущая строка 2 конфига
		$k = 1; //Счетчик строк в таблице
		$line_limiter = 0; //Ограничитель для обратного поиска
		$prev_finded = false; //Если поиск из предыдущих элементов был успешен
		$next_finded = false; //Если поиск из следующих элементов был успешен
		$current_sequence = ''; //Текущий блок параметров
		$html_buffer_diff = ''; //Здесь хранится временный HTML для частичных совпадений
		$temp_k = 1; //Временный счетчик для временного HTML
		$cfg1_finded_line = 0; //Если найдено частичное совпадение, здесь хранится номер строки первого конфига
		for($cfg1_line = 0; $cfg1_line <= $cfg1_size - 1; $cfg1_line++) {
			$prev_finded = $next_finded = false;
			if(mb_strlen(trim($cfg1[$cfg1_line])) <= 1) { //Если строка пустая или является разделителем (по типу тех, что в cisco "!")
				if($cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Текущие строки конфигов совпадают
					$html_buffer_diff = '';
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
					$line_limiter = ++$cfg2_line;
				}
				else { //В противном случае эта строка пишется как отсутсвующая
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
				}
			} elseif($cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Текущие строки конфигов совпали
				$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
				$line_limiter = ++$cfg2_line;
				$current_sequence = $cfg1[$cfg1_line];
			} elseif($cfg1[$cfg1_line][0] === ' '){ //Строка является частью верхнего блока
				if(!empty($current_sequence)) {
					for($i = $cfg2_line + 1; ($i <= $cfg2_size - 1 && $cfg2[$i][0] === ' '); $i++) {
						if($cfg1[$cfg1_line] === $cfg2[$i]) {
							for($j = $line_limiter; $j < $i; $j++){
								$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
							}
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$i]."</td></tr>";
							$cfg2_line = $line_limiter = $i + 1;
							$next_finded = true;
							$current_sequence = $cfg1[$cfg1_line];
							break;
						}
					}
					if(!$next_finded) { //Совпадений нет
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
					}
					if($cfg1[$cfg1_line+1][0] !== ' ')
						for($cfg2_line; ($cfg2_line <= $cfg2_size - 1 && $cfg2[$cfg2_line][0] === ' '); $cfg2_line++) {
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
						}
				}
				else {
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
				}
			} else { //Поиск из следующих строк 2 конфига
				//echo $cfg1[$cfg1_line]." ".$cfg2_line."<br>";
				$searching_sequence = explode(' ', trim($cfg1[$cfg1_line]));
				$searching_sequence_string = $searching_sequence[0];
				unset($searching_sequence[0]);
				$comprasion_sequence_string = '';
				$finded_sequence_index = 0; //Найденный индекс частичного совпадения
				$finded_line = 0; //Номер строки с частичным совпадением
				for($i = $cfg2_line; $i <= $cfg2_size - 1; $i++) {
					if($cfg1[$cfg1_line] === $cfg2[$i]) { //Совпадение найдено
						$html_buffer_diff = '';
						for($j = $line_limiter; $j < $i; $j++){
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
						}
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$i]."</td></tr>";
						$cfg2_line = $line_limiter = $i + 1;
						$next_finded = true;
						$current_sequence = $cfg1[$cfg1_line];
						$comprasion_sequence_string = '';
						break;
					} elseif($cfg2[$i][0] !== ' ') { //Поиск на частичное совпадение
						if(empty($comprasion_sequence_string)) { //Частичного совпадения еще не было найдено
							$comprasion_sequence = explode(' ', trim($cfg2[$i]));
							if($searching_sequence_string === $comprasion_sequence[0]) { //Найдено первое частичное совпадение
								$comprasion_sequence_string = $comprasion_sequence[0];
								unset($comprasion_sequence[0]);
								$finded_sequence_index++;
								$finded_line = $i;
								$cfg1_finded_line = $cfg1_line;
								for($g = 0; $g <= count($searching_sequence) - 1; $g++) {
									if($g <= count($comprasion_sequence) - 1 && $searching_sequence_string === $comprasion_sequence_string." ".$comprasion_sequence[$g + $finded_sequence_index]) {
										$searching_sequence_string .= " ".$searching_sequence[$g + $finded_sequence_index];
										unset($searching_sequence[$g]);
										$comprasion_sequence_string .= " ".$comprasion_sequence[$g + $finded_sequence_index];
										unset($comprasion_sequence[$g]);
										$finded_sequence_index++;
									}
									else break;
								}
							}
						}
						else { //При наличии частичного совпадения
							if($searching_sequence_string." ".$searching_sequence[$finded_sequence_index] === implode(' ', array_slice(explode(' ', trim($cfg2[$i])), 0, $finded_sequence_index + 1))) { //Найдено более лучшее частичное совпадение
								$searching_sequence_string .= " ".$searching_sequence[$finded_sequence_index];
								unset($searching_sequence[$finded_sequence_index]);
								$comprasion_sequence = explode(' ', trim($cfg2[$i]));
								$comprasion_sequence_string .= " ".$comprasion_sequence[$finded_sequence_index];
								for($g = 0; $g <= $finded_sequence_index; $g++)
									unset($comprasion_sequence[$g]);
								$finded_sequence_index++;
								$finded_line = $i;
								for($g = 0; $g <= count($searching_sequence) - 1; $g++) {
									if($g <= count($comprasion_sequence) - 1 &&
									   $searching_sequence_string." ".$searching_sequence[$g + $finded_sequence_index] ===
									   $comprasion_sequence_string." ".$comprasion_sequence[$g + $finded_sequence_index]) {
										$searching_sequence_string .= " ".$searching_sequence[$g + $finded_sequence_index];
										unset($searching_sequence[$g]);
										$comprasion_sequence_string .= " ".$comprasion_sequence[$g + $finded_sequence_index];
										unset($comprasion_sequence[$g]);
										$finded_sequence_index++;
										//$g--;
									}
									else break;
								}
							}
						}
					}
				}
				if(!empty($comprasion_sequence_string)) { //Есть частичное совпадение
					$temp_k = $k;
					for($j = $line_limiter; $j < $finded_line; $j++){
						$html_buffer_diff .= "<tr><td class='line-numbers'>".$temp_k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
					}
					$html_buffer_diff .= "<tr><td class='line-numbers'>".$temp_k++."</td><td class='first_config'>".$searching_sequence_string." <mark>".implode(' ', $searching_sequence)."</mark></td><td class='second_config'>".$comprasion_sequence_string." <mark>".implode(' ', $comprasion_sequence)."</mark></td></tr>";
					//$cfg2_line = $line_limiter = $finded_line + 1;
					$current_sequence = '';
					//$next_finded = true;
					//$current_sequence = $cfg1[$cfg1_line];
					//break;
				} elseif (!empty($html_buffer_diff)) {
					$k = $temp_k;
					$html_cfg_diffirence .= $html_buffer_diff;
					$cfg2_line = $line_limiter = $finded_line + 1;
					$html_buffer_diff = '';
				} elseif(!$next_finded) { //Совпадений нет
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
					$current_sequence = '';
				}
			}
		}
		for($cfg2_line; $cfg2_line <= $cfg2_size - 1; $cfg2_line++)
			$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
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
