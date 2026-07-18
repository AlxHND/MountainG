<?php

if (!function_exists('affiliate_programs_h')) {
	function affiliate_programs_h($value)
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('affiliate_programs_lower')) {
	function affiliate_programs_lower($value)
	{
		$value = (string)$value;
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($value, 'UTF-8');
		}
		return strtolower($value);
	}
}

$sources = new Sources($db->_db);
$message = '';
$messageType = 'ok';
$editingProgram = false;

if (!$userAuth->isAdmin()) {
	echo "<div style=\"padding:20px; color:#900;\">Недостаточно прав для управления affiliate programs.</div>";
	return;
}

if (isset($_POST['save_affiliate_program'])) {
	$affiliateProgramId = isset($_POST['affiliate_program_id']) ? (int)$_POST['affiliate_program_id'] : 0;
	$name = isset($_POST['affiliate_program_name']) ? trim((string)$_POST['affiliate_program_name']) : '';
	$url = isset($_POST['affiliate_program_url']) ? trim((string)$_POST['affiliate_program_url']) : '';
	$description = isset($_POST['affiliate_program_description']) ? trim((string)$_POST['affiliate_program_description']) : '';

	try {
		$savedId = $sources->saveAffiliateProgram($name, $url, $description, $affiliateProgramId);
		$message = $affiliateProgramId ? "Affiliate program #{$savedId} обновлена." : "Affiliate program #{$savedId} создана.";
	} catch (Exception $e) {
		$message = "Ошибка сохранения affiliate program: " . $e->getMessage();
		$messageType = 'error';
		$editingProgram = array(
			'affiliate_program_id' => $affiliateProgramId,
			'affiliate_program_name' => $name,
			'affiliate_program_url' => $url,
			'affiliate_program_description' => $description,
		);
	}
}

if (isset($_POST['delete_affiliate_program']) && isset($_POST['affiliate_program_id'])) {
	$affiliateProgramId = (int)$_POST['affiliate_program_id'];
	if ($sources->deleteAffiliateProgram($affiliateProgramId)) {
		$message = "Affiliate program #{$affiliateProgramId} удалена.";
	} else {
		$message = "Affiliate program #{$affiliateProgramId} не удалена. Возможно, она уже привязана к платникам.";
		$messageType = 'error';
	}
}

if (!$editingProgram && isset($_GET['edit_program'])) {
	$editingProgram = $sources->getAffiliateProgramById((int)$_GET['edit_program']);
	if (!$editingProgram) {
		$message = "Affiliate program не найдена.";
		$messageType = 'error';
	}
}

if (!$editingProgram) {
	$editingProgram = array(
		'affiliate_program_id' => 0,
		'affiliate_program_name' => '',
		'affiliate_program_url' => '',
		'affiliate_program_description' => '',
	);
}

$affiliatePrograms = $sources->getAffiliatePrograms();
$affiliateProgramsRows = '';
if ($affiliatePrograms) {
	foreach ($affiliatePrograms as $program) {
		$programId = (int)$program['affiliate_program_id'];
		$programName = isset($program['affiliate_program_name']) ? trim((string)$program['affiliate_program_name']) : '';
		$programUrl = isset($program['affiliate_program_url']) ? trim((string)$program['affiliate_program_url']) : '';
		$programDescription = isset($program['affiliate_program_description']) ? trim((string)$program['affiliate_program_description']) : '';
		$paysitesCount = isset($program['paysites_count']) ? (int)$program['paysites_count'] : 0;
		$updatedAt = isset($program['updated_at']) ? trim((string)$program['updated_at']) : '';
		$updatedAtTs = $updatedAt !== '' ? strtotime($updatedAt) : 0;
		if ($updatedAtTs === false) {
			$updatedAtTs = 0;
		}

		$programFilterText = affiliate_programs_lower(trim($programName . ' ' . $programUrl . ' ' . $programDescription));

		$affiliateProgramsRows .= "<tr class='affiliate-program-row' data-name='" . affiliate_programs_h(affiliate_programs_lower($programName)) . "' data-filter='" . affiliate_programs_h($programFilterText) . "'>";
		$affiliateProgramsRows .= "<td align='center' data-sort='" . $programId . "'>" . $programId . "</td>";
		$affiliateProgramsRows .= "<td data-sort='" . affiliate_programs_h(affiliate_programs_lower($programName)) . "'>
				<strong>" . affiliate_programs_h($programName) . "</strong>";
		if ($programDescription !== '') {
			$affiliateProgramsRows .= "<div class='affiliate-programs-muted'>" . affiliate_programs_h($programDescription) . "</div>";
		}
		$affiliateProgramsRows .= "</td>";
		$affiliateProgramsRows .= "<td data-sort='" . affiliate_programs_h(affiliate_programs_lower($programUrl)) . "' class='affiliate-programs-url'>" . affiliate_programs_h($programUrl) . "</td>";
		$affiliateProgramsRows .= "<td align='center' data-sort='" . $paysitesCount . "'>" . $paysitesCount . "</td>";
		$affiliateProgramsRows .= "<td align='center' data-sort='" . (int)$updatedAtTs . "'>" . affiliate_programs_h($updatedAt) . "</td>";
		$affiliateProgramsRows .= "<td align='center'>
				<a href='index.php?act=affiliate_programs&amp;edit_program=" . $programId . "'>Edit</a>";
		if ($paysitesCount === 0) {
			$affiliateProgramsRows .= "
				<form method='post' action='index.php?act=affiliate_programs' style='display:inline;' onsubmit=\"return confirm('Удалить affiliate program?');\">
					<input type='hidden' name='affiliate_program_id' value='" . $programId . "'>
					<input type='submit' name='delete_affiliate_program' value='Delete' style='color:#900; background:none; border:none; cursor:pointer; text-decoration:underline;'>
				</form>";
		} else {
			$affiliateProgramsRows .= " <span class='affiliate-programs-muted'>In use</span>";
		}
		$affiliateProgramsRows .= "</td>";
		$affiliateProgramsRows .= "</tr>";
	}
}
?>

<div style="width: 1400px; margin: 0 auto; text-align:left;">
	<div style="padding: 0 0 12px 0;">
		<a href="index.php?act=affiliate_programs">Список affiliate programs</a>
		|
		<a href="index.php?act=affiliate_programs&amp;add=1">Добавить новую</a>
		|
		<a href="index.php?act=paysites">К платникам</a>
	</div>

	<?php if ($message !== '') { ?>
		<div style="padding:10px 12px; margin-bottom:14px; border:1px solid <?=$messageType === 'error' ? '#d99' : '#bcd'?>; background: <?=$messageType === 'error' ? '#fff0f0' : '#f3fbff'?>;">
			<?=htmlspecialchars($message, ENT_QUOTES, 'UTF-8')?>
		</div>
	<?php } ?>

	<div style="float:left; width: 420px; margin-right: 20px;">
		<h3 style="margin-top:0;"><?= !empty($editingProgram['affiliate_program_id']) ? 'Редактирование affiliate program' : 'Новая affiliate program' ?></h3>
		<form method="post" action="index.php?act=affiliate_programs<?php if (!empty($editingProgram['affiliate_program_id'])) { ?>&amp;edit_program=<?=(int)$editingProgram['affiliate_program_id']?><?php } ?>">
			<input type="hidden" name="affiliate_program_id" value="<?=(int)$editingProgram['affiliate_program_id']?>">
			<table class="disclaim" cellpadding="4" cellspacing="2" width="100%">
				<tr>
					<td bgcolor="#e4e4e4" width="120">Name</td>
					<td bgcolor="#e4e4e4">
						<input type="text" name="affiliate_program_name" value="<?=htmlspecialchars($editingProgram['affiliate_program_name'], ENT_QUOTES, 'UTF-8')?>" style="width:98%;">
					</td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">URL</td>
					<td bgcolor="#e4e4e4">
						<input type="text" name="affiliate_program_url" value="<?=htmlspecialchars($editingProgram['affiliate_program_url'], ENT_QUOTES, 'UTF-8')?>" style="width:98%;">
					</td>
				</tr>
				<tr>
					<td bgcolor="#e4e4e4">Description</td>
					<td bgcolor="#e4e4e4">
						<textarea name="affiliate_program_description" rows="6" style="width:98%;"><?=htmlspecialchars($editingProgram['affiliate_program_description'], ENT_QUOTES, 'UTF-8')?></textarea>
					</td>
				</tr>
			</table>
			<div style="padding-top:10px;">
				<input type="submit" name="save_affiliate_program" value="<?= !empty($editingProgram['affiliate_program_id']) ? 'Сохранить' : 'Создать' ?>">
				<?php if (!empty($editingProgram['affiliate_program_id'])) { ?>
					<a href="index.php?act=affiliate_programs" style="margin-left:10px;">Сбросить</a>
				<?php } ?>
			</div>
		</form>
	</div>

	<div style="overflow:hidden;">
		<h3 style="margin-top:0;">Список программ</h3>
		<style type="text/css">
			.affiliate-programs-controls {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
				align-items: center;
				margin: 0 0 12px;
				padding: 12px;
				background: #f7f8fb;
				border: 1px solid #d8deea;
			}

			.affiliate-programs-controls input[type="text"] {
				height: 32px;
				min-width: 280px;
				padding: 0 8px;
				border: 1px solid #bfc7d6;
				box-sizing: border-box;
			}

			.affiliate-programs-summary {
				margin: 0 0 10px;
				color: #444;
			}

			.affiliate-programs-table-wrap {
				border: 1px solid #d8deea;
				background: #fff;
				overflow-x: auto;
			}

			.affiliate-programs-table {
				width: 100%;
				border-collapse: collapse;
				min-width: 880px;
				background: #fff;
			}

			.affiliate-programs-table th,
			.affiliate-programs-table td {
				padding: 10px 12px;
				border-bottom: 1px solid #e6eaf2;
				vertical-align: top;
			}

			.affiliate-programs-table th {
				background: #f2f5fa;
				color: #223;
				font-weight: bold;
				white-space: nowrap;
			}

			.affiliate-programs-table tbody tr:hover {
				background: #fafcff;
			}

			.affiliate-programs-sortable {
				cursor: pointer;
				user-select: none;
			}

			.affiliate-programs-sort-indicator {
				display: inline-block;
				width: 14px;
				color: #667;
			}

			.affiliate-programs-muted {
				padding-top: 4px;
				color: #666;
				font-size: 11px;
			}

			.affiliate-programs-url {
				word-break: break-all;
			}

			.affiliate-programs-empty {
				padding: 18px 12px;
				color: #666;
				text-align: center;
			}
		</style>

		<div class="affiliate-programs-controls">
			<input type="text" id="affiliate-programs-search" placeholder="Фильтр по имени, URL или описанию..." autocomplete="off" />
		</div>

		<div class="affiliate-programs-summary">
			Строк в текущем списке: <strong id="affiliate-programs-visible-count"><?= $affiliatePrograms ? count($affiliatePrograms) : 0 ?></strong>
		</div>

		<div class="affiliate-programs-table-wrap">
			<table class="affiliate-programs-table" id="affiliate-programs-table">
				<thead>
					<tr>
						<th width="50" class="affiliate-programs-sortable" data-column-index="0" data-sort-type="number">ID <span class="affiliate-programs-sort-indicator"></span></th>
						<th class="affiliate-programs-sortable" data-column-index="1" data-sort-type="text">Name <span class="affiliate-programs-sort-indicator"></span></th>
						<th class="affiliate-programs-sortable" data-column-index="2" data-sort-type="text">URL <span class="affiliate-programs-sort-indicator"></span></th>
						<th width="90" class="affiliate-programs-sortable" data-column-index="3" data-sort-type="number">Paysites <span class="affiliate-programs-sort-indicator"></span></th>
						<th width="180" class="affiliate-programs-sortable" data-column-index="4" data-sort-type="number">Updated <span class="affiliate-programs-sort-indicator"></span></th>
						<th width="120">Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php if ($affiliateProgramsRows !== '') { ?>
						<?=$affiliateProgramsRows?>
					<?php } else { ?>
						<tr id="affiliate-programs-empty-row">
							<td colspan="6" class="affiliate-programs-empty">Affiliate programs пока нет.</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
	</div>

	<div style="clear:both;"></div>
</div>

<script type="text/javascript">
	(function () {
		var searchInput = document.getElementById('affiliate-programs-search');
		var table = document.getElementById('affiliate-programs-table');
		if (!searchInput || !table) {
			return;
		}

		var tbody = table.querySelector('tbody');
		var countBlock = document.getElementById('affiliate-programs-visible-count');
		var emptyRow = document.getElementById('affiliate-programs-empty-row');
		var headers = table.querySelectorAll('.affiliate-programs-sortable');
		var currentSort = {
			index: 3,
			direction: 'desc',
			type: 'number'
		};

		function getRows() {
			return Array.prototype.slice.call(tbody.querySelectorAll('tr.affiliate-program-row'));
		}

		function updateCount() {
			var visible = 0;
			getRows().forEach(function (row) {
				if (row.style.display !== 'none') {
					visible += 1;
				}
			});
			countBlock.textContent = visible;
			if (emptyRow) {
				emptyRow.style.display = visible === 0 ? '' : 'none';
			}
		}

		function filterRows() {
			var query = searchInput.value.toLowerCase().trim();
			getRows().forEach(function (row) {
				var filterText = row.getAttribute('data-filter') || row.getAttribute('data-name') || '';
				row.style.display = query === '' || filterText.indexOf(query) !== -1 ? '' : 'none';
			});
			updateCount();
		}

		function getCellSortValue(row, columnIndex) {
			var cell = row.cells[columnIndex];
			if (!cell) {
				return '';
			}
			return cell.getAttribute('data-sort') || cell.textContent || '';
		}

		function sortRows(columnIndex, sortType, direction) {
			var rows = getRows();
			rows.sort(function (a, b) {
				var aValue = getCellSortValue(a, columnIndex);
				var bValue = getCellSortValue(b, columnIndex);

				if (sortType === 'number') {
					aValue = parseFloat(aValue || '0');
					bValue = parseFloat(bValue || '0');
				} else {
					aValue = aValue.toLowerCase();
					bValue = bValue.toLowerCase();
				}

				if (aValue < bValue) {
					return direction === 'asc' ? -1 : 1;
				}
				if (aValue > bValue) {
					return direction === 'asc' ? 1 : -1;
				}
				return 0;
			});

			rows.forEach(function (row) {
				tbody.appendChild(row);
			});

			headers.forEach(function (header) {
				var indicator = header.querySelector('.affiliate-programs-sort-indicator');
				if (!indicator) {
					return;
				}
				if (parseInt(header.getAttribute('data-column-index'), 10) === columnIndex) {
					indicator.textContent = direction === 'asc' ? '▲' : '▼';
				} else {
					indicator.textContent = '';
				}
			});
		}

		headers.forEach(function (header) {
			header.addEventListener('click', function () {
				var columnIndex = parseInt(header.getAttribute('data-column-index'), 10);
				var sortType = header.getAttribute('data-sort-type') || 'text';
				var direction = 'asc';
				if (currentSort.index === columnIndex) {
					direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
				}
				currentSort = {
					index: columnIndex,
					direction: direction,
					type: sortType
				};
				sortRows(columnIndex, sortType, direction);
				filterRows();
			});
		});

		searchInput.addEventListener('input', filterRows);
		sortRows(currentSort.index, currentSort.type, currentSort.direction);
		filterRows();
	})();
</script>
