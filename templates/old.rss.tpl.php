<?php $date = getdate(); echo "<?xml version='1.0'?>";?>
<rss xmlns:streamrotator='http://streamscripts.com/rss/1.0/' version='2.0'>
<channel>
<title>Stream Rotator Export RSS</title>
<lastBuildDate><?php echo $date['weekday'].", ".$date['mday'] ." ".$date['month'] ." ".$date['year']; ?></lastBuildDate>
<generator>Stream Rotator 1.008 beta 7</generator>
<?php
	foreach ($galleries as $id => $gallery) {
		if ($tags = $rss->selectTags($id)) {
			if ($thumbs = $rss->selectRssThumbs($id)) {
				if ($gallery['type'] !== 'Pics') {
					$gallery['thumbSize']['width'] = 200;
					$gallery['thumbSize']['height'] = 150;
				}
			
?>
			<item>
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
					echo "<![CDATA[<a href=".$gallery['url'].">";
					foreach ($thumbs as $imageId => $image) {
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
								if ($_GET['thsize'] == 'small') $thumbImg = "<img src='".HOSTING. "/thumbs/p/150/" .$folder. "/".$imageId.".jpg'>";
								elseif ($_GET['thsize'] == 'medium') $thumbImg = "<img src='".HOSTING. "/thumbs/p/180/" .$folder. "/".$imageId.".jpg'>";
								elseif ($_GET['thsize'] == 'big') $thumbImg = "<img src='".HOSTING. "/thumbs/p/240/" .$folder. "/".$imageId.".jpg'>";
								else $thumbImg = "<img src='".HOSTING. "/" .dirname($image). "/rss/tn-". $gallery['thumbSize']['width']. "x" .$gallery['thumbSize']['height'] . "-" .basename($image)."'>";

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
?>
				</description>
				<StreamRotatorDuration><?=$gallery['count']?></StreamRotatorDuration>
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
