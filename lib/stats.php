<?php
if (isset($_GET['site_id'])) {
	$site_id = (int)$_GET['site_id'];
	if($site_id > 0) {
?>
<div style="float: left;">
<?php
	$cache_worker->getShortDailyStats($site_id);
	// $cache_worker->getLikesFullInfo($site_id);
	$cache_server_id = 0;
	// $stored_users_count = $cache_worker->getSiteUsersListCount($site_id, $cache_server_id);
	// echo $stored_users_count;
	// $some_users = $cache_worker->getSiteUsersList($site_id, $cache_server_id);
	// var_dump($some_users);

	// var_dump($cache_worker->getUserInfo($site_id, $cache_server_id, $some_users[6]));

	


?>
</div>
<?php
	} else {
		echo "<h1>Ошибка в ID сайта</h1>";
	}
} else {
?>
		<div style="float: left; width: 1400px;">
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;">ID</div>
			<div style="padding-top:5px;  display:block; width: 260px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;">Сайт</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;">Uniques 24hr</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;">Pageviews 24hr</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;">Uniques Total</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;">Pageviews Total</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;">Лайки сегодня</div>
			<div style="padding-top:5px;  display:block; width: 195px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;">Лайки сегодня Новая</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;">Лайки всего</div>
		</div>
		<div style="clear: both;"></div>
<?php
	$c_total_unique_users_today = 0;
	$c_qualified_pageviews_today = 0;
	$c_total_unique_users = 0;
	$c_qualified_pageviews = 0;
	$c_likes_added_today_new = 0;
	$c_likes_added = 0;

	$sites = $default->SitesGetAll();
	foreach ($sites as $site) {

		if($site['only_export_site'] == 1) {
			continue;
		}

		$result = $cache_worker->server_siteStats($site['id'],2013,9,20);
		$id = $site['id'];
		$name = $site['name']; 	

		$c_total_unique_users_today += $result['total_unique_users_today'];
		$c_qualified_pageviews_today += $result['qualified_pageviews_today'];
		$c_total_unique_users += $result['total_unique_users'];
		$c_qualified_pageviews += $result['qualified_pageviews'];
		$c_likes_added_today_new += $result['likes_added_today_new'];
		$c_likes_added += $result['likes_added'];
		

?>
		<div style="float: left; width: 1400px;">
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;"><?=$id?></div>
			<div style="padding-top:5px;  display:block; width: 260px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;"><?=$name?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;"><?=$result['total_unique_users_today']?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;"><?=$result['qualified_pageviews_today']?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;"><?=$result['total_unique_users']?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;"><?=$result['qualified_pageviews']?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;"><?=$result['likes_added_today']?></div>
			<div style="padding-top:5px;  display:block; width: 195px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;"><?=$result['likes_added_today_new']?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;"><?=$result['likes_added']?></div>			
		</div>
		<div style="clear: both;"></div>		
<?php		
	}
?>
		<div style="float: left; width: 1400px;">
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;">&nbsp;</div>
			<div style="padding-top:5px;  display:block; width: 260px; height:22px; overflow: hidden; background-color: #ccc; margin: 1px; float:left; font-weight: bold;">Total:</div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;"><?=$c_total_unique_users_today?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #bbb; margin: 1px; float:left; font-weight: bold;"><?=$c_qualified_pageviews_today?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;"><?=$c_total_unique_users?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #aaa; margin: 1px; float:left; font-weight: bold;"><?=$c_qualified_pageviews?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;">&nbsp;</div>
			<div style="padding-top:5px;  display:block; width: 195px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;"><?=$c_likes_added_today_new?></div>
			<div style="padding-top:5px;  display:block; width: 95px; height:22px; overflow: hidden; background-color: #999; margin: 1px; float:left; font-weight: bold;"><?=$c_likes_added?></div>			
		</div>
		<div style="clear: both;"></div>
<?php	
}
?>		