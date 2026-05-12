<?php
class Templates {
	var $_db;		
	function __construct($db_connect)
	{		
		$this->_db = $db_connect;
	}

	function getSite($id)
	{
		$id = (int)$id;
		$result = false;
		if ($this->_db && $id) {
			$rs = $this->_db->Execute('select * from `sites` where `site_id` = "'.$id.'"');
			$res = $rs->GetRows();
			//pr($res);
			if(!empty($res)) {
				$result = $res[0];
			}
		
		}
		return $result;
	}

	function getSiteTemplates($siteId)
	{
		$siteId = (int)$siteId;
		$result = false;
		if ($this->_db && $siteId) {
			$rs = $this->_db->Execute('select * from `templates` where `site_id` = "'.$siteId.'"');
			$res = $rs->GetRows();
			//pr($res);
			if(!empty($res)) {
				$result = $res[0];
			}
		
		}
		return $result;
	}

	function getAllTemplates()
	{
		$result = false;
		if ($this->_db) {
			$rs = $this->_db->Execute('select id from `templates`');
			$res = $rs->GetRows();
			//pr($res);
			if(!empty($res)) {
				$result = $res;
			}
		
		}
		return $result;
	}

	function getTemplate($id)
	{
		$id = (int)$id;
		$result = false;
		if ($this->_db && $id) {
			$rs = $this->_db->Execute('select * from `templates` where `id` = "'.$id.'"');
			$res = $rs->GetRows();
			//pr($res);
			if(!empty($res)) {
				$result = $res[0];
			}
		
		}
		return $result;
	}

	private function backupTemplate($id) {
		$result = false;
		if ($template = $this->getTemplate($id)) {
//			print_r($template);
			$changed_on = $template['changed_on'];
			$name = $template['id'];
			$text_template = $template['template'];
			$sub_template = $template['sub_template'];
//			echo $template['sub_template'];
			$sql = "INSERT INTO template_backups (template_id, changed_on, template, sub_template) VALUES ('".$id."', '".$changed_on."', '".$text_template."', '".$sub_template."')";
			if ( $this->_db->Execute($sql) === false) {
		        print 'error inserting: '.$this->_db->ErrorMsg().'<BR>';
			} else $result = $this->_db->Insert_ID();
		}
		return $result;

	}

	function rebuildTemplate ($id, $error = false) {
		$result = false;
		$tagsString = "";
		$modelsString = "";
		$imageString = "";

		if ($template = $this->getTemplate($id)) {
			$string = $template['template'];
			$siteId = $template['site_id'];
			$templateSite = $this->getSite($siteId);
			$siteName = "http://www.".$templateSite['site_name']."/";
			$subTemplate = htmlspecialchars_decode($template['sub_template'], ENT_QUOTES);
			$gal_id = htmlspecialchars('<?=$gallery->id;?>');
			$local_gal_id = htmlspecialchars('<?=$gallery->localId;?>');
			$title = htmlspecialchars('<?=$gallery->title;?>');
			$description = htmlspecialchars('<?=$gallery->desc?>');
			$paysiteName = htmlspecialchars('<?=$gallery->paysiteName?>');
			$paysiteLink = htmlspecialchars('<?=$gallery->paysiteLink?>');
			$paysiteId = htmlspecialchars('<?=$gallery->paysiteId?>');
			$contentCount = htmlspecialchars('<?=$gallery->contentNum?>');

			$videoUrl =  htmlspecialchars('<?=VIDEO_URL . $gallery->videoUrl?>');

			$subTemplate = preg_replace("/\"/", "\\\"", $subTemplate);
			$subTemplate = preg_replace("/\#IMAGE_NO\#/", '".$imageId."', $subTemplate);
			$subTemplate = preg_replace("/\#IMAGE\#/", '".$image."', $subTemplate);
			if (preg_match("/\#THUMB_SMALL\#/", $subTemplate)) {
				$subTemplate = preg_replace("/\#THUMB_SMALL\#/", '".$thumb."', $subTemplate);
				$thumbSize = "/thumbs/p/150/";
			} elseif (preg_match("/\#THUMB_MEDIUM\#/", $subTemplate)) {
				$subTemplate = preg_replace("/\#THUMB_MEDIUM\#/", '".$thumb."', $subTemplate);
				$thumbSize = "/thumbs/p/180/";
			} else {
				$thumbSize = "/thumbs/p/150/";
			}			

//			echo $subTemplate . "<br><br><br><br><br>";

			if (!$error) {
				if (preg_match_all("%^(.*?)\#NO_CONTENT_START\#%xs", $string, $matches) && preg_match_all("%\#NO_CONTENT_END\#(.*?)$%xs", $string, $matches1) && preg_match_all("%\#NO_CONTENT_START\#(.*?)\#NO_CONTENT_END\#%xs", $string, $matches2)) {
					$str1 = $matches[1][0];
					$str2 = $matches1[1][0];
					$str3 = $matches2[1][0];
					$noImageStart = htmlspecialchars('<?php if (!$gallery->images) { ?>');
					$noImageEnd = htmlspecialchars('<?php } ?>');
					$string = $str1 .$noImageStart.$str3.$noImageEnd. $str2;
				}

				if (preg_match_all("%\#CONTENT_START\#(.*?)\#CONTENT_END\#%xs", $string, $matches) && preg_match_all("%^(.*?)\#CONTENT_START\#%xs", $string, $matches1) && preg_match_all("%\#CONTENT_END\#(.*?)$%xs", $string, $matches2)) {
			//		var_dump($matches);
			//		var_dump($matches1);
			//		var_dump($matches2);
					$str1 = $matches[1][0];
					$str2 = $matches1[1][0];
					$str3 = $matches2[1][0];
					$imageStart = htmlspecialchars('<?php if ($gallery->images) { if ($gallery->desc == "") $gallery->desc = $gallery->title; ?>');
					$imageEnd = htmlspecialchars('<?php } ?>');
					$adLinking = "";
					if (preg_match_all("%{ads[\s]*after[\s]*([0-9]{1,2})[\s]*=[\s]*(.*?)}%xs", $str1, $adLinksMatches)) {
						$adCounter = 0;
//						var_dump ($adLinksMatches);
						foreach ($adLinksMatches[1] as $key => $value) {
							if (isset($adLinksMatches[2][$key])) {
								$ad = htmlspecialchars_decode($adLinksMatches[2][$key], ENT_QUOTES);
								$ad = preg_replace("/\"/", "\\\"", $ad);
								$ad = preg_replace("/\'/", "&#180;", $ad);
								$ad = preg_replace("/\#PAYSITE_NAME\#/", '\'.$gallery->paysiteName.\'', $ad);
								$ad = preg_replace("/\#PAYSITE_LINK\#/", '\'.$gallery->paysiteLink.\'', $ad);
								$ad = preg_replace("/\#PAYSITE_ID\#/", '\'.$gallery->paysiteId.\'', $ad);
								
								$adLinking .= '$adLinksMatches['.$value.'] = \''.$ad.'\'; ';

								$adCounter++;
							}
						}
						//var_export($adLinking);
					}

					$duration = htmlspecialchars('<?php $contentCount = $gallery->contentNum;
												if ($contentCount < 60) {
													$hours = "00";
													$minutes = "00";
													$seconds = $contentCount;
												} else {
													$hours = ((int)($contentCount / 3600));
													if ($hours == 0) $hours = "00";
													elseif ($hours < 10) $hours = "0" . $hours;
													$minutes = ((int)(($contentCount - $hour*3600)/60));
													if ($minutes == 0) $minutes = "00";
													elseif ($minutes < 10) $minutes = "0" . $minutes;
													$seconds = (int)($contentCount - $hour*3600 - $minutes*60);
													if ($seconds == 0) $seconds = "00";
													elseif ($seconds < 10) $seconds = "0" . $seconds;
												}
												echo $hours.":".$minutes.":".$seconds;?>',ENT_QUOTES);
					
					$imageString = htmlspecialchars('<?php $counter = 0;
									'.stripslashes($adLinking).'
									foreach ($gallery->images as $imageId => $image) {
										if ($imageId < 256000){
											$folderId = (int)ceil($imageId/1000);
											$folder = "1/".$folderId;
										} else {
											$mainFolder= (int)ceil($imageId/256000);
											$folderId = (int)ceil($imageId/1000);	
											$folder = $mainFolder."/".$folderId;
										}
										
										$thumbsFolder = "'.$thumbSize.'".$folder ."/";
										$thumb = $thumbsFolder . $imageId . ".jpg";
										if (isset($adLinksMatches) && array_key_exists($counter, $adLinksMatches) && count($gallery->images) !== $counter) {
											echo $adLinksMatches[$counter];
										}
										echo "\t\t\t\t\t'.$subTemplate.'\r\n";
										$counter++;
									} ?>',ENT_QUOTES);

					if (preg_match_all("%\#TAGS_START\#(.*?)\#TAGS_END\#%xs", $string, $matches)) {
						$tagIdString = '".$tagId."';
						$tagNameString = '".$tagName."';
						$tagUrlNameString = '".$tagUrl."';
//						'<a href=\"http://www.suckaboner.com/tags/'".$tag."'/'".$tagUrl."'\">'".$tagName."'</a>'

						$tagsStr1 = $matches[1][0];
						$tagsStr1 = htmlspecialchars_decode($tagsStr1, ENT_QUOTES);
						$tagsStr1 = preg_replace("/\"/", "\\\"", $tagsStr1);
						$tagsStr1 = preg_replace("/\#TAG_ID\#/", $tagIdString, $tagsStr1);
						$tagsStr1 = preg_replace("/\#TAG_NAME\#/", $tagNameString, $tagsStr1);
						$tagsStr1 = preg_replace("/\#TAG_NAME_URL\#/", $tagUrlNameString, $tagsStr1);
						$tagsString = htmlspecialchars('<?php
							if (is_array($gallery->tags) && count($gallery->tags) !== 0) {
								foreach ($gallery->tags as $tagId => $tag) {
									$tagUrl = $tag["url_name"];
									$tagName = $tag["name"];
									echo "'.$tagsStr1.'";
								}
							} ?>',ENT_QUOTES);
					}

					if (preg_match_all("%\#MODELS_START\#(.*?)\#MODELS_END\#%xs", $string, $matches)) {
						$modelIdString = '".$modelId."';
						$modelNameString = '".$modelName."';
						$modelUrlNameString = '".$modelUrl."';
						$modelImgString = '".$modelImage."';
//						'<a href=\"http://www.suckaboner.com/models/'".$modelId."'/'".$modelUrl."'\">'".$modelName."'</a>'

						$modelsStr1 = $matches[1][0];
						$modelsStr1 = htmlspecialchars_decode($modelsStr1, ENT_QUOTES);
						$modelsStr1 = preg_replace("/\"/", "\\\"", $modelsStr1);
						$modelsStr1 = preg_replace("/\#MODEL_ID\#/", $modelIdString, $modelsStr1);
						$modelsStr1 = preg_replace("/\#MODEL_NAME\#/", $modelNameString, $modelsStr1);
						$modelsStr1 = preg_replace("/\#MODEL_NAME_URL\#/", $modelUrlNameString, $modelsStr1);
						$modelsStr1 = preg_replace("/\#MODEL_IMG\#/", $modelImgString, $modelsStr1);
						$modelsString = htmlspecialchars('<?php
							if (is_array($gallery->models) && count($gallery->models) !== 0) {
								foreach ($gallery->models as $modelId => $model) {
									$modelUrl = $model["url_name"];
									$modelName = $model["name"];
									$modelImage = $model["image"];
									echo "'.$modelsStr1.'";
								}
							} else echo "Sorry, models\' info is not available" ?>',ENT_QUOTES);
					}

					if (preg_match_all("%\#RELATED_START\#(.*?)\#RELATED_END\#%xs", $string, $matches)) {
						//echo "Related!";
						$relatedIdString = '".$relatedId."';
						$relatedNameString = '".$relatedName."';
						$relatedUrlNameString = '".$relatedUrl."';
						$relatedImgString = '".$relatedImage."';
						$relatedAddedString = '".$relatedAdded."';
						$relatedPicsString = '".$relatedPics."';
						$relatedDurationString = '".$relatedDuration."';
						$relatedSourceString = '".$relatedSource."';
						$relatedTitleNumber = '".$relatedTitle_number."';
						//$relatedMaxGals = '".$relatedMaxGals."';
//						'<a href=\"http://www.suckaboner.com/models/'".$modelId."'/'".$modelUrl."'\">'".$modelName."'</a>'
						$relatedsStr1 = $matches[1][0];
						if (preg_match_all("/\#RELATED_GALLERIES_([0-9]{1,3})\#/", $relatedsStr1, $relatedGalleriesCount)) {
							$relatedsStr1 = preg_replace("/\#RELATED_GALLERIES_([0-9]{1,3})\#/", "", $relatedsStr1);

							$relatedCounterBreak = 'if ($counter >= '.intval($relatedGalleriesCount[1][0]).') break;';
						} else {
							//var_dump($relatedGalleriesCount);
							$relatedCounterBreak = 'if ($counter >= 12) break;';
						}
						$relatedsStr1 = htmlspecialchars_decode($relatedsStr1, ENT_QUOTES);
						$relatedsStr1 = preg_replace("/\"/", "\\\"", $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_ID\#/", $relatedIdString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_NAME\#/", $relatedNameString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_URL\#/", $relatedUrlNameString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_IMG\#/", $relatedImgString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_ADDED\#/", $relatedAddedString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_PICS\#/", $relatedPicsString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_DURATION\#/", $relatedDurationString, $relatedsStr1);
						$relatedsStr1 = preg_replace("/\#RELATED_SOURCE\#/", $relatedSourceString, $relatedsStr1);
						$relatedName_Addition = "";
						if (preg_match_all("/\#RELATED_NAME_([0-9]{1,3})\#/", $relatedsStr1, $matches)) {
							$title_number = intval($matches[1][0]);
							$relatedName_Addition = '$title_number = '.intval($title_number).';';
							$relatedsStr1 = preg_replace("/\#RELATED_NAME_([0-9]{1,3})\#/", '".$relatedTitle_number."', $relatedsStr1);
						}
						//$relatedsStr1 = preg_replace("/\#RELATED_MAX_GALS\#/", $relatedMaxGals, $relatedsStr1);
						//#RELATED_ADDED#
						//#RELATED_PICS#
						//echo $relatedCounterBreak;
						$relatedsString = htmlspecialchars('<?php
							if (is_array($gallery->related) && count($gallery->related) !== 0) {
								$counter = 0;
								'.$relatedName_Addition.'
								foreach ($gallery->related as $related) {
									$relatedUrl = $related["url"];
									$relatedName = $related["gal_title"];
									$relatedImage = $related["image"];
									$relatedSource = $related["source_name"];
									$relatedAdded = date("Y-m-d",$related["added"]);
									$relatedPics = $related["gal_content_count"];
									if (isset($title_number)) {
										if (strlen($relatedName) <= $title_number) $relatedTitle_number = $relatedName;
										else $relatedTitle_number = substr($relatedName, 0, $title_number). "...";
									} else $relatedTitle_number = $relatedName;
									$_contentCount = $relatedPics;
									if ($_contentCount < 60) {
										$hours = "00";
										$minutes = "00";
										$seconds = $_contentCount;
									} else {
										$hours = ((int)($_contentCount / 3600));
										if ($hours == 0) $hours = "00";
										elseif ($hours < 10) $hours = "0" . $hours;
										$minutes = ((int)(($_contentCount - $hour*3600)/60));
										if ($minutes == 0) $minutes = "00";
										elseif ($minutes < 10) $minutes = "0" . $minutes;
										$seconds = (int)($_contentCount - $hour*3600 - $minutes*60);
										if ($seconds == 0) $seconds = "00";
										elseif ($seconds < 10) $seconds = "0" . $seconds;
									}
									$relatedDuration = $hours.":".$minutes.":".$seconds;

									echo "'.$relatedsStr1.'";
									$counter++;
									'.$relatedCounterBreak.'
								}
							} else echo "Sorry, related pics not available" ?>',ENT_QUOTES);
						//echo $relatedsString;
						//var_dump($str1);
						$str1 = preg_replace("%\#RELATED_START\#(.*?)\#RELATED_END\#%xs", $relatedsString, $str1);
					}
				}

				$str1 = preg_replace("%\{images[\s]*begin\}(.*?)\{images[\s]*end\}%xs", $imageString, $str1);
				$str1 = preg_replace("%\#TAGS_START\#(.*?)\#TAGS_END\#%xs", $tagsString, $str1);
				$str1 = preg_replace("%\#MODELS_START\#(.*?)\#MODELS_END\#%xs", $modelsString, $str1);
				
				$addedTime = htmlspecialchars('<?php if (isset($gallery->localTimeAdded) && intval($gallery->localTimeAdded)) {
										echo date("Y-m-d",intval($gallery->localTimeAdded));
									}
							 ?>',ENT_QUOTES);

				$string = $str2 .$imageStart. $str1 .$imageEnd.$str3;
				$string = preg_replace("/\#TITLE\#/", $title, $string);
				$string = preg_replace("/\#GAL_ID\#/", $gal_id, $string);
				$string = preg_replace("/\#LOCAL_GAL_ID\#/", $local_gal_id, $string);
				$string = preg_replace("/\#DESCRIPTION\#/", $description, $string);
				$string = preg_replace("/\#PAYSITE_LINK\#/", $paysiteLink, $string);
				$string = preg_replace("/\#PAYSITE_NAME\#/", $paysiteName, $string);
				$string = preg_replace("/\#PAYSITE_ID\#/", $paysiteId, $string);
				$string = preg_replace("/\#VIDEO_URL\#/", $videoUrl, $string);
				$string = preg_replace("/\#DURATION\#/", $duration, $string);
				$string = preg_replace("/\#ADDED\#/", $addedTime, $string);
				if (preg_match("/\#EMBED_VIDEO\#/", $string)) {
					$embedUrl = htmlspecialchars('<?php if (isset($gallery->embedUrl) && $gallery->embedUrl) echo $gallery->embedUrl;?>');
					$string = preg_replace("/\#EMBED_VIDEO\#/", $embedUrl, $string);
				}
				if (preg_match_all("/\#TITLE_([0-9]{1,3})\#/", $string, $matches)) {
					//var_dump($matches);
					//echo $matches[1][0];
					$title_number = intval($matches[1][0]);
					$title_number = htmlspecialchars('<?php 
														  $title_number = '.intval($title_number).';
														  if (strlen($gallery->title) <= $title_number) echo $gallery->title;
										 				  else echo substr($gallery->title, 0, $title_number). "..."; ?>');
					$string = preg_replace("/\#TITLE_([0-9]{1,3})\#/", $title_number, $string);
				}
			

				//
				// Обработка инклюдов
				//

				if (preg_match_all("/\#INCLUDE_([a-zA-Z0-9.\/\-]{1,50})(_PARAMS_([a-z]{1,55})=([a-z]{1,50}))?\#/", $string, $matches)) {
					//var_dump($matches);
					$include_count = count($matches[0]);
					for ($i=0; $i < $include_count; $i++) {
						if (!preg_match("/\.\./", $matches[0][$i])) {
							$match_pattern = preg_replace("/\#/", "\\#", $matches[0][$i]);
							$match_pattern = preg_replace("/\//", "\\/", $match_pattern);
							$match_pattern = preg_replace("/\./", "\\.", $match_pattern);
//							var_dump($match_pattern);
							$include_filename = "";
							if (isset($matches[1][$i])) {
								$include_filename = $matches[1][$i];
								if (isset($matches[3][$i]) && isset($matches[4][$i]) && $matches[3][$i] !== "" && $matches[4][$i] !== "") {
									$param_string = "?" . $matches[3][$i] ."=" . $matches[4][$i];
								} else $param_string = "";
								if ($include_filename !== "") {

									$replace_filename = htmlspecialchars('<?php if($include_file_listing = @file_get_contents("'.$siteName.$include_filename . $param_string.'")) echo $include_file_listing; ?>',ENT_QUOTES);
								} else $replace_filename = "";

								//echo $replace_filename ."<br>";
							}
							$string = preg_replace("/".$match_pattern."/", $replace_filename, $string);
						}
					}
//					var_dump($string);
				}

				if (preg_match_all("/\#SPOT_([0-9]{1,3})(_PAYSITE_([0-9]{1,5}))?(_PAYSITE_CURRENT)?\#/", $string, $matches)) {
					// print_r($matches);
					foreach ($matches[0] as $key => $name) {
						if ($matches[3][$key]) $insertion ='&amp;amp;paysite='.$matches[3][$key];
						elseif ($matches[4][$key])  $insertion='&amp;amp;paysite='.$paysiteId;
						else $insertion = "";
						if ($matches[1][$key]) {
							$spotInsert = '&lt;script type=&quot;text/javascript&quot;&gt;&lt;!--//&lt;![CDATA[
var url = &#039;'.DELIVERY_SITE.'&#039;;
document.write (&quot;&lt;scr&quot;+&quot;ipt type=&#039;text/javascript&#039; src=&#039;&quot;+url);
document.write (&quot;?spot='.$matches[1][$key].'&amp;amp;site='.$siteId.$insertion.'&amp;amp;galid=&lt;?=$gallery->id;?&gt;&quot;);
 document.write (&quot;&#039;&gt;&lt;/scr&quot;+&quot;ipt&gt;&quot;);
//]]&gt;--&gt;&lt;/script&gt;';
						} else { $spotInsert = "";}
						$name = preg_replace("/\#/", "", $name);
						$string = preg_replace("/\#".$name."\#/", $spotInsert, $string);
					}
				}

				// если макинтош
				if (preg_match_all("%\#IF_MACINTOSH\#(.*?)\#END_IF_MACINTOSH\#%xs", $string, $matches)) {
					//echo "IF AVAIL<br>";
					$tempString = $string;
					$if_mac_start_String = htmlspecialchars('<?php if (isset($_SERVER["HTTP_USER_AGENT"]) && preg_match("/(macintosh|iphone|ipod|ipad)/im",$_SERVER["HTTP_USER_AGENT"])) { ?>',ENT_QUOTES);
					$if_mac_end_String = htmlspecialchars('<?php } ?>',ENT_QUOTES);
					$if_mac_else_String = htmlspecialchars('<?php } else { ?>',ENT_QUOTES);
					if (preg_match("%\#IF_MACINTOSH\#%xs", $tempString)) {
						$tempString = preg_replace("%\#IF_MACINTOSH\#%xs", $if_mac_start_String, $tempString);
						$tempString = preg_replace("%\#ELSE_MACINTOSH\#%xs", $if_mac_else_String, $tempString);
						if (!preg_match("%\#END_IF_MACINTOSH\#%xs", $tempString)) {
							echo "Нет закрывающего тега END_IF_MACINTOSH ,блок пропущен<br>";
						} else {
							$tempString = preg_replace("%\#END_IF_MACINTOSH\#%xs", $if_mac_end_String, $tempString);
							$string = $tempString;
							//echo "STRING UPDATED<br>";		
						}
					}
				} else {
					//echo "IF NOT AVAIL<br>";
				}

				$result = $string;
			//	$string = 	preg_replace("/\{images[\s]*begin\}[\s]*(.*)[\s]*\{images[\s]*end\}/xs", $imageString, $string);
			} else {
				$string = preg_replace("%\#CONTENT_START\#(.*?)\#IMAGES_END\#%xs", "", $string);
				if (preg_match_all("%^(.*?)\#NO_CONTENT_START\#%xs", $string, $matches) && preg_match_all("%\#NO_IMAGES_END\#(.*?)$%xs", $string, $matches1) && preg_match_all("%\#NO_CONTENT_START\#(.*?)\#NO_IMAGES_END\#%xs", $string, $matches2)) {
					$str1 = $matches[1][0];
					$str2 = $matches1[1][0];
					$str3 = $matches2[1][0];
					$string = $str1 . $str3. $str2;
					$result = $string;
				}
			}
		}
		return $result;

	}

	function addTemplate($siteId, $name, $mobile, $type, $template, $sub_template)
	{
		$result = false;
		if ($this->_db) {
			$changed_on = time();
			$name = clean_string($name);
			$siteId = (int)$siteId;
			$mobile = (int)$mobile;
			$type = (int)$type;
			$template = htmlspecialchars($template,ENT_QUOTES);
			$sub_template = htmlspecialchars($sub_template,ENT_QUOTES);
			//var_dump($template);
			$sql = "INSERT INTO templates (site_id, name, mobile, type, template, sub_template, changed_on) VALUES ('".$siteId."', '".$name."', '".$mobile."', '".$type."', '".$template."', '".$sub_template."' ,'".$changed_on."')";
			if ( $this->_db->Execute($sql) === false) {
		        print 'error inserting: '.$this->_db->ErrorMsg().'<BR>';
			} else $result = $this->_db->Insert_ID();

		}
		return $result;
	}

	function updateTemplate($id, $siteId, $name, $mobile, $type, $template, $sub_template)
	{
		$result = false;
		$id = (int)$id;
		$siteId = (int)$siteId;
		if ($this->_db && $id && $siteId && $this->backupTemplate($id)) {
			$changed_on = time();
			$name = clean_string($name);
			$mobile = (int)$mobile;
			$type = (int)$type;
			$template = htmlspecialchars($template,ENT_QUOTES);
			$sub_template = htmlspecialchars($sub_template,ENT_QUOTES);
			$sql = "UPDATE templates SET site_id = '".$siteId."', name = '".$name."', mobile = '".$mobile."', type = '".$type."', template = '".$template."', sub_template = '".$sub_template."', changed_on = '".$changed_on."' WHERE id = '".$id."'";
			if ( $this->_db->Execute($sql) === false) {
		        print 'error inserting: '.$this->_db->ErrorMsg().'<BR>';
			} else $result = true;

		}
		return $result;
	}
}
?>