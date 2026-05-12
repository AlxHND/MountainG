<?php
	define ("IS_MOBILE", 1);
	define ("NO_MOBILE", 0);

	$spots = new CBannerSpot($db->_db);

  if (isset($_POST['addSpot']) || isset($_POST['updateSpot'])) {
//    var_dump($_POST);
    $name = $_POST['name'];
    $siteId = (int)$_POST['site'];
    $paysiteId = (int)$_POST['paysite'];
    $category_1 = (int)$_POST['category_1'];
    $category_2 = (int)$_POST['category_1'];
    $min_width = (int)$_POST['min_width'];
    $min_height = (int)$_POST['min_height'];
    $max_width = (int)$_POST['max_width'];
    $max_height = (int)$_POST['max_height'];
    $onsite_position = $_POST['onsite_position'];
    $onpage_position = $_POST['onpage_position'];
    $row =  (int)$_POST['row'];
    $column = (int)$_POST['column'];
    $number = (int)$_POST['number'];
    $use_if_empty = $_POST['use_if_empty'];

    if (isset($_POST['addSpot'])) {
      $insertId = $spots->addSpots( $name, $siteId, $paysiteId, $category_1, $category_2, $max_width, 
                                    $max_height, $min_width, $min_height, $onsite_position, $onpage_position,
                                    $row, $column, $number, $use_if_empty);
      if ($insertId) echo "Spot ". $insertId. " added";
    } elseif (isset($_POST['updateSpot']) && isset($_GET['id'])) {
      $id = intval($_GET['id']);
      if ($spots->updateSpots($id, $name, $siteId, $paysiteId, $category_1, $category_2, $max_width, 
                              $max_height, $min_width, $min_height, $onsite_position, $onpage_position,
                              $row, $column, $number, $use_if_empty)) {
        echo "Spot ". $id. " updated";
      }
    }
  }

  if ((isset($_GET['query']) && $_GET['query'] == 'add') || (isset ($_GET['id']))) {
      if (isset ($_GET['id']) && $spots) {
            $currentSpot = new CCurentBannerSpot($db->_db);
            if ($currentSpot->switchSpots($_GET['id'])) {
              $id = (int)$_GET['id'];
              $name = $currentSpot->getName();
              $siteId = $currentSpot->getSiteId();
              $paysiteId = $currentSpot->getPaysiteId();
              $category_1 = $currentSpot->getCategory1();
              $category_2 = $currentSpot->getCategory2();
              $max_width = $currentSpot->getMaxWidth();
              $max_height = $currentSpot->getMaxHeight();
              $min_width = $currentSpot->getMinWidth();
              $min_height = $currentSpot->getMinHeight();
              $onsite_position = $currentSpot->getOnsitePosition();
              $onpage_position = $currentSpot->getOnpagePosition();
              $row = $currentSpot->getRow();
              $column = $currentSpot->getColumn();
              $number = $currentSpot->getNumber();   
              $use_if_empty = $currentSpot->getIfEmptyBanner();   
            } else {
              $id = false;
              $name = false;
              $siteId = false;
              $paysiteId = false;
              $category_1 = false;
              $category_2 = false;
              $max_width = false;
              $max_height = false;
              $min_width = false;
              $min_height = false;
              $onsite_position = false;
              $onpage_position = false;
              $row = false;
              $column = false;
              $number = false;
              $use_if_empty = false;   
            }

     } else {
              $id = false;
              $name = false;
              $siteId = false;
              $paysite_id = false;
              $category_1 = false;
              $category_2 = false;
              $max_width = false;
              $max_height = false;
              $min_width = false;
              $min_height = false;
              $onsite_position = false;
              $onpage_position = false;
              $row = false;
              $column = false;
              $number = false;
              $use_if_empty = false;
      }
      $sitesList = $default->SitesGetAll();

?>
      <form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
        <div align="center">
        <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Название спота:
            </td>
            <td bgcolor="#e4e4e4">
              <input size="30" name="name" value="<?php if (isset($name) && $name !== false) echo $name;?>" >
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Сайт: </td>
            <td bgcolor="#e4e4e4">
              <select name="site">
                <?php 
                  foreach ($sitesList as $site) {
?>
            <option value="<?=$site['id']?>" <?php if ($siteId === $site['id']) echo "selected";?>><?=$site['name']?></option>
<?php                   
                  }
                ?>
              </select>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Платник: </td>
            <td bgcolor="#e4e4e4">
              <select name="paysite">
                <option value="0" <?php if (isset($paysiteId) && $paysiteId === 0) echo "selected"; ?>>Ротация платников</option>
                <?php if ($paysiteId) $default->PaysitesListing ("<option value=\"#PAYSITE_ID#\" #CHECKED#>#PAYSITE#</option>",$paysiteId);
                      else echo $default->AllPaysitesToString ("<option value=\"#PAYSITE_ID#\">#PAYSITE#</option>");
                ?>
              </select>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Категории: </td>
            <td bgcolor="#e4e4e4">
              Категория 1: <select name="category_1">
                <option value="0">Нет</option>
              </select> | 
              Категория 2: <select name="category_2">
                <option value="0">Нет</option>

              </select>
            </td>
          </tr>
          <td bgcolor="#e4e4e4" align="left">
              Min стороны:
            </td>
            <td bgcolor="#e4e4e4">
              <input size="8" name="min_width" value="<?php if (isset($min_width) && $min_width !== false) echo $min_width;?>" > x <input size="8" name="min_height" value="<?php if (isset($min_height) && $min_height !== false) echo $min_height;?>" >
            </td>
          </tr>          
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Max стороны:
            </td>
            <td bgcolor="#e4e4e4">
              <input size="8" name="max_width" value="<?php if (isset($max_width) && $max_width !== false) echo $max_width;?>" > x <input size="8" name="max_height" value="<?php if (isset($max_height) && $max_height !== false) echo $max_height;?>" >
            </td>
          </tr>
          <tr>
          <tr>
            <td bgcolor="#e4e4e4">Позиция внутри сайта: </td>
            <td bgcolor="#e4e4e4">
              <select name="onsite_position">
                <option value="main" <?php if ($onsite_position == 'main') echo "selected";?>>Главная</option>
                <option value="category" <?php if ($onsite_position == 'category') echo "selected";?>>Категория</option>
                <option value="gallery" <?php if ($onsite_position == 'gallery') echo "selected";?>>Галлерея</option>
                <option value="archive" <?php if ($onsite_position == 'main') echo "selected";?>>Архив</option>
                <option value="other" <?php if ($onsite_position == 'other') echo "selected";?>>Другое</option>
              </select>             
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Позиция внутри страницы: </td>
            <td bgcolor="#e4e4e4">
              <select name="onpage_position">
                <option value="top" <?php if ($onpage_position == 'top') echo "selected";?>>Верх</option>
                <option value="bottom" <?php if ($onpage_position == 'bottom') echo "selected";?>>Низ</option>
                <option value="lsidebar" <?php if ($onpage_position == 'lsidebar') echo "selected";?>>Левый сайдбар</option>
                <option value="rsidebar" <?php if ($onpage_position == 'rsidebar') echo "selected";?>>Правый сайдбар</option>
                <option value="middle" <?php if ($onpage_position == 'middle') echo "selected";?>>Середина</option>
              </select>             
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Какой ряд: </td>
            <td bgcolor="#e4e4e4">
              <select name="row">
                <option value="1" <?php if ($row == 1) echo "selected";?>>1</option>
                <option value="2" <?php if ($row == 2) echo "selected";?>>2</option>
                <option value="3" <?php if ($row == 3) echo "selected";?>>3</option>
                <option value="4" <?php if ($row == 4) echo "selected";?>>4</option>
                <option value="5" <?php if ($row == 5) echo "selected";?>>5</option>
                <option value="6" <?php if ($row == 6) echo "selected";?>>6</option>
                <option value="7" <?php if ($row == 7) echo "selected";?>>7</option>
                <option value="8" <?php if ($row == 8) echo "selected";?>>8</option>
                <option value="9" <?php if ($row == 9) echo "selected";?>>9</option>
              </select>             
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Какая колонка: </td>
            <td bgcolor="#e4e4e4">
              <select name="column">
                <option value="1" <?php if ($column == 1) echo "selected";?>>1</option>
                <option value="2" <?php if ($column == 2) echo "selected";?>>2</option>
                <option value="3" <?php if ($column == 3) echo "selected";?>>3</option>
                <option value="4" <?php if ($column == 4) echo "selected";?>>4</option>
                <option value="5" <?php if ($column == 5) echo "selected";?>>5</option>
                <option value="6" <?php if ($column == 6) echo "selected";?>>6</option>
                <option value="7" <?php if ($column == 7) echo "selected";?>>7</option>
                <option value="8" <?php if ($column == 8) echo "selected";?>>8</option>
                <option value="9" <?php if ($column == 9) echo "selected";?>>9</option>
              </select>             
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4">Числовое значение: </td>
            <td bgcolor="#e4e4e4">
              <select name="number">
                <option value="1" <?php if ($number == 1) echo "selected";?>>1</option>
                <option value="2" <?php if ($number == 2) echo "selected";?>>2</option>
                <option value="3" <?php if ($number == 3) echo "selected";?>>3</option>
                <option value="4" <?php if ($number == 4) echo "selected";?>>4</option>
                <option value="5" <?php if ($number == 5) echo "selected";?>>5</option>
                <option value="6" <?php if ($number == 6) echo "selected";?>>6</option>
                <option value="7" <?php if ($number == 7) echo "selected";?>>7</option>
                <option value="8" <?php if ($number == 8) echo "selected";?>>8</option>
                <option value="9" <?php if ($number == 9) echo "selected";?>>9</option>
              </select>             
            </td>
          </tr><tr>
            <td bgcolor="#e4e4e4">Использовать баннер если пустое значение: </td>
            <td bgcolor="#e4e4e4">
              <textarea name="use_if_empty" style="width: 310px; height: 420px;"><?=$use_if_empty?></textarea>           
            </td>
          </tr>      
        </table>
        <input type="submit" value="<?php if (isset($id)) { ?>Изменить спот<?php } else { ?>Добавить спот<?php } ?>" name="<?php if (isset($id) && $id) { ?>updateSpot<?php } else { ?>addSpot<?php } ?>" />
        </div>
      </form>
<?php
      if(isset($currentSpot)) {
        $banners = $currentSpot->getBannersFitSpot();
      }
      if (isset($banners) && $banners && is_array($banners)) {
        $banner = new CCurentBanner($db->_db);
        $sources = new Sources($db->_db);
        foreach ($banners as $id) {
          $banner->switchBanner($id);
          $paysiteId = $banner->getPaysiteId();
          $paysiteName = $sources->getSourceNameById($paysiteId);
          $width = $banner->getWidth();
          $height = $banner->getHeight();
          $type = $banner->getType();
          $text = $banner->getText();
          $specialLink = $banner->getSpecialLink();
          $result_filename = HOSTING .'/banners/' .ceil($id/1000)."/". $id . '_' . $width . 'x' . $height . '.' . $type;
  ?>
          
          <div style="margin:6px; padding: 5px; width: 1200px; height: 18px; border: 1px #000 solid; display: block-inline;">
            <div style='float:left;'>
              <a onmouseover="over('<?=$result_filename?>')" onmousemove="move(event)" onmouseout="out()" href="<?=$_SERVER['SCRIPT_NAME']?>?act=banners&id=<?=$id?>">Edit banner</a>
              -> ID: <?=$id?>
              <?=$paysiteName?> |
              <strong><?=$width?> x <?=$height?></strong>
            </div>
            <div style='float:right'>
              <?=$type?>
            </div>
          </div>

<?php          
        }
      }
    } else {
      $allSpots = $spots->getAllSpots();
//      var_dump($allSpots);
      $currentSpot = new CCurentBannerSpot($db->_db);

      if (is_array($allSpots)) {
        foreach ($allSpots as $id) {
        	$id = (int)$id;
          $currentSpot->switchSpots($id);
          $name = $currentSpot->getName();
          $siteId = $currentSpot->getSiteId();
          $siteName = $default->SiteInformation($siteId);
          $siteName = $siteName['name'];
          $paysiteId = $currentSpot->getPaysiteId();
          $category_1 = $currentSpot->getCategory1();
          $category_2 = $currentSpot->getCategory2();
          $max_width = $currentSpot->getMaxWidth();
          $max_height = $currentSpot->getMaxHeight();
          $min_width = $currentSpot->getMinWidth();
          $min_height = $currentSpot->getMinHeight();
          $onsite_position = $currentSpot->getOnsitePosition();
          $onpage_position = $currentSpot->getOnpagePosition();
          $row = $currentSpot->getRow();
          $column = $currentSpot->getColumn();
          $number = $currentSpot->getNumber();   
?>
          <div style="margin:6px; padding: 5px; width: 1200px; height: 18px; border: 1px #000 solid; display: block-inline;">
            <div style='float:left;'>
              <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=spots&id=<?=$id?>">Edit template</a>
              -> ID: <strong><?=$id?></strong> | <?=$name?> | <?=$siteName?> |
              <strong><?=$min_width?></strong>x<strong><?=$min_height?></strong>, <strong><?=$max_width?></strong>x<strong><?=$max_height?></strong>, <strong><?=$onpage_position?></strong>
            </div>
            <div style='float:right'>
              <?=$onpage_position?>
            </div>
          </div>

<?php          
        }
      }
    }
?>
      <div class="menu">
        >> <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=spots&query=add">Add new template</a>
        <br />
        <hr>
      </div>