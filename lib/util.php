<?php

if($_GET['fix_ok_to_cropped']) {
	$galleries = new Galleries($db->_db);
	$fixed = $galleries->fixOkGalleriesToCropped();

	echo "<h1>" . ($fixed === false ? "Ошибка fixOkGalleriesToCropped" : "Флаг кроп поднят для {$fixed} галер") . "</h1>";

	die;
}

$tags = new Tags($db->_db);

if (isset($_GET['rss'])) {
	$sites = new Sites($db->_db);
?>
<hr>
		<div id="sites_listing" style="height: auto; width: 900px; border: 1px dashed #000">
		</div>
<div style="clear: both;"></div>		
<hr>
		<div id="use_sites_listing" style="height: auto; width: 900px; border: 1px dashed #000">
		</div>
		<script type="text/javascript">

			// заменить ID блоков, чтобы удалять - добавлять сайты было удобнее ()

			// префиксы ID сайтов в списке: sites_site_, в используемых used_sites_site_

			Object.size = function(obj) {
			    var size = 0, key;
			    for (key in obj) {
			        if (obj.hasOwnProperty(key)) size++;
			    }
			    return size;
			};

			function callWhenLoaded(func) {
				if (window.addEventListener) {
					window.addEventListener("load", func, false);
				} else if (window.attachEvent) {
					window.attachEvent("onload", func);
				}
			}

			var tags_list = 
			[ <?php
				$tags_list = $tags->getAllTags(false, false, true);
				$tags_count = count($tags_list); $count = 0;
				foreach ($tags_list as $tag) {
					$count++;
					echo "'".$tag['name']."'";
					if ($count < $tags_count) echo ",";
				}
			?> ];
			var sites_list = 
			{
			<?php
				$sites_list = $sites->GetAll();
				$site_count = count($sites_list);
				$count = 0;
				foreach ($sites_list as $site_item) {
					$count++;
					echo "'".$site_item['id'] ."' : {\n
							'name' : '".$site_item['name']."',\n
							'niche' : '".$site_item['niche']."',\n
							'galleries_count' : '',\n
							'last_update' : '".$site_item['last_update']."',\n
							'type' : '',\n
							'tags' : [\n";
							$tags_count = count($site_item['tags_list']); $t_count = 0;
							foreach ($site_item['tags_list'] as $tag) {
								$t_count++;
								echo "'".$tag."'";
								if ($t_count < $tags_count) echo ",";
							}

					echo "	 		 ],\n";
					echo "	'no_tags' : [\n";

					echo "			 ],\n
							'set_tag' : [],
							'set_no_tag' : []
						}"; if ($count < $site_count) echo ",";	
					
					}
			?>
			};
/*				'1' : { 
					'name' : 'mpxgirls.com',
					'niche' : 'Straight',
					'galleries_count' : '',
					'last_update' : '',
					'type' : 'pics',
					'tags' : [
								'teens'
							 ],
					'no_tags' : [
								'fat', 'chubby'
							 ],
					'set_tag' : [],
					'set_no_tag' : []
					},
				'2' : { 
					'name' : 'girlsoftcore.com',
					'niche' : 'Straight',
					'galleries_count' : '',
					'last_update' : '',
					'type' : 'pics',
					'tags' : [
								'teens', 'erotic'
							 ],
					'no_tags' : [
								'fat', 'chubby', 'hardcore', 'groupsex'
							 ],
					'set_tag' : [],
					'set_no_tag' : []
					},
				'3' : { 
					'name' : 'matureporn43.com',
					'niche' : 'Straight',
					'galleries_count' : '',
					'last_update' : '',
					'type' : 'pics',
					'tags' : [
								'mature'
							 ],
					'no_tags' : [],
					'set_tag' : [],
					'set_no_tag' : []
					}
*/					



			var all_sites_num = Object.size(sites_list);

			var used_sites_list = {};
			var one_site_element_height = 25;
			var all_sites_count = one_site_element_height;
			var used_sites_count = 0;
			var all_sites_block = document.getElementById('sites_listing');
			var used_sites_block = document.getElementById('use_sites_listing');
			var rss_password = "sqg2fdqkjHgdlo";
			var rss_domain = "xratedtravels.com";
			var rss_count = 200;

			function add_new_tag(site_id, tag, element_id) {
				var element = document.getElementById("use_tags_"+site_id);
				if (tag != 0 && set_tag_for_site(site_id, tag)) {
					var newdiv = document.createElement('div');
					newdiv.setAttribute("id","site_"+site_id+"_tag_"+tag);
					newdiv.setAttribute("class","tag");
					newdiv.innerHTML = "<div style=\"font-size: 18px; margin-top: 5px; margin-right: 4px; margin-left: 4px; width: auto; height: auto; float: left;\">\
					"+tag+"\
					</div>\
					<div style=\"margin-bottom: 5px; width: auto; height: auto; float: left;\">\
					<img src=\"images/button_red_minus.png\" border=0 on"+"click=\"remove_tag("+site_id+",'"+tag+"');\" />\
					</div>";
					element.appendChild(newdiv,element);
					//element.style.height = parseInt(element.style.height) + 15+"px";
					draw_rss_link(site_id);
			    }
			}

			function add_new_no_tag(site_id, tag, element_id) {
				//alert("Adding no_tag:"+tag+" for site:"+site_id+", ElementId: 'use_no_tags_"+site_id+"'")
				var element = document.getElementById("use_no_tags_"+site_id);
				if (tag != 0 && set_notag_for_site(site_id, tag)) {
					var newdiv = document.createElement('div');
					newdiv.setAttribute("id","site_"+site_id+"_no_tag_"+tag);
					newdiv.setAttribute("class","tag");
					newdiv.innerHTML = "<div style=\"font-size: 18px; margin-top: 5px; margin-right: 4px; margin-left: 4px; width: auto; height: auto; float: left;\">\
					"+tag+"\
					</div>\
					<div style=\"margin-bottom: 5px; width: auto; height: auto; float: left;\">\
					<img src=\"images/button_red_minus.png\" border=0 on"+"click=\"remove_no_tag("+site_id+",'"+tag+"');\" />\
					</div>";
					element.appendChild(newdiv,element);
					//element.style.height = parseInt(element.style.height) + 15+"px";
					draw_rss_link(site_id);
			    }
			}			

			function remove_tag (site_id, tag) {
				var search_tag_array = 'tags';
			    if (site_id && tag) {
			      var element = document.getElementById('site_'+site_id+'_tag_'+tag);
		          element.parentNode.removeChild(element);
		          if (sites_list[site_id][search_tag_array].indexOf(tag) === -1 // проверка изначальных настроек сайта (tags|no_tags)
					&& sites_list[site_id]['set_tag'].indexOf(tag) !== -1)
					{
						sites_list[site_id]['set_tag'].splice(sites_list[site_id]['set_tag'].indexOf(tag),1);
						draw_rss_link(site_id);
					}
			    } else alert ("Ошибка входящих данных");
			}

			function remove_no_tag (site_id, tag) {
				var search_tag_array = 'no_tags';
			    if (site_id && tag) {
			      var element = document.getElementById('site_'+site_id+'_no_tag_'+tag);
		          element.parentNode.removeChild(element);
		          if (sites_list[site_id][search_tag_array].indexOf(tag) === -1 // проверка изначальных настроек сайта (tags|no_tags)
					&& sites_list[site_id]['set_no_tag'].indexOf(tag) !== -1)
					{
						sites_list[site_id]['set_no_tag'].splice(sites_list[site_id]['set_no_tag'].indexOf(tag),1);
						draw_rss_link(site_id);
					}
					// alert("Удалено с:"+site_id+", тег "+tag);
			    } else alert ("Ошибка входящих данных");
			}			


			function draw_tags_by_temlate(template) 
			{
				var string ="";
				for (var tag in tags_list) {
					var tmp_string = template.replace("#TAG#", tags_list[tag]);
					string += tmp_string.replace("#TAG_ID#", tags_list[tag]);
				}
				return string;
			}

			function draw_all_sites_list() {
				var list_type = 'sites';
				if (all_sites_block) {
					for (var site in sites_list) {
						var node = draw_node(site, list_type);
						all_sites_block.appendChild(node);

					}
				}
			}

			function rss_link_node() {
				var link_field = document.createElement("div");
				link_field.style.width = "100%";
				link_field.style.display = "block";
				link_field.style.margin = "5px;";
				return link_field;
			}

			function draw_rss_link(site_id) {
				var rss_tags = "";
				var rss_no_tags = "";
				var rss_tag_counter = 0;
				var rss_no_tag_counter = 0;
				var link = "http://"+rss_domain+"/rssfeeder.php?pwd="+rss_password+"&site="+site_id;
				for(var tag in sites_list[site_id]["set_tag"]) {
					if (sites_list[site_id]["set_tag"][tag] != 'undefined') {
						if (rss_tag_counter > 0) rss_tags += "|";
						rss_tags += sites_list[site_id]["set_tag"][tag];
						rss_tag_counter++;
					}
				}
				for(var tag in sites_list[site_id]["set_no_tag"]) {
					if (sites_list[site_id]["set_no_tag"][tag] != 'undefined') {
						if (rss_no_tag_counter > 0) rss_no_tags += "|";
						rss_no_tags += sites_list[site_id]["set_no_tag"][tag];
						rss_no_tag_counter++;
					}
				}				
				if (rss_tags != "") link += "&niche="+rss_tags;
				if (rss_no_tags != "") link += "&noniche="+rss_no_tags;
				var link_field = document.getElementById("rss_link_site_"+site_id);
				link_field.value = link;
				//alert(rss_no_tag_counter);
			}

			function draw_node (site_id, list_type) 
			{
				var node = document.createElement('div');
				node.id = list_type + "_site_" + site_id;
				node.style.width = "850px";
				node.style.height = "35px";
				node.style.padding = "5px";
				node.style.margin = "1px";
				node.style.fontSize = "20px";
				node.style.textAlign = "left";
				node.style.border = "solid 1px #666";

				var button = document.createElement("div");
				var button = document.createElement("div");
				button.style.cssFloat = "right";
				button.style.display = "inline-block";

				var no_tags = document.createElement("div");
				no_tags.setAttribute('id', "no_tags_"+site_id);

				var use_tags = document.createElement("div");
				use_tags.setAttribute('id', "use_tags_"+site_id);
				use_tags.style.cssFloat = "left";
				use_tags.style.display = "block";
				use_tags.style.backgroundColor = "green";
				use_tags.style.width = "100%";
				use_tags.style.height = "36px";
				use_tags.style.margin = "3px";

				var no_tags = document.createElement("div");
				no_tags.setAttribute('id', "use_no_tags_"+site_id);
				no_tags.style.cssFloat = "left";
				no_tags.style.display = "block";
				no_tags.style.backgroundColor = "red";
				no_tags.style.width = "100%";
				no_tags.style.height = "36px";
				no_tags.style.margin = "3px";

				node.innerHTML = sites_list[site_id]['name'];
				if (list_type == 'sites') {
					button.innerHTML = " <font color=green>V</font> ";
					button.setAttribute( "onClick", "javascript: use_site("+site_id+");");
				}
				else {
					node.style.height = "180px";
					node.appendChild(no_tags);
					node.appendChild(use_tags);
					node.innerHTML += "<div style='width: 100%; float: left;'>Использовать теги: <select id='select_tag_site_"+site_id+"' onchange=\"tag_value = get_option_value('select_tag_site_"+site_id+"'); add_new_tag("+site_id+", tag_value, '"+list_type+"_site_"+site_id+"')\">"+draw_tags_by_temlate("<option value='#TAG_ID#'>#TAG#</option>")+"</select></div>"
					node.innerHTML += "<div style='width: 100%; float: left;'>Не использовать теги: <select id='select_no_tag_site_"+site_id+"' onchange=\"no_tag_value = get_option_value('select_no_tag_site_"+site_id+"'); add_new_no_tag("+site_id+", no_tag_value, '"+list_type+"_site_"+site_id+"')\">"+draw_tags_by_temlate("<option value='#TAG_ID#'>#TAG#</option>")+"</select></div>"
					button.innerHTML = " <font color=red>X</font> ";
					button.setAttribute( "onClick", "javascript: unuse_site("+site_id+");");
					var link_field = rss_link_node();
					link_field.innerHTML = "<input id='rss_link_site_"+site_id+"' type='text' style='width: 100%' value='http://"+rss_domain+"/rssfeeder.php?pwd="+rss_password+"&amp;site="+site_id+"&amp;count="+rss_count+"'>";
					node.appendChild(link_field);
					
				}	
				node.appendChild(button);
				return node;
			}

			function get_option_value(elem_id) 
			{
				var elem_value = document.getElementById(elem_id).value
				return elem_value;
			}

			function remove_node(site_id, remove_from) 
			{
				node = document.getElementById(remove_from+"_site_"+site_id);
				while (node.firstChild) {
				    node.removeChild(node.firstChild);
				}
				node.parentNode.removeChild(node);
				if (remove_from == 'sites') all_sites_count--;
				else used_sites_count--;
			}
			
			function use_site(site_id) 
			{
				remove_node(site_id,'sites');
				used_sites_count++;
				var node = draw_node(site_id,'used_sites')
				used_sites_block.appendChild(node);

			}

			function unuse_site(site_id) 
			{
				remove_node(site_id,'used_sites');
				all_sites_count++;
				var node = draw_node(site_id,'sites')
				all_sites_block.appendChild(node);
			}

			function set_tag_for_site(site_id, tag) { return setTagToRss(site_id, tag, 'set_tag'); }

			function set_notag_for_site (site_id, tag) { return setTagToRss(site_id, tag, 'set_no_tag'); }

			//tag_type = set_tag|set_no_tag
			function setTagToRss(site_id, tag, tag_type) 
			{
				var result = false;
				if (tag_type == 'set_tag') { var search_tag_array = 'tags'; }
				else { var search_tag_array = 'no_tags';   }
				if (sites_list[site_id][search_tag_array].indexOf(tag) === -1 // проверка изначальных настроек сайта (tags|no_tags)
					&& sites_list[site_id]['set_tag'].indexOf(tag) === -1
					&& sites_list[site_id]['set_no_tag'].indexOf(tag) === -1) // проверка добавленных
				{
					result = true;
					sites_list[site_id][tag_type].push(tag);
					for (var i = 1; i <= all_sites_num; i++) { if (i == site_id) continue; } // current site skeeping
				} else alert(tag+' already in array');
				return result;
			}
			callWhenLoaded(draw_all_sites_list());
			// set_tag_for_site(1,'anal');
			// set_tag_for_site(1,'foo');
		</script>
<?php
} else {
	$sources = new Sources ($db->_db);
	$models = new CModels ($db->_db);
	
	$format = "#TAG_NAME#|#TAG_URL_NAME#<br>";
	$listing = $sources->formattedListing($format);
	$listing .= $models->formattedListing($format);
	$listing .= $tags->formattedListing($format);

	echo $listing;
}
?>