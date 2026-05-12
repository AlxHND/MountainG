<?php
			var_dump($_REQUEST);
			$gal = new Galleries($db->_db);
			if (@$_REQUEST['grab-galleries'] || isset($_GET['galid'])) {
				if (isset($_GET['galid'])) {
					$galleries[0] = $_GET['galid'];
					$gal_id = $_GET['galid'];
					$gal->addToQuery($gal_id);
					echo "Галлерея #".$gal_id." добавлена в очередь<br>";
				} else {
					unset($_POST['grab-galleries']);
					foreach ($_POST as $gal_id) {
						$gal_id = intval($gal_id);
						if ($gal_id) {
							$gal->addToQuery($gal_id);	
							echo "Галлерея #".$gal_id." добавлена в очередь<br>";
						}

					}
				}
			} elseif (isset($_POST['delete-galleries'])) {
					unset($_POST['delete-galleries']);
					foreach ($_POST as $gal_id) {
						$gal_id = intval($gal_id);
						if ($gal_id) {
							if ($gal->deleteNewGallery($gal_id)) echo "Галлерея #".$gal_id." удалена<br>";
							else  echo "Галлерея #".$gal_id." не удалена - ошибка статуса. Статус не NEW и не TOREGRAB<br>";
						}
					}
			}

			$paysite_id = false;

			if (isset($_GET['paysite']) && intval($_GET['paysite'])) {
				$paysite_id = intval($_GET['paysite']);
				$paysiteSelect = "&paysite=" . $paysite_id;
			} else $paysiteSelect = "";
					//
			//	Граб из очереди TOGRAB
			//
			//


				if (isset($_REQUEST['gallery-quantity'])) $galsPerPage = intval($_REQUEST['gallery-quantity']);
				elseif (isset($_GET['items'])) $galsPerPage = intval($_GET['items']);
				else $galsPerPage = 20;

				if (isset($_GET['page'])) $page = intval($_GET['page']);
				else $page = 0;

				$start = get_time();
				if (isset($_GET['title']) && ($_GET['title'] == 'desc' || $_GET['title'] == 'asc')) {
					$sort_by = 'title';
					$sort_order = $_GET['title'];
					if ($_GET['title'] == 'desc') $querySortString = str_replace("title=desc", "title=asc", $_SERVER['QUERY_STRING']);
					else $querySortString = str_replace("title=asc", "title=desc", $_SERVER['QUERY_STRING']);
				} else {
					$sort_by = 'gal_id';
					$sort_order = 'asc';
					$querySortString = $_SERVER['QUERY_STRING'] . "&title=asc";
				}

				$querySortString .= $paysiteSelect;

				$start_1 = get_time();
				$new_gals_count = $gal->countGalleriesToGrab($paysite_id);
				$finish_1 = get_time();
				$exec_1 = $finish_1 - $start_1;
				// var_dump($exec_1);
				$numPages = ceil($new_gals_count / $galsPerPage);

				$start_1 = get_time();
				$rows = $gal->galleriesToGrab($page, $galsPerPage, $sort_by, $sort_order, $paysite_id);
				$finish_1 = get_time();
				$exec_1 = $finish_1 - $start_1;
				// var_dump($exec_1);

				$count = 1;			
?>
			<div style="float: right;">
				&nbsp;&nbsp;&nbsp;<a href="index.php?act=grabber&amp;log">Состояние граббера</a>
				&nbsp;&nbsp;&nbsp;|
				&nbsp;&nbsp;&nbsp;<a href="index.php?act=grabber&amp;clear">Сбросить граббер</a>
			</div>
			<div style="clear: both;"></div>
			<form name=selector enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post" style = "width: 1200px; margin-top:10px;">
				&nbsp;&nbsp;&nbsp;Ниша:&nbsp;
				<select name="niche" id="niche" onChange="searchGrabOptions(this.id,this.value);">
					<option value="all">All</option>
					<?php $default->AllNichesToString ("<option value=\"#NICHE#\">#NICHE#</option>"); ?>
				</select>
				&nbsp;&nbsp;&nbsp;Категория:&nbsp;
				<select name="category" id="category" onChange="searchGrabOptions(this.id,this.value);">
					<option value="all">All</option>
					<?php $default->AllTagsToString ("<option value=\"#TAG_ID#\">#TAG#</option>"); ?>
				</select>
				&nbsp;&nbsp;&nbsp;Платник:&nbsp;
				<select name="paysite" id="paysite" onChange="searchGrabOptions(this.id,this.value);">
					<option value="all">All</option>
					<?php 
					$start = get_time();
					echo $default->AllPaysitesToString ("<option value=\"#PAYSITE_ID#\">#PAYSITE# - апдейт: #LAST_UPDATE#, галер: #GALLERIES_COUNT# </option>", 'new'); 
					$finish = get_time();
					$exec_t = $finish - $start;
					// var_dump($exec_t);
					echo '<option>default/AllPaysitesToString Exec time:'.$exec_t.'</option>'
					?>
				</select>
				&nbsp;&nbsp;&nbsp;<select name="type" id="type" onChange="searchGrabOptions(this.id,this.value);">
					<option value="pics">Pics</option>
					<option value="video">Movies</option>

				</select>
				&nbsp;&nbsp;&nbsp;<select name="quant" id="quant" onChange="searchGrabOptions(this.id,this.value);">
					<option value="20">20</option>
					<option value="50">50</option>
					<option value="100">100</option>
					<option value="200">200</option>
				</select>
				<div id="searchResult" class="searchResult" style="float:right;"></div>
			</form>
			<div style="display: block; margin-top: 5px; text-align:center; font-weight: bold;">Галер к грабу: <?=$new_gals_count?></div>
			<div style="clear: both;"></div>

		<form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME']?>?act=grabber" method="post" style = "width: 1200px; margin-top:10px;">
			<table align="center">
			<tr><td></td><td>&nbsp;</td><td>URL</td><td><a href="<?=$_SERVER['SCRIPT_NAME'] . "?". $querySortString?>">Title</a></td><td>Niche</td><td>Category</td><td>Paysite</td><td>&nbsp;</td></tr>
<?php
				foreach ($rows as $row) {

					$galUrl = $row['gal_source']
?>
					<tr>
						<td>
							<input type="checkbox" name="<?=$row['gal_id']?>" checked id="<?=$row['gal_id']?>" value="<?=$row['gal_id']?>">
						</td>
						<td>
							<a href="<?=$_SERVER['SCRIPT_NAME'] . "?act=grabber&galid=" . $row['gal_id']?>" target="_blank">Grab</a>
						</td>
						<td>
							<div style="width: 360px; text-align: left; display: block; overflow: hidden; border: 1px #000 solid; height: 16px; "><a href="<?=$galUrl?>" class="link" target="_blank"><?=$galUrl?></a></div>
						</td>
						<td>
							<div style="width: 260px; text-align: left; display: block; overflow: hidden; border: 1px #000 solid; height: 16px; ">
								<?=$row["gal_title"]?>
							</div>
						</td>
						<td>
							<div style="width: 80px; text-align: left; display: block; overflow: hidden; border: 1px #000 solid; height: 16px; ">
								<?=$row['paysite_niche'];?>
							</div>							
						</td>
						<td>
							<div style="width: 90px; text-align: left; display: block; overflow: hidden; border: 1px #000 solid; height: 16px; ">
								<?=$row['tag_name'];?>
							</div>							
						</td>
						<td>
							<div style="width: 220px; text-align: left; display: block; overflow: hidden; border: 1px #000 solid; height: 16px; ">
								<?=$row['paysite_name'];?>
							</div>							
						</td>
						<td>
							<a href="<?=$_SERVER['SCRIPT_NAME'] . "?act=trash&galid=" . $row['gal_id'] . "&status=delete" ?>" class="link" target="_self">Delete</a>
						</td>
					</tr>

<?php
					$count++;
				}
				echo "</table>";
?>
			<br />
			<br />
			<input style=" float: right;" type="submit" value="Сграбить галеры" name="grab-galleries" id="grab-galleries" />
			<input style=" float: left;" type="submit" value="Удалить галеры" onclick="return confirm('Точно удалить галеры?');" name="delete-galleries" id="delete-galleries" />
				
			<div style="clear: both;"></div>
		</form>
		<br />
<?php
			for ($i = 1; $i < $numPages; $i++) {
				echo "<a style='margin:2px;' href='".$_SERVER['SCRIPT_NAME'] . "?act=grabber&page=" . $i . "&items=".$galsPerPage . $paysiteSelect ."'>". $i . "</a> ";
			}
			$finish = get_time();
			$exec = $finish - $start;
			// var_dump($exec);
?>