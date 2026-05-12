			<div class="gallery">
				<table class="disclaim" cellpadding="2" cellspacing="2" border="0">
					<tr>
						<td bgcolor="#e4e4e4" rowspan="6" align="center">
							<img src="<?=$thumb; ?>">
							<br />
							<a href="<?=$_SERVER['SCRIPT_NAME']?>?act=galleries&galid=<?=$gallery['id'] ?>" target="_blank" style="text-align: center; font-size: 16px; font-weight: bold;">Edit gallery</a>
						</td>
						<td bgcolor="#e4e4e4">
							<div style="float: left;"><strong>Paysite: </strong><?=$gallery['paysite']['name'] ?></div>
							<div style="float: right;">
								<a href="<?=$_SERVER['SCRIPT_NAME'] . '?act=grabber&galid=' . $gallery['id']?>&regrab" target="_blank">
									Re-Grab
								</a>
								 | 
								<a href="<?=$_SERVER['SCRIPT_NAME'] . '?act=trash&galid=' . $gallery['id'] . '&status=delete' ?>" class="link" target="_self">
									Delete
								</a>
							</div>
						</td>
					</tr>
					<tr>
						<td bgcolor="#e4e4e4"><strong>URL: </strong><?=$gallery['source']?></td>
						<td> </td>
					</tr>
					<tr>
						<td bgcolor="#e4e4e4"><strong>Title: </strong><input size="42" value="<?php echo stripslashes($gallery['title'])?>" id="title<?=$gallery['contentCount']?>"></td>
						<td> </td>
					</tr>
					<tr>
						<td bgcolor="#e4e4e4" valign="center"><strong>Description: </strong>
							<textarea rows="3" cols="41" name="description<?php echo stripslashes($gallery['contentCount'])?>" id="description<?=$gallery['contentCount']?>"><?=$gallery['description']?></textarea>
						</td> 
						<td> </td>
					</tr>
					<tr>
						<td bgcolor="#e4e4e4"><strong>Movs/Pics: </strong><?=$gallery['contentCount']?></td> 
						<td> </td>
					</tr>
					<tr>
						<td bgcolor="#e4e4e4"><strong>Folder: </strong><?=$gallery['id']?></td>
						<td> </td>
					</tr>
				</table>
			</div>