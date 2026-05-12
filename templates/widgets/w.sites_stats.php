							<script type="text/javascript" src="https://www.google.com/jsapi"></script>
							<script type="text/javascript">
							    function OpenInNewTab(url) {
									  var win = window.open(url, '_blank');
									  win.focus();
								}
							      
								google.load("visualization", "1", {packages:["table"]});
							    google.setOnLoadCallback(drawTable);

							    function drawTable() {
							        var data = new google.visualization.DataTable();
							        data.addColumn('string', 'Домен');
							        data.addColumn('string', 'В очереди');
							        data.addColumn('string', 'Последний апдейт');
							        data.addColumn('string', 'Обновлений на сайте до');
							        data.addColumn('string', 'Searches to approve');
							        //data.addColumn('number', 'Salary');
							        //data.addColumn('boolean', 'Full Time Employee');
							        data.addRows([
							        <?php
							        $sites_list = $sites->GetAll();
							        $sites_count = count($sites_list);
							        $counter = 0;
							        foreach($sites_list as $sites_value) { 
							        	$counter++; 
										$site_updates_till = getSitesLastQueryDate($sites_value['id']); 
										?>
							          ["<?=$sites_value['name']?>",
							           "<?=getSitesQuery($sites_value['id'])?>",
									   "<?php 
									   		if ($sites_value['last_update'] == 0) echo 'Не известно';
											elseif ((time() - $sites_value['last_update']) >  (24*60*60)) echo date('d-m-Y', $sites_value['last_update']);
											elseif ((time() - $sites_value['last_update']) >  (2*24*60*60)) echo date('d-m-Y', $sites_value['last_update']);
											elseif ((time() - $sites_value['last_update']) >  (4*24*60*60)) echo date('d-m-Y', $sites_value['last_update']);
											else echo date('d-m-Y', $sites_value['last_update']);
										?>",
										"<?php 
											if ($site_updates_till == 0) echo 'Не известно';
											elseif ((time() - $site_updates_till) >  (24*60*60)) echo date('d-m-Y', $site_updates_till);
											elseif ((time() - $site_updates_till) >  (2*24*60*60)) echo date('d-m-Y', $site_updates_till);
											elseif ((time() - $site_updates_till) >  (4*24*60*60)) echo date('d-m-Y', $site_updates_till);
											else echo date('d-m-Y', $site_updates_till);
										?>",
										"<?php 
											$unapp_count = $cache_worker->unapprovedSearchesCount($sites_value['id']);
											if ($unapp_count) {
												echo '<a href=\'index.php?act=searches_approve&amp;site_id='.$sites_value['id'].'\'>'.(int)$unapp_count.'</a>';
											} else {
												echo '0';
											}
										?> "
										]
										<?php 
											if($counter < $sites_count) echo ",\n";
									} 
									?>
							        ]);

							        var table = new google.visualization.Table(document.getElementById('table_div'));

							        table.draw(data, {showRowNumber: true});
							    }
							</script>
							<div id="table_div" style="float: right; width: 50%;"></div>