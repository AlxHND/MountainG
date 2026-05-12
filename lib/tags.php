<div class="menu" style="margin: 20px; display: block; width: 1200px; height: 25px; padding: 4px;">
	<div style="padding-right: 30px; float: right; display: block-inline;">&lt; <a href="index.php?act=tags&amp;query=add&amp;qrstring=<?=time()?>">Добавить тег</a> &gt;</div>
	<div style="padding-left: 30px; float: left; display: block-inline;">&lt; <a href="index.php?act=tags&amp;qrstring=<?=time()?>">Список</a> &gt;</div>
</div>
<?php	


	$tags = new Tags($db->_db);

  if (  (isset($_REQUEST['insert_tag']) || isset($_REQUEST['update_tag'])) 
        && isset($_REQUEST['name']) && isset($_REQUEST['category']) 
        && (isset($_REQUEST['Gay']) || isset($_REQUEST['Straight']) || isset($_REQUEST['Shemale']))
      ) {

      $main_tag_id  = ( isset($_REQUEST['main_tag_id']) && (int)$_REQUEST['main_tag_id'] > 0) ? (int)$_REQUEST['main_tag_id'] : 0;
      $niches       = "";
      $niches_array = array();
      $tag_approved = true;
      
      if(isset($_REQUEST['Gay'])) $niches_array[] = "Gay";
      if(isset($_REQUEST['Straight']))  $niches_array[] = "Straight";
      if(isset($_REQUEST['Shemale'])) $niches_array[] = "Shemale";

      $niches = implode(',', $niches_array);

      

      if ($niches != "") {
        if (isset($_REQUEST['update_tag']) && $_REQUEST['update_tag']) {
          if (isset($_REQUEST['tag_id'])) {
            if($tags->updateTag($_REQUEST['tag_id'], $_REQUEST['name'], $niches, $_REQUEST['category'], $main_tag_id)) {
              echo "Тег #".$_REQUEST['tag_id']." изменен успешно<br>";
              $cache_worker->server_cacheTag($_REQUEST['tag_id']);
            }
            else echo "Ошибка изменения тега #".$_REQUEST['tag_id']."<br>";
          } else echo "Ошибка добавления/апдейта тега, неверный ID<br>";
        } else {
          var_dump($_POST);
          $newTag = $tags->insertTag($_REQUEST['name'], $niches, $_REQUEST['category'], $tag_approved, $main_tag_id);
          var_dump($newTag);
          if ($newTag) {
            echo "Тег #".$newTag." добавлен.<br><br>";
            $cache_worker->server_cacheTag($newTag);
          }
          else echo "Ошибка добавления нового тега<br>";  
        }
        
      } else echo "Ошибка добавления/апдейта тега, niches пустая<br>";
	}
?>

<?php
	if ((isset($_GET['query']) && $_GET['query'] == 'add') || isset($_GET['id'])) {
    $name = "";
    $category = "";
    $main_tag_id = 0;
    if (isset($_GET['id'])) {
      $tag = $tags->getTag($_GET['id']);
      if ($tag) {
        $tag_id = $tag['id'];
        $name = $tag['name'];
        $category = $tag['category'];
        $tags_array = $tag['niche_array'];
        $main_tag_id = $tag['main_tag_id'];
      }
    }
?>
      <form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post" id="tagsform">
        <div align="center">
        <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
        <?php if (isset($tag_id)) { ?>
          <tr>
            <td bgcolor="#e4e4e4">ID: </td>
            <td bgcolor="#e4e4e4"><?=$tag_id?><input style="display: none;" name="tag_id" value="<?=$tag_id?>"></td>
          </tr>        	
        <?php } ?>
          <tr>
            <td bgcolor="#e4e4e4">Имя: </td>
            <td bgcolor="#e4e4e4"><input size="42" name="name" <?php if ($name) echo "value='".$name."'";?>></td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Тег уточняет следующий тег: </td>
            <td bgcolor="#e4e4e4">
              <select name="main_tag_id">
                <option value="0">No</option>
<?php
                  $tags_list = $tags->getAllTags();
                  foreach ($tags_list as $_tag_id => $_tag) { ?>
                    <option value="<?=$_tag_id?>"<?=($_tag_id == $main_tag_id) ? "selected='selected'" : NULL?>><?=$_tag['name']?></option>
<?php             } ?>
              </select>
            </td>
          </tr>

 
          <tr>
            <td bgcolor="#e4e4e4">Тип: </td>
            <td bgcolor="#e4e4e4">
              Категория: <input name="category" type="radio" value="Category"<?= (!$category || ($category && $category == 'Category')) ? " checked" : NULL;?>>
              Действие: <input name="category" type="radio" value="Action"<?= ($category == 'Action') ? " checked" : NULL;?>>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Ниши: </td>
            <td bgcolor="#e4e4e4">
                Gay: <input name="Gay" type="checkbox" <?php if(isset($tags_array['Gay'])) echo "checked";?>>
                Straight: <input name="Straight" type="checkbox" <?php if(isset($tags_array['Straight'])) echo "checked";?>>
                Shemale: <input name="Shemale" type="checkbox" <?php if(isset($tags_array['Shemale'])) echo "checked";?>>
            </td>
          </tr>
        </table>
        <input type="submit" value="<?php if (isset($_GET['id'])) { ?>Изменить<?php } else { ?>Добавить<?php } ?>" name="<?php if (isset($_GET['id'])) { ?>update_tag<?php } else { ?>insert_tag<?php } ?>" />
        </div>
      </form>
<?php
	} else {
    if(isset($_GET['query']) && $_GET['query'] == 'candidates') {

      $page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
      $candidates_per_page = isset($_GET['count']) ? (int)$_GET['count'] : 50;
      $sort_by_count = false;

      $candidates_list = $tags->getCandidatesList($candidates_per_page, $page, $sort_by_count);

      if($candidates_list) {
        $tags_list = $tags->getAllTagsSortedByName();
        $js_tags = "";
        foreach($tags_list as $_tag_id => $_tag) {
          $js_tags .= "<option value=\"".$_tag_id."\">".$_tag['name']."</option>";
        }
        foreach($candidates_list as $candidate) {

          $candidate_id = $candidate['id'];
          $candidate_gals_count = $candidate['candidate_gals_count'];

          $candidate_data = $tags->getCandidateById($candidate_id);
          if($candidate_data) {
            $id = false;
            $tag_name = false;
            $added_on = false;
            $tags_count = false;

            extract($candidate_data);
 ?>
          <div id="tag_candidate_<?=$candidate_id?>" style="display: block; border: 1px solid #666; margin: 5px; width: 1200px; height: 27px; 
                      padding: 3px; text-align: left;">
            <input style="width: 100px; float: left; margin-right: 14px;" type="button" value="Blacklist" onclick="candidate_tag_blacklist(<?=$candidate_id?>)">
            <input style="width: 100px; float: left; margin-right: 14px;" type="button" value="Ignore" onclick="candidate_tag_ignore(<?=$candidate_id?>)">
            
            <input style="width: 120px; float: right; margin-right: 14px;" type="button" value="Add As Source" onclick="open_approve_tag_div(<?=$candidate_id?>)">
            <input style="width: 120px; float: right; margin-right: 14px;" type="button" value="Add As Model" onclick="open_approve_model_div(<?=$candidate_id?>)">
            <input style="width: 120px; float: right; margin-right: 14px;" type="button" value="Add As Tag" onclick="open_approve_tag_div(<?=$candidate_id?>)">
            <div style="width: 130px; float: left; padding: 2px;">
              <strong style="font-size: 15px;"><?=$tag_name?></strong>
            </div>
            <div style="width: 40px; float: left; padding: 2px;">
              <strong style="font-size: 15px;"><?=$tags_count?></strong>
            </div>
            <div style="width: 300px; float: left; padding: 2px;">
              <input style="width: 60px; float: left; margin-right: 14px;" type="button" value="Add" onclick="candidate_tag_add_as_synonym(<?=$candidate_id?>)">
              <select id="<?=$candidate_id?>_add_as_synonym_of" name="add_as_synonym_of">
                  <option value="0">..как синоним</option>
                  <?= $js_tags ?>
              </select>
            </div>
            <div id="approve_candidate_<?=$candidate_id?>" style="width: 800px; background-color: #DDD; height: 30px; margin-left: 150px; padding-left: 3px; padding-top: 4px; float: right; display: none;">
              Ext. of:<select id="main_tag_id_<?=$candidate_id?>" name="main_tag_id">
                <option value="0">No</option>
                <?= $js_tags ?>
              </select>
              &nbsp;&nbsp;&nbsp;&nbsp;
              Category <input name="category" id="Category_<?=$candidate_id?>" type="radio" value="Category" checked>
              Action <input name="category" id="Action_<?=$candidate_id?>" type="radio" value="Action">
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;          
              Gay <input id="Gay_<?=$candidate_id?>" type="checkbox" checked>
              Straight <input id="Straight_<?=$candidate_id?>" type="checkbox">
              Shemale <input id="Shemale_<?=$candidate_id?>" type="checkbox">
              <input type="submit" value="Add Tag"  onclick="approve_tag_candidate(<?=$candidate_id?>)" />
            </div>
            <div id="approve_candidate_model_<?=$candidate_id?>" style="width: 800px; background-color: #DDD; height: 30px; margin-left: 150px; padding-left: 3px; padding-top: 4px; float: right; display: none;">
              &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              <input type="radio" name="sex" id="female_<?=$candidate_id?>" value="female" checked>Жен.
              <input type="radio" name="sex" id="male_<?=$candidate_id?>" value="male">Муж.
              <input type="radio" name="sex" id="shemale_<?=$candidate_id?>" value="shemale">Шмель
              <input type="submit" value="Add Model"  onclick="approve_model_candidate(<?=$candidate_id?>)" />
            </div>
          </div>
          <div style="clear: both;"></div>
 <?php           
          }
        }
?>
      <h1>
        <?php
        if($page >= 1) {
          ?>
          <a href="index.php?act=tags&amp;query=candidates&amp;page=<?=$page - 1?>">Previous page</a>
          <?php
        }
        ?>
        <a href="index.php?act=tags&amp;query=candidates&amp;page=<?=$page + 1?>">Next page</a>
      </h1>
<?php        
      } else {
?>
<h1>Список пуст</h1>
<?php        
      }
    } elseif(isset($_GET['query']) && $_GET['query'] == 'blacklist') {
      $blacklist = $tags->getBlacklist();
      if($blacklist) {
        foreach($blacklist as $b_word) {
            $id = false;
            $name = false;
            $added_on = false;
            extract($b_word);
 ?>
          <div style="display: block; border: 1px solid #666; margin: 5px; width: 200px; height: 27px; 
                      padding: 3px; text-align: left; float: left;">
            <div style="width: 130px; float: left; padding: 2px;">
              <strong style="font-size: 15px;"><?=$name?></strong>
            </div>
          </div>
 <?php           
        }
      } else {
?>
<h1>Список пуст</h1>
<?php        
      }
    } else {
?>
<div style="float: left; display: block; width: 100%; text-align: left; font-size: 12px; margin: 15px;">
  Всего тегов: <?=$tags_count = $tags->tagsCount(); ?>,
  Тегов в кэше: <strong id="tags_count_block"><?php 
                      $tags_cached = $cache_worker->tagsCount();
                      if ($tags_cached && $tags_cached == $tags_count) echo $tags_cached;
                      elseif (!$tags_cached) {
?>
                        Пусто
<?php                        
                      } else {
?>
                        Количество тегов в кэше не совпадает: <i><?=$tags_cached?></i>
<?php
                      } 
?></strong>
  <input type="button" value="Пересобрать кэш тегов" id="init_tags" onclick="init_tags();">
</div>
<div style="clear:both;"></div>
<form enctype="multipart/form-data" action="index.php?act=tags&amp;tags_listing=true" method="post" id="tagsform">
  <div align="center">
    <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
      <tr>
        <td>
          Формат листинга: <input name="list_format" value="#TAG_ID#|#TAG_NAME#|#TAG_URL_NAME#|#TAG_NICHES#|#TAG_CATEGORY#<br>" size="90">
        </td>
        <td>
          Gay: <input name="Gay" type="checkbox">
          Straight: <input name="Straight" type="checkbox">
          Shemale: <input name="Shemale" type="checkbox">
        </td>
        <td>
          <input type="submit" value="Показать" name="tag_list_format" />
        </td>
      </tr>
    </table>
  </div>
</form>
<?php
      if (isset($_REQUEST['tag_list_format']) && isset($_REQUEST['list_format'])) {
        $niches = "";
        $format = $_REQUEST['list_format'];
        if(isset($_REQUEST['Gay'])) $niches .= "Gay";
        if(isset($_REQUEST['Straight'])) {
          if($niches != "") $niches .=",";
          $niches .= "Straight";
         } 
        if(isset($_REQUEST['Shemale'])) {
          if($niches != "") $niches .=",";
          $niches .= "Shemale";
        }
        if ($niches == "") $niches = false;
        $tagsList = $tags->formattedListing($format, $niches);
        if ($tagsList) {
          echo $tagsList;
        } else echo "Ошибка";

      } else {
    		$tagsList = $tags->getAllTagsWithSynonyms();
    		$counter = 0;

    		if ($tagsList && is_array($tagsList)) {
          $tagsCount = round(count($tagsList)/2);
    			foreach ($tagsList as $id => $tag) {
            $counter++;
            if ($counter == 1 || $counter == $tagsCount) {
              if ($counter != 1) echo "</div>";
    ?>
            <div style="width: 600px; padding: 10px; margin: 15px; height: auto; background-color: #fff; display: block-inline; float: left; border: 2px #000 solid;">
    <?php        
            }			
    ?>
              <div style="margin:6px; padding: 5px; height: 18px; border: 1px #000 solid; display: block-inline;">
                <div style='float:left;'>
                  <?=$counter?> | 
                  <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=tags&amp;id=<?=$id?>">Edit tag</a> | 
                  id тега: <?=$id?>
                  Тег: <strong><?=$tag['name']?></strong> | 
                  Ниши: <?=$tag['niches']?>, 
                  Синонимы: <?= $tag['synonyms'] ? $tag['synonyms'] : "нет"?>
                </div>
              </div>
    <?php	
    			}
    		}
      }
    }
	}	

?>