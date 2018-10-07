<?php

	const CONFIG_DIRECTORY = 'cfgs/';

	function file_diff_errors(int $error_code) {
		switch($error_code){
			case 1: {return "Файл не выбран"; break;}
			case 2: {return "Файл не найден"; break;}
			default: {return "Неизвестная ошибка";}
		}
	}

	function make_session_config($file_name) {
		if($file_name !== ''){
			if(file_exists(CONFIG_DIRECTORY.$file_name)) {
				$cfg = file(CONFIG_DIRECTORY.$file_name);
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

	$html_cfg_diffirence = ''; //Здесь содержится HTML код разницы конфигов
	if(!array_key_exists('error_code', $cfg1) && !array_key_exists('error_code', $cfg2)) {
		$cfg1_size = count($cfg1); //Количество строк 1 конфига
		$cfg2_size = count($cfg2); //Количество строк 2 конфига
		$cfg2_line = 0; //Текущая строка 2 конфига
		$k = 1; //Счетчик строк в таблице
		$line_limiter = 0; //Ограничитель для обратного поиска
		$next_finded = false; //Если поиск из следующих элементов был успешен
		$current_sequence = ''; //Текущий блок параметров
		$temp_k = 1; //Временный счетчик для временного HTML
		$cfg1_finded_line = 0; //Если найдено частичное совпадение, здесь хранится номер строки первого конфига
		for($cfg1_line = 0; ($cfg1_line <= $cfg1_size - 1 && $cfg2_line <= $cfg2_size - 1); $cfg1_line++) { //Проходимся по строкам по 1 конфига
			//if($cfg1_line === 63) break;
			$next_finded = false;
			if(mb_strlen(trim($cfg1[$cfg1_line])) <= 1) { //Если строка пустая или является разделителем (по типу тех, что в cisco "!")
				if($cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Текущие строки конфигов совпадают
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
					$line_limiter = ++$cfg2_line;
				}
				else { //В противном случае эта строка пишется как отсутсвующая
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
				}
			} elseif($cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Текущие строки конфигов совпали
				$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
				if($cfg1_line + 1 <= $cfg1_size - 1 && $cfg1[$cfg1_line+1][0] !== ' ' && $cfg1[$cfg1_line][0] === ' '){ //Если текущая строка является частью верхнего блока, а следующая строка не является частью
					for(++$cfg2_line; ($cfg2_line <= $cfg2_size - 1 && $cfg2[$cfg2_line][0] === ' '); $cfg2_line++) {
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
					}
					$line_limiter = $cfg2_line;
				} else {
					$line_limiter = ++$cfg2_line;
					$current_sequence = $cfg1[$cfg1_line];
				}
			} elseif($cfg1[$cfg1_line][0] === ' '){ //Строка является частью верхнего блока
				if(!empty($current_sequence)) { //Блок имеется в обоих конфигах
					$searching_sequence = explode(' ', trim($cfg1[$cfg1_line]));
					$searching_sequence_string = '';
					$comprasion_sequence_string = '';
					$finded_sequence_index = 0; //Найденный индекс частичного совпадения
					$finded_line = 0; //Номер строки с частичным совпадением
					for($i = $cfg2_line; ($i <= $cfg2_size - 1 && $cfg2[$i][0] === ' '); $i++) { //Проходимся по строкам 2 конфига
						if($cfg1[$cfg1_line] === $cfg2[$i]) { //Если строки конфигов полностью совпадают
							for($j = $line_limiter; $j < $i; $j++){
								$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$j]."</mark></td></tr>";
							}
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$i]."</td></tr>";
							$cfg2_line = $line_limiter = $i + 1;
							$next_finded = true;
							//$current_sequence = $cfg1[$cfg1_line];
							$comprasion_sequence_string = '';
							break;
						} else { //Поиск на частичное совпадение
							if(empty($comprasion_sequence_string)) { //Частичного совпадения еще не было найдено
								$finded_line = $i;
								$comprasion_sequence = explode(' ', trim($cfg2[$i]));
								while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
									  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
									$searching_sequence_string .= " ".$searching_sequence[0];
									$comprasion_sequence_string .= " ".$comprasion_sequence[0];
									$searching_sequence = array_splice($searching_sequence, 1);
									$comprasion_sequence = array_splice($comprasion_sequence, 1);
									$finded_sequence_index++;
								}
								
							}
							elseif(trim($searching_sequence_string." ".$searching_sequence[0]) === implode(' ', array_slice(explode(' ', trim($cfg2[$i])), 0, $finded_sequence_index + 1))) { //Найдено более лучшее частичное совпадение
								$finded_line = $i;
								$comprasion_sequence = explode(' ', trim($cfg2[$i]));
								$comprasion_sequence = array_splice($comprasion_sequence, $finded_sequence_index);
								while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
								  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
									$searching_sequence_string .= $searching_sequence[0];
									$comprasion_sequence_string .= $comprasion_sequence[0];
									$searching_sequence = array_splice($searching_sequence, 1);
									$comprasion_sequence = array_splice($comprasion_sequence, 1);
									$finded_sequence_index++;
								}
							}
						}
					}
					if(!empty($comprasion_sequence_string)) { //Есть частичное совпадение
						$cfg1_line_buff = $cfg1_line;
						for($i = $cfg1_line + 1; $i <= $cfg1_size - 1; $i++) { //Дополнительный проход по следующим строкам 1 конфига
							if($cfg1[$i][0] === ' ' && mb_strlen(trim($cfg1[$i])) > 1) { //Строка должна являться частью верхнего блока и не быть разделителем
								$second_comprasion_sequence = explode(' ', trim($cfg1[$i]));
								if(count($second_comprasion_sequence) < $finded_sequence_index) continue; //Если текущая строка содержит меньше информации, чем найденная ранее, то мы просто прыгаем на следующую итерацию цикла
								$second_comprasion_sequence_string = '';
								for($j = 0; $j < $finded_sequence_index; $j++)
									$second_comprasion_sequence_string .= " ".$second_comprasion_sequence[$j];
								$second_comprasion_sequence = array_splice($second_comprasion_sequence, $finded_sequence_index);
								if(trim($second_comprasion_sequence_string) === trim($searching_sequence_string) && count($second_comprasion_sequence) > 0 && 
								   trim($second_comprasion_sequence_string." ".$second_comprasion_sequence[0]) === trim($comprasion_sequence_string." ".$comprasion_sequence[0])) { //Найдено более лучшее частичное совпадение
									$cfg1_line = $i;
									$searching_sequence = $second_comprasion_sequence;
									$searching_sequence_string = $second_comprasion_sequence_string;
									while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
									  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
										$searching_sequence_string .= " ".$searching_sequence[0];
										$comprasion_sequence_string .= " ".$comprasion_sequence[0];
										$searching_sequence = array_splice($searching_sequence, 1);
										$comprasion_sequence = array_splice($comprasion_sequence, 1);
										$finded_sequence_index++;
									}
								}
							}
						}
						for($i = $cfg1_line_buff; $i < $cfg1_line; $i++) { //Приписываем недостающие строки из 1 конфига
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$i]."</mark></td><td class='second_config'></td></tr>";
						}
						for($i = $line_limiter; $i < $finded_line; $i++){ //Приписываем недостающие строки из 2 конфига
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$i]."</mark></td></tr>";
						}
						$line_limiter = $cfg2_line = $finded_line + 1;
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'> ".trim($searching_sequence_string)." <mark>".implode(' ', $searching_sequence)."</mark></td><td class='second_config'> ".trim($comprasion_sequence_string)." <mark>".implode(' ', $comprasion_sequence)."</mark></td></tr>";
					} elseif(!$next_finded) { //Совпадений нет
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
					}
					if($cfg1[$cfg1_line+1][0] !== ' '){ //Если следующая строка не является частью верхнего блока
						for($cfg2_line; ($cfg2_line <= $cfg2_size - 1 && $cfg2[$cfg2_line][0] === ' '); $cfg2_line++) {
							$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$cfg2_line]."</mark></td></tr>";
						}
						$line_limiter = $cfg2_line;
					}
				} else { //Такого блока нет во 2 конфиге
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
				}
			} else { //Поиск из следующих строк 2 конфига
				$searching_sequence = explode(' ', trim($cfg1[$cfg1_line]));
				$searching_sequence_string = '';
				$comprasion_sequence_string = '';
				$finded_sequence_index = 0; //Найденный индекс частичного совпадения
				$finded_line = 0; //Номер строки с частичным совпадением
				for($i = $cfg2_line; $i <= $cfg2_size - 1; $i++) {
					if($cfg1[$cfg1_line] === $cfg2[$i]) { //Совпадение найдено
						for($j = $line_limiter; $j < $i; $j++){ //Приписываем недостающие строки из 2 конфига
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
							$finded_line = $i;
							$comprasion_sequence = explode(' ', trim($cfg2[$i]));
							while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
								  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
								$searching_sequence_string .= " ".$searching_sequence[0];
								$comprasion_sequence_string .= " ".$comprasion_sequence[0];
								$searching_sequence = array_splice($searching_sequence, 1);
								$comprasion_sequence = array_splice($comprasion_sequence, 1);
								$finded_sequence_index++;
							}
							
						}
						elseif(trim($searching_sequence_string." ".$searching_sequence[0]) === implode(' ', array_slice(explode(' ', trim($cfg2[$i])), 0, $finded_sequence_index + 1))) { //Найдено более лучшее частичное совпадение
							$finded_line = $i;
							$comprasion_sequence = explode(' ', trim($cfg2[$i]));
							$comprasion_sequence = array_splice($comprasion_sequence, $finded_sequence_index);
							while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
							  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
								$searching_sequence_string .= $searching_sequence[0];
								$comprasion_sequence_string .= $comprasion_sequence[0];
								$searching_sequence = array_splice($searching_sequence, 1);
								$comprasion_sequence = array_splice($comprasion_sequence, 1);
								$finded_sequence_index++;
							}
						}
					}
				}
				if(!empty($comprasion_sequence_string)) { //Есть частичное совпадение
					$cfg1_line_buff = $cfg1_line;
					for($i = $cfg1_line + 1; $i <= $cfg1_size - 1; $i++) {
						if($cfg1[$i][0] !== ' ' && mb_strlen(trim($cfg1[$i])) > 1) {
							$second_comprasion_sequence = explode(' ', trim($cfg1[$i]));
							if(count($second_comprasion_sequence) < $finded_sequence_index) continue;
							$second_comprasion_sequence_string = '';
							for($j = 0; $j < $finded_sequence_index; $j++)
								$second_comprasion_sequence_string .= " ".$second_comprasion_sequence[$j];
							$second_comprasion_sequence = array_splice($second_comprasion_sequence, $finded_sequence_index);
							if(trim($second_comprasion_sequence_string) === trim($searching_sequence_string) && count($second_comprasion_sequence) > 0 && 
							   trim($second_comprasion_sequence_string." ".$second_comprasion_sequence[0]) === trim($comprasion_sequence_string." ".$comprasion_sequence[0])) { //Найдено более лучшее частичное совпадение
								$cfg1_line = $i;
								$searching_sequence = $second_comprasion_sequence;
								$searching_sequence_string = $second_comprasion_sequence_string;
								while(count($searching_sequence) > 0 && count($comprasion_sequence) > 0 && //Поиск совпадений
								  $searching_sequence_string." ".$searching_sequence[0] === $comprasion_sequence_string." ".$comprasion_sequence[0]) {
									$searching_sequence_string .= " ".$searching_sequence[0];
									$comprasion_sequence_string .= " ".$comprasion_sequence[0];
									$searching_sequence = array_splice($searching_sequence, 1);
									$comprasion_sequence = array_splice($comprasion_sequence, 1);
									$finded_sequence_index++;
								}
							}
						}
					}
					for($i = $cfg1_line_buff; $i < $cfg1_line; $i++) { //Приписываем недостающие строки из 1 конфига
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$i]."</mark></td><td class='second_config'></td></tr>";
					}
					for($i = $line_limiter; $i < $finded_line; $i++){ //Приписываем недостающие строки из 2 конфига
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'></td><td class='second_config'><mark class='lone'>".$cfg2[$i]."</mark></td></tr>";
					}
					if ($cfg1[$cfg1_line] === $cfg2[$cfg2_line]) { //Если строки конфигов полностью совпадают
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".$cfg1[$cfg1_line]."</td><td class='second_config'>".$cfg2[$cfg2_line]."</td></tr>";
						$current_sequence = $cfg1[$cfg1_line];
					} else { //Если совпадение строк только частичное
						$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'>".trim($searching_sequence_string)." <mark>".implode(' ', $searching_sequence)."</mark></td><td class='second_config'>".trim($comprasion_sequence_string)." <mark>".implode(' ', $comprasion_sequence)."</mark></td></tr>";
						$current_sequence = '';
					}
					$line_limiter = $cfg2_line = $finded_line + 1;
				} elseif(!$next_finded) { //Совпадений нет
					$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
					$current_sequence = '';
				}
			}
		}
		for($cfg1_line; $cfg1_line <= $cfg1_size - 1; $cfg1_line++) //Приписываем недостающие строки из 1 конфига
			$html_cfg_diffirence .= "<tr><td class='line-numbers'>".$k++."</td><td class='first_config'><mark class='lone'>".$cfg1[$cfg1_line]."</mark></td><td class='second_config'></td></tr>";
		for($cfg2_line; $cfg2_line <= $cfg2_size - 1; $cfg2_line++) //Приписываем недостающие строки из 2 конфига
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
