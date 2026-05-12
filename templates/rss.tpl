<?php
	$date = getdate();
	echo "&lt;?xml version='1.0'?&gt;";
?>

<rss xmlns:streamrotator='http://streamscripts.com/rss/1.0/' version='2.0'>
<channel>
<title>Stream Rotator Export RSS</title>
<lastBuildDate><?php echo $date['weekday'].", ".$date['mday'] ." ".$date['month'] ." ".$date['year']; ?></lastBuildDate>
<generator>Stream Rotator 1.008 beta 7</generator>
<?php
	foreach ($galleries as $id => $gallery) {
		if ($tags = $rss->selectTags($id)) {
			if ($thumbs = $rss->selectRssThumbs($id)) {
			
?>
			<item>
				<StreamRotatorSponsor><?=$gallery['sponsor']?></StreamRotatorSponsor>
				<StreamRotatorPaysite><?=$gallery['paysite']?></StreamRotatorPaysite>
				<StreamRotatorInfo>
<?php
					foreach ($tags as $tagId => $tag) {
						echo $tag;
						if ($i < $count) echo ",";
						$i++;
					}
?>
				</StreamRotatorInfo>
				<pubDate><?=$gallery['date']?></pubDate>
				<title><?=$gallery['title']?></title>
				<link><?=$gallery['url']?></link>
				<description>
<?php
//					echo "<![CDATA[<a href=".$galleryUrl.">";
					foreach ($thumbs as $imageId => $image) {
						echo "<img src='".HOSTING. "/" .dirname($image). "/rss/tn-". $gallery['thumbSize']['width']. "x" .$gallery['thumbSize']['height'] . "-" .basename($image)."'>";
					}
					echo "</a>]]>";
?>
				</description>
				<StreamRotatorRZInfo>
					<?php 
						echo $gallery['thumbSize']['width'] ."x". $gallery['thumbSize']['height'] . $gallery['niche'] ."|". $gallery['thumbSize']['width']. "|" .$gallery['thumbSize']['height']. "|images|pics";
					?>
				</StreamRotatorRZInfo>
			</item>
			}
		}
<?php
	}
?>
</channel>
</rss>