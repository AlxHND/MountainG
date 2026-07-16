<?php

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
		<table cellpadding="4" cellspacing="1" width="100%" style="background:#d8d8d8;">
			<tr style="background:#efefef;">
				<th width="50">ID</th>
				<th>Name</th>
				<th>URL</th>
				<th width="90">Paysites</th>
				<th width="180">Updated</th>
				<th width="120">Действия</th>
			</tr>
			<?php if ($affiliatePrograms) { ?>
				<?php foreach ($affiliatePrograms as $program) { ?>
					<tr style="background:#fff;">
						<td align="center"><?=(int)$program['affiliate_program_id']?></td>
						<td>
							<strong><?=htmlspecialchars($program['affiliate_program_name'], ENT_QUOTES, 'UTF-8')?></strong>
							<?php if (!empty($program['affiliate_program_description'])) { ?>
								<div style="padding-top:4px; color:#666; font-size:11px;"><?=htmlspecialchars($program['affiliate_program_description'], ENT_QUOTES, 'UTF-8')?></div>
							<?php } ?>
						</td>
						<td style="word-break: break-all;"><?=htmlspecialchars($program['affiliate_program_url'], ENT_QUOTES, 'UTF-8')?></td>
						<td align="center"><?=(int)$program['paysites_count']?></td>
						<td align="center"><?=htmlspecialchars($program['updated_at'], ENT_QUOTES, 'UTF-8')?></td>
						<td align="center">
							<a href="index.php?act=affiliate_programs&amp;edit_program=<?=(int)$program['affiliate_program_id']?>">Edit</a>
							<?php if ((int)$program['paysites_count'] === 0) { ?>
								<form method="post" action="index.php?act=affiliate_programs" style="display:inline;" onsubmit="return confirm('Удалить affiliate program?');">
									<input type="hidden" name="affiliate_program_id" value="<?=(int)$program['affiliate_program_id']?>">
									<input type="submit" name="delete_affiliate_program" value="Delete" style="color:#900; background:none; border:none; cursor:pointer; text-decoration:underline;">
								</form>
							<?php } else { ?>
								<span style="color:#999;">In use</span>
							<?php } ?>
						</td>
					</tr>
				<?php } ?>
			<?php } else { ?>
				<tr style="background:#fff;">
					<td colspan="6" align="center">Affiliate programs пока нет.</td>
				</tr>
			<?php } ?>
		</table>
	</div>

	<div style="clear:both;"></div>
</div>
