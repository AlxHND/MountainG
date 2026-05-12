			<script>


				function update_tag(tag_id) {
					var name = document.getElementById('category_name_'+tag_id).value;
					var folder_name = document.getElementById('category_folder_name_'+tag_id).value;
					var title = document.getElementById('category_title_'+tag_id).value;
					var description = document.getElementById('category_desc_'+tag_id).value;
					var keywords = document.getElementById('category_keywords_'+tag_id).value;
					var site_id = <?=$site_id?>;

					var tag_element_block = document.getElementById('tag_blck_'+tag_id);

					
					/*alert (name+':'+folder_name); */
					
					if(name && folder_name && tag_id && site_id) {
						var $jq = jQuery.noConflict();
					
						$jq.post("util/sites.update_site_tag.php", 
									{	name : name, folder_name : folder_name, 
										title : title, description : description, 
										keywords : keywords, tag_id : tag_id, 
										site_id : site_id}, 
								
								function(data){
									if ('success' in data) {
										tag_element_block.style.backgroundColor = "#e7ffec";
										return;
									} else if ('error' in data) {
										tag_element_block.style.backgroundColor = "#f88e8e";
							        	alert (data.error);
							        } else {
							        	tag_element_block.style.backgroundColor = "#f88e8e";
							        	alert ("Ошибка изменения тега ");
							        }
						});	
					}
					
				}
			</script>

			<hr>
<?php
	


		if($site_id) {

			$site = $default->SiteInformation($site_id);

			$cache_worker->server_initializeSiteTags($site_id, $site['redis_server']);

			$site_worker = new Tags($db->_db);
			$tags_list = $site_worker->getSiteGalleriesTagsList($site_id, $sort_by)
?>

		<div id="used_categories" style="margin-bottom: 150px;">
<?php
		
		if($tags_list && is_array($tags_list)) { 		
			// var_dump($tags_list);
			foreach($tags_list as $tag_item) { 
				// дефолтные данные для экстракта (в случае ошибки)

				$id = false;
				$tag_id = false;
				$site_id = false;
				$name = false;
				$folder_name = false;
				$description = false;
				$keywords = false;
				$title = false;
				$md5 = false;
				$gals_count = false;
				$video_count = false;
				$total_count = false;
				$pageviews = false;
				$likes = false;
				$added_on = false;
				$updated_on = false;

				extract($tag_item);

				?>
					<div id="tag_blck_<?=$tag_id?>" style="display: block; height: 96px; margin: 8px; padding: 9px; border: 1px solid #ccc;">
						<div style="float: left;">
							<input type="button" value="Update" onclick="update_tag(<?=$tag_id?>)">
						</div>
						<div style="float: right;">
							Name:<input size="10" id="category_name_<?=$tag_id?>" value="<?=$name?>">
							Folder name:<input size="10" id="category_folder_name_<?=$tag_id?>" value="<?=$folder_name ? $folder_name : folderNameFromTag($name);?>">
							Title: <input size="60" id="category_title_<?=$tag_id?>" value="<?=$title ? $title : ucwords($name);?>"><hr>
							Desc:<input size="106" id="category_desc_<?=$tag_id?>" value="<?=$description ? $description : ucwords($name);?>"><br>
							Keys:<input size="106" id="category_keywords_<?=$tag_id?>" value="<?=$keywords ? $keywords : $name;?>">
						</div>
						
						<div style="clear: both;"></div>
					</div>
<?php		
			} 
		} else {
			echo "No used categories found!";
		}
?>		
		</div>
<?php
	}
?>				