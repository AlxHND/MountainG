<?php 

function __getThumbFolderById(int $imageId) : string {
	$folderId = (int) ceil($imageId / 1000);
	$mainFolder = ($imageId < 256000) ? 1 : (int) ceil($imageId / 256000);
	return "$mainFolder/$folderId";	
}

$output_array = [];

$imagesWorker = new Images($db->_db);
$galleriesWorker = new Galleries($db->_db);

if ($rotator) {
	header('Content-type: application/json');
	$output = array();
	foreach ($galleries as $id => $gallery) {
		if ($tags = $rss->selectTags($id)) {
			if ($thumbs = $rss->selectRssThumbs($id)) {
				$output[] = array('id'=>$id,'source'=>$gallery['paysite_id'],'gallery' => $gallery, 'tags' => $tags, 'thumbs' => $thumbs, 'internal_source' => strtolower(SCRIPT_PRE));
			}
		}
		
	}
	echo json_encode($output);
} elseif(isset($smart_thumbs) && $smart_thumbs) {

	$original_image_path = false;

	foreach ($galleries as $gallery) {
		

		$id = $gallery['gal_id'];

		$url = ($gallery['hosted'] == 1) ? $gallery['url'] : $gallery['source'];
		$slug = ($digibasedLocalIdAsSlug) ? $gallery['digibasedLocalId'] : ($gallery['slug'] ?? '');
		$title = (isset($gallery['own_title']) && $gallery['own_title'] && $gallery['own_title'] != "" ) ? $gallery['title'] : $gallery['title'];
		$description = $gallery['description'] ?? '';

		if($original_images && $gallery['own_main_thumb']) {
			$image_array = explode(":", $gallery['own_main_thumb']);
			$imageId = $image_array[0] ?? null;
			$original_image_path = $image_array[1] ?? null;

		} elseif (isset($gallery['own_main_thumb']) && $gallery['own_main_thumb']) { 
			$imageId = $gallery['own_main_thumb']; 
		} else { 
			$imageId = $gallery['gal_thumb'];
		}

		$thumbs = [];

	

		if ($gallery['type'] == 'Pics') {

							if ($imageId < 256000){
								$folderId = (int)ceil($imageId/1000);
								$folder = "1/".$folderId;
							} else {
								$mainFolder= (int)ceil($imageId/256000);
								$folderId = (int)ceil($imageId/1000);	
								$folder = $mainFolder."/".$folderId;
							}

							if($original_images) {
								$thumbImg = HOSTING."/" .$original_image_path;
							} elseif (isset($_GET['thsize'])) {
								if ($_GET['thsize'] == 'small') {
									$thumbImg = HOSTING."/thumbs/p/150/" .$folder. "/".$imageId.".jpg";
									$width = 150;
									$height = 200;
								} elseif ($_GET['thsize'] == 'medium') {
									$thumbImg = HOSTING."/thumbs/p/180/" .$folder. "/".$imageId.".jpg";
									$width = 180;
									$height = 240;
								} elseif ($_GET['thsize'] == 'big') {
									$thumbImg = HOSTING."/thumbs/p/240/" .$folder. "/".$imageId.".jpg";
									$width = 240;
									$height = 320;
								} else {
									$thumbImg = HOSTING."/thumbs/p/180/" .$folder. "/".$imageId.".jpg";
									$width = 180;
									$height = 240;
								}

							} else $thumbImg = HOSTING."/thumbs/p/".$gallery['thumbSize']['width']."/" .$folder. "/".$imageId.".jpg";		
			} else {
							if($original_images) {
								$thumbImg = HOSTING."/" .$original_image_path;
							} else {

								if (isset($_GET['thsize'])) {
									if ($_GET['thsize'] == 'small') {
										$thumbImg = HOSTING."/thumbs/m/200/";
										$width = 200;
										$height = 150;
									}elseif ($_GET['thsize'] == 'medium') {
										$thumbImg = HOSTING."/thumbs/m/240/";
										$width = 240;
										$height = 180;
									} elseif ($_GET['thsize'] == 'big') {
										$thumbImg = HOSTING."/thumbs/m/320/";
										$width = 320;
										$height = 240;
									} else {
										$thumbImg = HOSTING."/thumbs/m/200/";
										$width = 200;
										$height = 150;
									}

								} else {
									$thumbImg = HOSTING."/thumbs/m/200/";
									$width = 200;
									$height = 150;
								}
								if ($imageId < 256000){
									$folderId = (int)ceil($imageId/1000);
									$folder = "1/".$folderId;
								} else {
									$mainFolder= (int)ceil($imageId/256000);
									$folderId = (int)ceil($imageId/1000);
									$folder = $mainFolder."/".$folderId;
								}
								$thumbImg .= $folder ."/".$imageId.".jpg";
							}
			}

		if(isset($_GET['json'])) {


			// var_dump($gallery); die;
			$tags 	 = $rss->selectTags($id);
			$models  = ($use_models) ? $rss->selectModels($id) : array();

			$images = $imagesWorker->getGalImages($id);


			$legacyThumbs = $imagesWorker->getGalImagesWitCropInfo($id);

			
			
			if($gallery['type'] == 'Movies') {
				$videoImages = [];
				$previewVideo = $galleriesWorker->getVideoPreviewRelativePath($id, true, 'mp4');

				foreach($images as $keyImageId => $imagePath) {
					$imageFile = "/".$gallery['paysite_id']."/".$id."/".$gallery['md5']."/".$keyImageId.".jpg";	
					$videoImages[$keyImageId] = $imageFile;

					$legacyThumbs[$keyImageId]['image_url'] = $imageFile;
				}
				
					$output_array[] = [
						'id' 		=> $id,
						'local_id'  => $siteUseLocalIds ? $gallery['localId'] : null,
						'niche'		=> strtolower($gallery['niche']),
						'url' 		=> $url,
						'slug' 		=> $slug,
						'video' 	=> $gallery['paysite_id'] .'/'.$id.'.mp4',
						'video_preview' => $previewVideo ? $previewVideo : null,
						'preview_video' => $previewVideo ? $previewVideo : null,
						'title' 	=> $title,
						'description' => $description,
					'image_id'	=> $imageId,
					'paysite'	=> $gallery['paysite'],
					'count'		=> $gallery['count'],
					'type'		=> $gallery['type'],
                    'pageviews'		=> $gallery['pageviews'] ?? 0,
                    'likes'		=> $gallery['likes'] ?? 0,
					'tags'		=> $tags,
					'models'	=> $models,
					'original_thumbs' => $thumbs,
					'image_url' => $thumbImg,
					'images' 	=> array_map(
										function($i_url) {
											return HOSTING.$i_url;
										}, 
										$videoImages
									) ,
					'legacy_thumbs' 	=> $legacyThumbs,
					'created_at' => !empty($gallery['added_on']) ? date('Y-m-d H:i:s', $gallery['added_on']) : date('Y-m-d H:i:s'),
				];
			} else {
				$output_array[] = [
					'id' 		=> $id,
					'local_id'  => $siteUseLocalIds ? $gallery['localId'] : null,
					'niche'		=> strtolower($gallery['niche']),
					'url' 		=> $url,
					'slug' 		=> $slug,
					'title' 	=> $title,
					'description' => $description ?? '',
					'image_id'	=> $imageId,
					'paysite'	=> $gallery['paysite'],
					'count'		=> $gallery['count'],
					'type'		=> $gallery['type'],
                    'pageviews'		=> $gallery['pageviews'] ?? 0,
                    'likes'		=> $gallery['likes'] ?? 0,
					'tags'		=> $tags,
					'models'	=> $models,
					'original_thumbs' => $thumbs,
					'image_url' => $thumbImg,
					'images' 	=> array_map(
										function($i_url) {
											return HOSTING.$i_url;
										}, 
										$images
									) ,
					'legacy_thumbs' 	=> $legacyThumbs,
					'created_at' => !empty($gallery['added_on']) ? date('Y-m-d H:i:s', $gallery['added_on']) : date('Y-m-d H:i:s'),
				];
			}


		} elseif (isset($_GET['advanced'])) {

			$tags 	 = $rss->selectTags($id);
			$models  = ($use_models) ? $rss->selectModels($id) : array();

			$output  = $id."|".$url."|".$title."|".$imageId."|".$gallery['paysite']."|".$gallery['count']."|".$gallery['type'];
			$output .= ($tags) ? "|".join(",", $tags) : "|";
			$output .= "|".strtolower($gallery['niche']);
			$output .= ($use_models) ? "|".join(",", $models) : "";
			
			if($original_images) {
				$output .= "|".$thumbImg;
			}

			$output .= "\n";
		} else {
			$output = $url."|".$gallery['title']."|".$thumbImg ."\n";
		}

		if(!isset($_GET['json'])) {
			echo $output;
		}
	}

	if(isset($_GET['json'])) {
		header('Content-type: application/json; charset=utf-8');
		echo json_encode($output_array);
	}

	exit();
} 

if($smart_thumbs == "xxx") {
$date = getdate(); echo "<?xml version='1.0' encoding=\"utf-8\"?>\n";
?><rss version="2.0">
    <channel>
        <title>MOUNTAIN RSS</title>
        <link>http://www.sexhound.com/</link>
        <description>My description of this feed</description>
        <language>en-us</language>
        <pubDate><?php echo $date['weekday'].", ".$date['mday'] ." ".$date['month'] ." ".$date['year']; ?></pubDate>
<?php

	

	foreach ($galleries as $id => $gallery) {
		if ($gallery['hosted'] == 1) $url = $gallery['url'];
		else $url = $gallery['source'];
		$imageId = $gallery['gal_thumb'];

		if ($gallery['type'] == 'Pics') {
			

							if ($imageId < 256000){
								$folderId = (int)ceil($imageId/1000);
								$folder = "1/".$folderId;
							} else {
								$mainFolder= (int)ceil($imageId/256000);
								$folderId = (int)ceil($imageId/1000);	
								$folder = $mainFolder."/".$folderId;
							}
							if (isset($_GET['thsize'])) {
								if ($_GET['thsize'] == 'small') {
									$thumbImg = HOSTING. "/thumbs/p/150/" .$folder. "/".$imageId.".jpg'";
									$width = 150;
									$height = 200;
								} elseif ($_GET['thsize'] == 'medium') {
									$thumbImg = HOSTING. "/thumbs/p/180/" .$folder. "/".$imageId.".jpg'";
									$width = 180;
									$height = 240;
								} elseif ($_GET['thsize'] == 'big') {
									$thumbImg = HOSTING. "/thumbs/p/240/" .$folder. "/".$imageId.".jpg'";
									$width = 240;
									$height = 320;
								} else {
									$thumbImg = HOSTING. "/thumbs/p/180/" .$folder. "/".$imageId.".jpg'";
									$width = 180;
									$height = 240;
								}

							} else $thumbImg = HOSTING. "/thumbs/p/".$gallery['thumbSize']['width']."/" .$folder. "/".$imageId.".jpg";		
			} else {
							if (isset($_GET['thsize'])) {
								if ($_GET['thsize'] == 'small') {
									$thumbImg = HOSTING . "/thumbs/m/200/";
									$width = 200;
									$height = 150;
								}elseif ($_GET['thsize'] == 'medium') {
									$thumbImg = HOSTING . "/thumbs/m/240/";
									$width = 240;
									$height = 180;
								} elseif ($_GET['thsize'] == 'big') {
									$thumbImg = HOSTING . "/thumbs/m/320/";
									$width = 320;
									$height = 240;
								} else {
									$thumbImg = HOSTING . "/thumbs/m/200/";
									$width = 200;
									$height = 150;
								}

							} else {
								$thumbImg = HOSTING . "/thumbs/m/200/";
								$width = 200;
								$height = 150;
							}
							if ($imageId < 256000){
								$folderId = (int)ceil($imageId/1000);
								$folder = "1/".$folderId;
							} else {
								$mainFolder= (int)ceil($imageId/256000);
								$folderId = (int)ceil($imageId/1000);
								$folder = $mainFolder."/".$folderId;
							}
							$thumbImg .= $folder ."/".$imageId.".jpg";
			}
?>    
<item>
<title><?=$gallery['title']?></title>
<link><?=$url?></link>
<description><![CDATA[<a href="<?=$url?>"><img src="<?=$thumbImg?>" width="<?=$width?>" height="<?=$height?>" alt="<?=$gallery['title']?>"><br><?=$gallery['title']?></a>]]></description>
<pubDate><?=$gallery['date']?></pubDate>
<guid><?=$url?></guid>
</item>
<?php
	}
?>
     </channel>
</rss>
<?php
} else {
	// var_dump($_GET);
// нормальный РСС
$date = getdate(); echo "<?xml version='1.0'?>";?>
<rss xmlns:streamrotator='http://streamscripts.com/rss/1.0/' version='2.0'>
<channel>
<title>Stream Rotator Export RSS</title>
<lastBuildDate><?php echo $date['weekday'].", ".$date['mday'] ." ".$date['month'] ." ".$date['year']; ?></lastBuildDate>
<generator>Stream Rotator 1.008 beta 7</generator>
<?php
	foreach ($galleries as $id => $gallery) {

		// var_dump($gallery);
		if ($tags = $rss->selectTags($id)) {
			if ($thumbs = $rss->selectRssThumbs($id)) {
				if ($gallery['type'] !== 'Pics') {
					$gallery['thumbSize']['width'] = 200;
					$gallery['thumbSize']['height'] = 150;
				}
?>
			<item>
<?php
				if ($rotator) {
					echo "<internal_source>".strtolower(SCRIPT_PRE)."</internal_source>\n";
					echo "<internal source_global_id>".$id."</internal source_global_id>\n";
						
				}
?>				
				<StreamRotatorSponsor><?=$gallery['sponsor']?></StreamRotatorSponsor>
				<StreamRotatorPaysite><?=$gallery['paysite']?></StreamRotatorPaysite>
				<StreamRotatorInfo>
<?php
					echo "\t\t\t\t\t";
					$count = count($tags);
					$i = 1;
					foreach ($tags as $tagId => $tag) {
						echo $tag;
						if ($i < $count) echo ",";
						$i++;
					}

					if(isset($use_models) && $use_models) {
						$models = $rss->selectModels($id);
						if($models) {
							$impl = implode(",", $models);

							if($impl) echo ",".strtolower($impl);
						}
						
					}
					echo "\r\n";
?>
				</StreamRotatorInfo>
				<pubDate><?=$gallery['date']?></pubDate>
				<title><?=$gallery['title']?></title>
<?php
				if ($gallery['hosted'] == 1) {
?>
					<link><?=$gallery['url']?></link>
<?php
				} else {
?>
					<link><?=$gallery['source']?></link>
<?php
				}
?>
				<description>
<?php
				if ($rotator) {
					echo "\t\t\t\t\t";
					echo $id.":";					
					$count = count($thumbs);
					$i = 1;
					foreach ($thumbs as $imageId => $image) {
						echo $imageId;
						if ($i < $count) echo ",";
						$i++;
					}
					echo "\r\n";					

				} else {
					echo "<![CDATA[<a href=".$gallery['url'].">";
					foreach ($thumbs as $imageId => $image) {
						if($original_images) {
							$thumbImg = "<img src='".HOSTING.$image."'>";
						} elseif ($gallery['type'] == 'Pics') {
							if ($imageId < 256000){
								$folderId = (int)ceil($imageId/1000);
								$folder = "1/".$folderId;
							} else {
								$mainFolder= (int)ceil($imageId/256000);
								$folderId = (int)ceil($imageId/1000);	
								$folder = $mainFolder."/".$folderId;
							}
							if (isset($_GET['thsize'])) {
								if ($_GET['thsize'] == 'small') $thumbImg = "<img src='".HOSTING. "/thumbs/p/150/" .$folder. "/".$imageId.".jpg'>";
								elseif ($_GET['thsize'] == 'medium') $thumbImg = "<img src='".HOSTING. "/thumbs/p/180/" .$folder. "/".$imageId.".jpg'>";
								elseif ($_GET['thsize'] == 'big') $thumbImg = "<img src='".HOSTING. "/thumbs/p/240/" .$folder. "/".$imageId.".jpg'>";
								else $thumbImg = "<img src='".HOSTING. "/thumbs/p/180/" .$folder. "/".$imageId.".jpg'>";

							} else $thumbImg = "<img src='".HOSTING. "/thumbs/p/".$gallery['thumbSize']['width']."/" .$folder. "/".$imageId.".jpg'>";
						} else {
							if (isset($_GET['thsize'])) {
								if ($_GET['thsize'] == 'small') $thumbImg = "<img src='".HOSTING . "/thumbs/m/200/";
								elseif ($_GET['thsize'] == 'medium') $thumbImg = "<img src='".HOSTING . "/thumbs/m/240/";
								elseif ($_GET['thsize'] == 'big') $thumbImg = "<img src='".HOSTING . "/thumbs/m/320/";
								else $thumbImg = "<img src='".HOSTING . "/thumbs/m/200/";

							} else $thumbImg = "<img src='".HOSTING . "/thumbs/m/200/";
							if ($imageId < 256000){
								$folderId = (int)ceil($imageId/1000);
								$folder = "1/".$folderId;
							} else {
								$mainFolder= (int)ceil($imageId/256000);
								$folderId = (int)ceil($imageId/1000);
								$folder = $mainFolder."/".$folderId;
							}
							$thumbImg .= $folder ."/".$imageId.".jpg'>";
						}
						echo $thumbImg;
					}
					echo "</a>]]>";
				}
?>
				</description>
<?php
				if ($gallery['type'] == 'Pics') {
?>				
					<StreamRotatorPics><?=$gallery['count']?></StreamRotatorPics>
<?php
				} else {
?>
					<StreamRotatorDuration><?=$gallery['count']?></StreamRotatorDuration>
<?php					
				}
?>				
				<StreamRotatorRZInfo><?php echo $gallery['thumbSize']['width'] ."x". $gallery['thumbSize']['height'] . $gallery['niche'] ."|". $gallery['thumbSize']['width']. "|" .$gallery['thumbSize']['height']. "|images|pics";?>
				</StreamRotatorRZInfo>
			</item>
<?php
			}
		}
	}
?>
</channel>
</rss>
<?php
}
?>
