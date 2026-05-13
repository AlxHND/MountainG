<?php


$usersPageAllowedIps = array('78.140.141.80', '5.9.148.109', '127.0.0.1');
if (defined('ALWAYS_ALLOWED_IP') && ALWAYS_ALLOWED_IP) {
  foreach (preg_split('/[\s,;|]+/', ALWAYS_ALLOWED_IP) as $alwaysAllowedIp) {
    if ($alwaysAllowedIp) {
      $usersPageAllowedIps[] = $alwaysAllowedIp;
    }
  }
}

$usersPageAllowed = false;
if (isset($userAuth) && is_object($userAuth) && method_exists($userAuth, 'isAdmin') && $userAuth->isAdmin()) {
  $usersPageAllowed = true;
} elseif (isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $usersPageAllowedIps, true)) {
  $usersPageAllowed = true;
}

if ($usersPageAllowed) {

  if (isset($_GET['uid'], $_GET['q'], $_GET['type']) && $_GET['q'] =='history' && $_GET['type'] == 'descs') {
    $writer_query = new WritersQuery();
    $letters_month = $writer_query->lettersDoneThisMonth($_GET['uid']);
    $galleries_month = $writer_query->galleriesDoneThisMonth($_GET['uid']);
    $words_month = $writer_query->wordsDoneThisMonth($_GET['uid']);
    $letters_last_month = $writer_query->lettersDoneLastMonth($_GET['uid']);
    $galleries_last_month = $writer_query->galleriesDoneLastMonth($_GET['uid']);
    $words_last_month = 0;
    if(!$letters_month) $letters_month = 0;
    if(!$galleries_month) $galleries_month = 0;
    if(!$words_month) $words_month = 0;
?>
    
    <h2>Galleries LM: <?=$galleries_last_month?>, Words: <?=$words_last_month?>, Letters: <?=$letters_last_month?></h2>
    <h2>Galleries: <?=$galleries_month?>, Words: <?=$words_month?>, Letters: <?=$letters_month?></h2>
<?php

  // не помню зачем этот блок. удалить?
  } else {
?>
<div class="menu" style="margin: 20px; display: block; width: 1200px; height: 25px; padding: 4px;">
	<div style="padding-right: 30px; float: right; display: block-inline;">&lt; <a href="index.php?act=users&amp;query=add&amp;qrstring=<?=time()?>">Добавить работника</a> &gt;</div>
	<div style="padding-left: 30px; float: left; display: block-inline;">&lt; <a href="index.php?act=users&amp;qrstring=<?=time()?>">Список</a> &gt;</div>
</div>
<?php	

  // блок апдейта, добавления
	$users = $userAuth;
	if ((isset($_REQUEST['insert_user']) || isset($_REQUEST['update_user'])) && isset($_REQUEST['name'], $_REQUEST['pass'],$_REQUEST['ip'],$_REQUEST['operations'],$_REQUEST['add_models'])) {
      if (isset($_REQUEST['update_user']) && isset($_REQUEST['id'])) {
        $newUser = $users->updateUser($_REQUEST['id'], $_REQUEST['name'], $_REQUEST['pass'], $_REQUEST['ip'], $_REQUEST['operations'], $_REQUEST['add_models'], $_REQUEST['language']);
      } 
			else {
        $newUser = $users->insertUser($_REQUEST['name'], $_REQUEST['pass'], $_REQUEST['ip'], $_REQUEST['operations'], $_REQUEST['add_models'], $_REQUEST['language']);
			  
        if ($newUser) echo "Пользователь #".$newUser['id']." добавлен.<br> Пароль сохранен в базе данных<br>";
			  else echo "Ошибка добавления нового пользователя<br>";
      }
	}
?>

<?php

  
  $name = "";
  $pass = "";
  $ip = "";
  $user_id = false;
  $operations = "";
  $add_models = false;
  $language = 'en';
  $added = 'Not added yet';
  $last_login = 'Never';
  $excluded_paysites = false;
  $sources = new Sources($db->_db);
  $excluded_paysites_list = $sources->getAllSourcesShort();

	if ((isset($_GET['query']) && $_GET['query'] == 'add') || isset($_GET['id'])) {

		

		if (isset($_GET['id'])) {
      $user_id = (int)$_GET['id'];
			$userInfo = $users->getUsers($user_id);
      
			if ($userInfo) {
        if (isset($userInfo[$user_id]['id'])) $user_id = $userInfo[$user_id]['id'];
				if (isset($userInfo[$user_id]['name'])) $name = $userInfo[$user_id]['name'];
        if (isset($userInfo[$user_id]['pass'])) $pass = $userInfo[$user_id]['pass'];
        if (isset($userInfo[$user_id]['ip'])) $ip = $userInfo[$user_id]['ip'];
        if (isset($userInfo[$user_id]['last_login'])) $last_login = $userInfo[$user_id]['last_login'];
        if (isset($userInfo[$user_id]['added'])) $added = $userInfo[$user_id]['added'];
        if (isset($userInfo[$user_id]['operations'])) $operations = $userInfo[$user_id]['operations'];
        if (isset($userInfo[$user_id]['add_models'])) $add_models = $userInfo[$user_id]['add_models'];
        if (isset($userInfo[$user_id]['language'])) $language = $userInfo[$user_id]['language'];

        $excluded_paysites = $users->getExcludedPaysites($user_id);
			} else {
        
      }

      $form_button = '<input type="submit" value="Изменить" name="update_user" />';
		} else {
      $form_button = '<input type="submit" value="Добавить" name="insert_user" />';
    }
?>
      <form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post" id="userform">
        <div align="center">
        <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
          <tr>
            <td bgcolor="#e4e4e4">ID: </td>
            <td bgcolor="#e4e4e4"><?=$user_id?></td>
          </tr>        	
          <tr>
            <td bgcolor="#e4e4e4">Имя: </td>
            <td bgcolor="#e4e4e4"><input size="42" name="name" <?php if($name) echo "value='".$name."'";?>></td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Added: </td>
            <td bgcolor="#e4e4e4"><?=$added?></td>
          </tr>        	
          <tr>
            <td bgcolor="#e4e4e4">Last login: </td>
            <td bgcolor="#e4e4e4"><?=$last_login?></td>
          </tr>        	
          <tr>
            <td bgcolor="#e4e4e4">Разрешенные операции: </td>
            <td bgcolor="#e4e4e4">
              <select name="operations">
                <option value="descs" <?php if($operations == 'descs') echo " selected='selected'"; ?>>Дески</option>
                <option value="crop" <?php if($operations == 'crop') echo " selected='selected'"; ?>>Кроп</option>
                <option value="tags"<?php if($operations == 'tags') echo " selected='selected'"; ?>>Теги</option>
                <option value="croptags"<?php if($operations == 'croptags') echo " selected='selected'"; ?>>Кроп и теги</option>
                <option value="admin"<?php if($operations == 'admin') echo " selected='selected'"; ?>>Адм.</option>
              </select>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Язык десков: </td>
            <td bgcolor="#e4e4e4">
              <select name="language">
                <option value="en" <?php if($language == 'en') echo " selected='selected'"; ?>>Английский</option>
                <option value="nl" <?php if($language == 'nl') echo " selected='selected'"; ?>>Голландский</option>
                <option value="ru"<?php if($language == 'ru') echo " selected='selected'"; ?>>Русский</option>
                <option value="fr"<?php if($language == 'fr') echo " selected='selected'"; ?>>Французский</option>
                <option value="de"<?php if($language == 'de') echo " selected='selected'"; ?>>Немецкий</option>
              </select>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Можно добавлять моделей?</td>
            <td bgcolor="#e4e4e4">
              <select name="add_models">
                <option value="allowed" <?php if($add_models == 'allowed') echo " selected='selected'"; ?>>Да</option>
                <option value="disallowed"<?php if($add_models == 'disallowed') echo " selected='selected'"; ?>>Нет</option>
              </select>
            </td>
          </tr>          
          <tr>
            <td bgcolor="#e4e4e4">Password: </td>
            <td bgcolor="#e4e4e4">
                <input size="42" name="pass" value="">
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">IP: </td>
            <td bgcolor="#e4e4e4">
				<textarea name="ip" rows="3" cols="42"><?php if ($ip) echo htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');?></textarea>
        <div style="font-size: 11px; color: #666;">Можно несколько IP через запятую, пробел или новую строку.</div>
            </td>
          </tr>
<?php     if($excluded_paysites_list) { ?>
            <tr>
              <td bgcolor="#e4e4e4">Исключаем платники: </td>
              <td bgcolor="#e4e4e4">
<?php
              if($excluded_paysites) {
                foreach ($excluded_paysites as $paysite_id) { ?>
                      <div id="user_exclude_paysite_<?=$paysite_id?>" class="tag">
                        <div style="font-size: 18px; margin-top: 5px; margin-right: 4px; margin-left: 4px; width: auto; height: auto; float: left;">
                          <?=$excluded_paysites_list[$paysite_id]['paysite_name']?>
                        </div>
                        <div style="margin-bottom: 5px; width: auto; height: auto; float: left;">
                          <img src="images/button_red_minus.png" border=0 onclick="remove_user_user_exclude_paysite(<?=$user_id?>,<?=$paysite_id?>);" />
                        </div>
                      </div>
                      <div style="clear: both"></div>
  <?php         }
              }
?>
              <div>
                <select id="add_user_exclude_paysite" name="add_user_exclude_paysite" style="float: left; font-size: 18px; height: 38px; margin: 5px; padding: 5px;">
                  <option value="0">No</option>
<?php             foreach ($excluded_paysites_list as $paysite_id => $paysite_info) { ?>
                    <option value="<?=$paysite_id?>"><?=$paysite_info['paysite_name']?></option>
<?php             } ?>
                </select>
                <div style="float: left; padding: 0; margin: 2px;  margin-top: 5px;display: block;"><img src="images/add_button_small.png" border=0 onclick="add_user_exclude_paysite(<?=$user_id?>);" /></div>
              </div>
              </td>
            </tr>

<?php     } ?>
        </table>
        <?=$form_button?>
        </div>
      </form>
<?php
	} elseif (isset($_GET['uid']) && intval($_GET['uid']) && $users->getUsers($_GET['uid']) && isset($_GET['q']) && $_GET['q'] == 'history') {
          include ('history.php');           
  } else {
		$userInfo = $users->getUsers();
		$counter = 0;
		if ($userInfo && is_array($userInfo)) {
      $date = getdate();
      $current_month = $date['mon'];
      $current_year = $date['year'];
      $current_day = $date['mday'];
			foreach ($userInfo as $id => $user) {
				$counter++;

        $crop_today = $users->dayHistory($id, $current_year,$current_month, $current_day, 'crop');
        if(is_array($crop_today['galleries'])) $crop_today = count($crop_today['galleries']);
        else $crop_today = 0;
        
        $tags_today = $users->dayHistory($id, $current_year,$current_month, $current_day, 'updates');
        
        if(is_array($tags_today['galleries'])) $tags_today = isset($tags_today['galleries']['galleries_approved']) ? $tags_today['galleries']['galleries_approved'] : 0;
        else $tags_today = 0;
?>
          <div style="margin:6px; padding: 5px; width: 1200px; min-height: 18px;  height:auto; border: 1px #000 solid; display: block;">
            <div style='float:left; height:18px; width: 100%; display: block; text-align: left;'>
                <?=$counter?> | 
                <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=users&amp;id=<?=$id?>">Edit user</a> | 
                id: <?=$id?>
                Имя: <?=$user['name']?> |
                Статус: <strong><?php if ($user['operations'] === 'admin') { ?>Админ<?php } else { ?>Не админ<?php } ?></strong> |
                IP: <strong><?=$user['ip']?></strong>
                Последний логин: <?=$user['last_login']?>
                Добавлен: <strong><?=date("Y-m-d, H:i",$user['added'])?></strong>
                Операции: <?=$user['operations']?> | 
                <?php
                if ($user['operations'] == "descs") {
                  $writer_query = new WritersQuery();

                  $letters_today = $writer_query->lettersDoneToday($id);
                  $galleries_today = $writer_query->galleriesDoneToday($id);
                  $words_today = $writer_query->wordsDoneToday($id);
                  $query_length = $writer_query->queryLength($user['language']);

                  if(!$letters_today) $letters_today = 0;
                  if(!$galleries_today) $galleries_today = 0;
                  if(!$words_today) $words_today = 0;
                  if(!$query_length) $query_length = 0;

                  
                  ?>
                  Сегодня обработано: <b><?="Galleries: ".$galleries_today.", Words: ".$words_today.", Letters:".$letters_today. ", In query: ".$query_length ?></b>
                  <?php
                } else {
                ?>
                  Сегодня обработано галер: <a href="index.php?act=users&amp;uid=<?=$id?>&amp;q=history&amp;type=updates&amp;year=<?=$current_year?>&amp;month=<?=$current_month?>&amp;day=<?=$current_day?>"><strong><?=$tags_today?></strong></a>
                  Сегодня скроплено: <a href="index.php?act=users&amp;uid=<?=$id?>&amp;q=history&amp;year=<?=$current_year?>&amp;month=<?=$current_month?>&amp;day=<?=$current_day?>"><strong><?=$crop_today?></strong></a>
                <?php
                }
                ?>                
                <a style="float: right;" href="<?=$_SERVER['SCRIPT_NAME']?>?act=users&amp;uid=<?=$id?>&amp;q=history<?php if ($user['operations'] == "descs") {?>&amp;type=descs<?php }?>">History</a>
            </div>
            <?php if ($user['current_gallery']) { ?>
              <div style="width: 100%; height:18px; float: left; text-align: left; display: block;">
                  Работает над: <a href="index.php?act=galleries&amp;galid=<?=$user['current_gallery']?>"><?=$user['current_gallery']?></a>, операция <?=$user['work_type']?>, время начала: <?= date("Y-m-d, H:i",$user['change_time'])?>
              </div>
            <?php } ?>
            <div style="clear: both;"></div>
          </div>
<?php				
			}
		}
	}	
  } // descs history end
} else {
  echo "<div style=\"margin:20px; padding:12px; width:900px; border:1px solid #c66; background:#fff4f4; color:#900; text-align:left;\">Недостаточно прав для просмотра списка пользователей.</div>";
}

?>
