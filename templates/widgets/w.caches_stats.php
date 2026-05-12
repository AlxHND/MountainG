							<div style="display: block-inline; margin: 10px; width: 520px; border: 1px solid #000; padding: 15px; float: left;">
<?php
								CachingServers::reset();

								while(CachingServers::next()) {

									$cache_server_id = CachingServers::currentID();
									$cache_server_name = CachingServers::currentName();
?>
									<br>
									<div id="server_check_block">
										Redis Сервер <b><?=$cache_server_id?></b> (<b><?=$cache_server_name?></b>)
										<div style="border-bottom: 1px dotted #ccc;">
											<div style="float: left;">Проверка коннекта REDIS</div>
											<strong id="cache_connect_block_<?=$cache_server_id?>" style="float: right;">Проверяю</strong><br />
										</div>
										<div style="border-bottom: 1px dotted #ccc;">
											<div style="float: left;">Проверка кеша тегов</div>
											<strong id="tags_count_block_<?=$cache_server_id?>" style="float: right;">Проверяю</strong><br />
										</div>
										<div style="border-bottom: 1px dotted #ccc;">
											<div style="float: left;">Проверка кеша моделей</div>
											<strong id="models_count_block_<?=$cache_server_id?>" style="float: right;">Проверяю</strong><br />
										</div>
										<div style="border-bottom: 1px dotted #ccc;">
											<div style="float: left;">Проверка кеша платников</div>
											<strong id="sources_count_block_<?=$cache_server_id?>" style="float: right;">Проверяю</strong><br />
										</div>
										<div style="border-bottom: 1px dotted #ccc;">
											<div style="float: left;">Проверка кеша всех &quot;ОК&quot; галер</div>
											<strong id="galleries_count_block_<?=$cache_server_id?>" style="float: right;">Проверяю</strong><br />							
										</div>
									</div>
									<script type="text/javascript">
										$(document).ready(function() { 
											check_cache_connect(<?=$cache_server_id?>);
											check_tags_cache(<?=$cache_server_id?>);
											check_models_cache(<?=$cache_server_id?>);
											check_sources_cache(<?=$cache_server_id?>);
											check_galleries_cache(<?=$cache_server_id?>);
										});
									</script>
		<?php
								}
		?>

							</div>