					<div style="margin: 40px; width: 1000px; border: 2px solid #000; padding: 15px;">
						Redis Сервер 0
						Проверка коннекта REDIS ...... <strong id="cache_connect_block">Проверяю</strong><br />
						Проверка кеша тегов ...... <strong id="tags_count_block">Проверяю</strong><br />
						Проверка кеша моделей ...... <strong id="models_count_block">Проверяю</strong><br />
						Проверка кеша платников ...... <strong id="sources_count_block">Проверяю</strong><br />
						Проверка кеша всех &quot;ОК&quot; галер ...... <strong id="galleries_count_block">Проверяю</strong><br />

						Redis Сервер 1
						Проверка коннекта REDIS ...... <strong id="cache_connect_block_1">Проверяю</strong><br />
						Проверка кеша тегов ...... <strong id="tags_count_block_1">Проверяю</strong><br />
						Проверка кеша моделей ...... <strong id="models_count_block_1">Проверяю</strong><br />
						Проверка кеша платников ...... <strong id="sources_count_block_1">Проверяю</strong><br />
						Проверка кеша всех &quot;ОК&quot; галер ...... <strong id="galleries_count_block_1">Проверяю</strong><br />						
					</div>

					<script type="text/javascript">
						$(document).ready(function() { 
								check_cache_connect();
								check_tags_cache();
								check_models_cache();
								check_sources_cache();
								check_galleries_cache ();
						});
					
					</script>
<?php


if (isset($_GET['fix'])) {
	if ($_GET['fix'] == 'models') {
		var_dump($cached_models_count = $cache_worker->initializeModels());
	}
	$gal = new Galleries($db->_db);
	$gals = $gal->getOKGalleries();
	$count = 0;
	foreach ($gals as $gal) {
		if (!$cache_worker->gallery_cached($gal)) {
			$cache_worker->cacheGallery($gal);
			echo "<a href='index.php?act=galleries&amp;galid=".$gal."' target=_blank'>".$gal."</a><br>";
			$count++;
		}
	}
	echo "<br><br>". $count;
}

?>