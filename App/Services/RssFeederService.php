<?php

namespace App\Services;

class RssFeederService
{
	public static function run($scriptDir)
	{
		$scriptDir = rtrim((string)$scriptDir, '/');

		ini_set('display_errors', 1);
		error_reporting(E_ALL);

		$pwrd = defined('RSS_FEEDER_PASSWORD') ? (string) RSS_FEEDER_PASSWORD : '';
		$content_url = defined('RSS_FEEDER_CONTENT_URL') ? (string) RSS_FEEDER_CONTENT_URL : '';

		if (!isset($_GET['pwd']) || $_GET['pwd'] != $pwrd) {
			echo "Error!";
			die;
		}

		require_once($scriptDir . "/config/config.php");
		require_once($scriptDir . "/classes/Logger.php");
		require_once($scriptDir . "/classes/class.db_access.php");
		require_once($scriptDir . "/lib/functions.php");
		require_once($scriptDir . "/classes/rss.new.php");
		require_once($scriptDir . "/classes/class.images.php");

		if (!isset($db) || !is_object($db) || !isset($db->_db)) {
			$db = isset($GLOBALS['db']) && is_object($GLOBALS['db']) ? $GLOBALS['db'] : null;
		}

		if (!is_object($db) || !isset($db->_db)) {
			$db = new \db_access();
		}

		$GLOBALS['db'] = $db;
		$rss = new \SelectTools();
		$connection_m = \DB::get();

		$original_images = false;
		$excludeNiche = 0;
		$nicheDelim = (isset($_GET['delim']) && strlen($_GET['delim']) === 1) ? $_GET['delim'] : "|";

		$digibasedLocalIdAsSlug = (isset($_GET['use_as_slug']) && $_GET['use_as_slug'] == 'local_id') ? true : false;

		$count = (isset($_GET['count'])) ? (int)$_GET['count'] : 200;
		$type = (isset($_GET['type']) && in_array($_GET['type'], array('Movies', 'Pics'))) ? $_GET['type'] : 0;
		$niche = (isset($_GET['niche'])) ? explode($nicheDelim, mysqli_real_escape_string($connection_m, $_GET['niche'])) : 0;
		$sort = (isset($_GET['sort'])) ? true : false;
		$offset = (isset($_GET['offset'])) ? intval($_GET['offset']) : 0;
		$rotator = (isset($_GET['rotator']) && $_GET['rotator'] == 'true') ? true : false;
		$smart_thumbs = (isset($_GET['smart_thumbs']) && $_GET['smart_thumbs']) ? true : false;
		$deleted = (isset($_GET['deleted']) && $_GET['deleted']) ? true : false;

		$use_models = (isset($_GET['use_models'])) ? true : false;

		if (isset($_GET['paysite'])) {
			require_once($scriptDir . "/classes/class.sources.php");
			$paysitesWorker = new \Sources($db->_db);
			$paysiteInfo = $paysitesWorker->getSourceByName($_GET['paysite']);
			header('Content-type: application/json');
			echo json_encode((array)$paysiteInfo);

			exit;
		}

		if (isset($_GET['model_info'])) {
			require_once($scriptDir . "/classes/class.models.php");

			$modelService = new \CModels($db->_db);
			$modelService->find_models_by_string($_GET['model_info']);

			header('Content-type: application/json');

			try {
				$model = $modelService->findModelByNameOrFail($_GET['model_info']);
				echo json_encode(array(
					'success' => $model,
				));
			} catch (\Exception $e) {
				echo json_encode(array('error' => 'Модель не найдена'));
			}

			exit;
		}

		if (isset($_GET['sitemaps'])) {
			require_once($scriptDir . "/classes/class.galleries.php");
			require_once($scriptDir . "/classes/class.sitesgalleries.php");
			require_once($scriptDir . "/classes/class.sites.php");

			$sites_util = new \Sites($db->_db);
			if (isset($_GET['site_id'])) {
				$site_id = (int)$_GET['site_id'];
				$site = $sites_util->getSite($site_id);

				if ($site && is_array($site)) {
					$gallery_url_pattern = $site["sites_gallery_url"];
					$gallery_url_digi_base = $site["digit_base_for_id"];

					$sites_util = new \SitesGalleries();
					$galleries = $sites_util->getAllSiteGalleriesUrls($site_id);

					$output = array();
					$id = 0;
					$result = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
								<urlset
								      xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"
								      xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
								      xmlns:xhtml=\"http://www.w3.org/1999/xhtml\"
								      xsi:schemaLocation=\"
								            http://www.sitemaps.org/schemas/sitemap/0.9
								            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">";

					foreach ($galleries as $gallery) {
						$galley_local_id = $gallery["id"];
						$galley_global_id = $gallery["global_id"];
						$type = $gallery["gal_type"];
						$url_desc = $gallery["url_desc"];

						$output = str_replace("#TYPE#", $type, $gallery_url_pattern);

						if ($gallery_url_digi_base != 10) {
							$local_gal_id = base_convert($galley_local_id, 10, $gallery_url_digi_base);
							$global_gal_id = base_convert($galley_global_id, 10, $gallery_url_digi_base);
						} else {
							$local_gal_id = $galley_local_id;
							$global_gal_id = $galley_global_id;
						}

						$output = str_replace("#LOCALID#", $local_gal_id, $output);
						$output = str_replace("#ID#", $global_gal_id, $output);
						$output = str_replace("#GALNAME#", $url_desc, $output);

						$result .= "\t<url>\n";
						$result .= "\t\t<loc>" . $output . "</loc>\n";
						$result .= "\t\t<lastmod>" . date("Y-m-d", time()) . "T" . date("H:i:s", time()) . "+00:00</lastmod>\n";
						$result .= "\t\t<changefreq>never</changefreq>\n";
						$result .= "\t\t<priority>0.5000</priority>\n";
						$result .= "\t</url>\n";

						$id++;
					}

					$result .= "</urlset>";
					echo $result;
				}
			}
			return;
		} elseif (isset($_GET['kvs'])) {
			require_once($scriptDir . "/classes/class.galleries.php");

			$galleries = new \Galleries();

			$niche = isset($_GET['niche']) ? $_GET['niche'] : false;
			$count = isset($_GET['count']) ? $_GET['count'] : false;
			$page = isset($_GET['page']) ? $_GET['page'] : false;
			$order = isset($_GET['order']) ? $_GET['order'] : false;
			$category = isset($_GET['category']) ? $_GET['category'] : false;

			$xx = $galleries->getOkVideoGalleries($niche, $count, $page, $order, $category);
			if ($xx) {
				$galleries_ids = array();
				foreach ($xx as $gallery) {
					$galleries_ids[] = $gallery['id'];
				}
				$tags_list = $galleries->getGalsListTags($galleries_ids);
				foreach ($xx as $gallery) {
					$tags = isset($tags_list[$gallery['id']]) ? $tags_list[$gallery['id']] : "";
					echo "http://" . $content_url . $gallery['video_url'] . "|" . $gallery['title'] . "|" . $gallery['paysite'] . "|" . $tags . "<br>";
				}
			}
			die;
		} elseif (isset($_GET['site'])) {
			$site_id = (int)$_GET['site'];

			require_once($scriptDir . "/classes/class.sites.php");

			$sites_util = new \Sites($db->_db);
			$site = $sites_util->getSite($site_id);

			if (!$site || !$site['site_id']) {
				echo "Error occured! S.";
				die;
			}

			$siteUseLocalIds = $site['local_id_flag'] == 1 ? true : false;

			if (isset($_GET['all_model_info'])) {
				require_once($scriptDir . "/classes/class.sitesgalleries.php");
				require_once($scriptDir . "/classes/class.models.php");

				$resultArray = array();

				$galleries = new \SitesGalleries();
				$modelService = new \CModels($db->_db);

				$modelsArray = $galleries->getSiteModelsListings($_GET['all_model_info']);

				foreach ($modelsArray as $model) {
					$modelInfo = $modelService->findModelByIdOrFail($model['id']);
					$modelInfo['created_at'] = !empty($modelInfo['added_on']) ? date('Y-m-d H:i:s', $modelInfo['added_on']) : date('Y-m-d H:i:s');

					$resultArray[] = array(
						'info' => $modelInfo,
						'stats' => $model,
					);
				}

				header('Content-type: application/json');

				try {
					echo json_encode(array(
						'success' => true,
						$resultArray,
					));
				} catch (\Exception $e) {
					echo json_encode(array('error' => 'Модель не найдена'));
				}

				exit;
			}

			if (isset($_GET['noniche'])) {
				$excludeNiche = explode("|", $_GET['noniche']);
				for ($i = 0; $i < count($excludeNiche); $i++) {
					$excludeNiche[$i] = mysqli_real_escape_string($connection_m, $excludeNiche[$i]);
				}
			}

			if (isset($_GET['get_random_thumbs'])) {
				$rss->setThumbSelectMode('random');
			}

			if (isset($_GET['get_original_images'])) {
				$original_images = true;
				$rss->setSelectOriginalImageMode();
			}

			if ($deleted) {
				$galleries = $rss->getDeletedGalleries($site_id);
				echo "<?xml version='1.0' encoding=\"utf-8\"?>\n";
				?>
		<rss xmlns:streamrotator="http://streamscripts.com/rss/1.0/" version="2.0">
			<channel>
				<title>Stream Rotator Export RSS</title>
				<description>
					<![CDATA[Stream Rotator Content Manager]]>
				</description>
				<link>http://www.streamscripts.com</link>
				<lastBuildDate></lastBuildDate>
				<generator>Delete feed</generator>
				<?php
				if ($galleries) {
					foreach ($galleries as $gallery) {
				?>
						<deleteditem>
							<link><?= $gallery ?></link>
						</deleteditem>
				<?php
					}
				}
				?>
			</channel>
		</rss>
<?php
			} else {
				$cjtube_mode = (isset($_GET['cjtube_mode']) && $_GET['cjtube_mode'] == 'on') ? true : false;

				if (isset($_GET['thumb_select_mode'])) {
					$rss->setThumbSelectMode($_GET['thumb_select_mode']);
				}

				$galleries = ($cjtube_mode) ? $rss->getCJTubeGalleries($site_id, $count, $niche, $type, $excludeNiche, $sort, $offset, $smart_thumbs)
					: $rss->selectSiteGalleries($site_id, $count, $niche, $type, $excludeNiche, $sort, $offset, $smart_thumbs);

				if ($galleries) {
					include $scriptDir . "/templates/rss.tpl.php";
				}
			}
		} elseif (isset($_GET['nosite'])) {
			$rss = new \SelectTools();
			$galleries = $rss->all();

			if ($galleries) {
				include $scriptDir . "/templates/rss.tpl.php";
			}
		}
	}
}
