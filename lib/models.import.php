<?php

if (!function_exists('models_import_h')) {
	function models_import_h($value) {
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}

	function models_import_normalize_name($name) {
		$name = strip_tags((string)$name);
		$name = str_replace(array("\r", "\n", "\t"), " ", $name);
		$name = preg_replace('/\s+/', ' ', $name);
		return trim($name);
	}

	function models_import_find_existing_id($name) {
		$result = false;
		$name = strtolower(models_import_normalize_name($name));
		if ($name === '') {
			return false;
		}

		$db = DB::get();
		if ($db) {
			$sql = "SELECT id_model FROM model WHERE LOWER(name) = ? LIMIT 1";
			$stmt = $db->prepare($sql);
			if ($stmt) {
				if ($stmt->bind_param("s", $name) && $stmt->execute()) {
					$stmt->bind_result($result);
					$stmt->fetch();
				}
				$stmt->close();
			}

			if (!$result) {
				$sql = "SELECT model_id FROM model_names WHERE LOWER(name) = ? LIMIT 1";
				$stmt = $db->prepare($sql);
				if ($stmt) {
					if ($stmt->bind_param("s", $name) && $stmt->execute()) {
						$stmt->bind_result($result);
						$stmt->fetch();
					}
					$stmt->close();
				}
			}
		}

		return $result ? (int)$result : false;
	}

	function models_import_parse_line($line) {
		$line = trim((string)$line);
		if ($line === '') {
			return false;
		}

		$parts = explode('|', $line, 2);
		$name = models_import_normalize_name($parts[0]);
		if ($name === '') {
			return false;
		}

		$aliases = array();
		if (isset($parts[1])) {
			foreach (explode(',', $parts[1]) as $alias) {
				$alias = models_import_normalize_name($alias);
				if ($alias !== '' && strtolower($alias) !== strtolower($name)) {
					$aliases[strtolower($alias)] = $alias;
				}
			}
		}

		return array(
			'name' => $name,
			'aliases' => array_values($aliases),
		);
	}
}

$models = new CModels($db->_db);
$importResult = array();
$defaultSex = (isset($_POST['sex']) && in_array($_POST['sex'], array('female', 'male', 'shemale'), true)) ? $_POST['sex'] : 'female';
$inputText = isset($_POST['models_import_text']) ? (string)$_POST['models_import_text'] : '';

if (isset($_POST['importModels'])) {
	if (!$userAuth->isAdmin()) {
		$importResult[] = array('type' => 'error', 'text' => 'Нет прав на импорт моделей');
	} else {
		$lines = preg_split('/\R/u', $inputText);
		$lineNumber = 0;
		foreach ($lines as $line) {
			$lineNumber++;
			$parsed = models_import_parse_line($line);
			if (!$parsed) {
				continue;
			}

			$name = $parsed['name'];
			$aliases = $parsed['aliases'];

			$existingId = models_import_find_existing_id($name);
			if ($existingId) {
				$importResult[] = array(
					'type' => 'skip',
					'text' => "Строка {$lineNumber}: модель уже есть #{$existingId} - {$name}",
				);
				continue;
			}

			$aliasConflict = false;
			foreach ($aliases as $alias) {
				$aliasExistingId = models_import_find_existing_id($alias);
				if ($aliasExistingId) {
					$aliasConflict = $aliasExistingId;
					$importResult[] = array(
						'type' => 'skip',
						'text' => "Строка {$lineNumber}: AKA '{$alias}' уже привязан к модели #{$aliasExistingId}, строка пропущена",
					);
					break;
				}
			}
			if ($aliasConflict) {
				continue;
			}

			$modelId = $models->addModel($name, $defaultSex, 'brown', 'none', 'yes', '1000-01-01', 0, 0, 'versatile', '', 0, 0, 'none', 'none', 'none', 'none', 'none', '', '', $aliases);
			if ($modelId) {
				if (isset($cache_worker) && is_object($cache_worker)) {
					$cache_worker->server_cacheModel($modelId);
				}
				$importResult[] = array(
					'type' => 'ok',
					'text' => "Добавлена модель #{$modelId}: {$name}" . ($aliases ? " | AKA: ".implode(', ', $aliases) : ''),
				);
			} else {
				$importResult[] = array(
					'type' => 'error',
					'text' => "Строка {$lineNumber}: ошибка добавления модели '{$name}'",
				);
			}
		}
	}
}
?>
<div style="max-width: 1100px; margin: 15px auto; text-align: left;">
	<h2>Импорт моделей</h2>
	<p>Формат строки: <code>Model Name|Aka name,Aka name 2,Aka Name 3</code>. Можно указывать только имя без <code>|</code>.</p>
	<form method="post" action="index.php?act=models_import" style="padding: 12px; background: #eee; border: 1px solid #ccc;">
		<div style="margin-bottom: 10px;">
			<label>
				Пол:
				<select name="sex">
					<option value="female"<?=$defaultSex == 'female' ? ' selected' : ''?>>female</option>
					<option value="male"<?=$defaultSex == 'male' ? ' selected' : ''?>>male</option>
					<option value="shemale"<?=$defaultSex == 'shemale' ? ' selected' : ''?>>shemale</option>
				</select>
			</label>
		</div>
		<textarea name="models_import_text" style="width: 100%; height: 360px; font-family: monospace;"><?=models_import_h($inputText)?></textarea>
		<div style="margin-top: 10px;">
			<input type="submit" name="importModels" value="Импортировать">
			<a href="index.php?act=models" style="margin-left: 15px;">Назад к моделям</a>
		</div>
	</form>

	<?php if ($importResult) { ?>
		<h3>Результат</h3>
		<div style="border: 1px solid #ccc;">
			<?php foreach ($importResult as $row) {
				$color = '#222';
				if ($row['type'] == 'ok') $color = '#0a7a24';
				elseif ($row['type'] == 'skip') $color = '#8a6d00';
				elseif ($row['type'] == 'error') $color = '#a30000';
			?>
				<div style="padding: 5px 8px; border-bottom: 1px solid #eee; color: <?=$color?>;">
					<?=models_import_h($row['text'])?>
				</div>
			<?php } ?>
		</div>
	<?php } ?>
</div>
