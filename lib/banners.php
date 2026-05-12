<?php
	$banners = new CCurentBanner($db->_db);
  $sources = new Sources($db->_db);

  function banner_html($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
  }

  function banner_public_url($id, $width, $height, $type) {
    return HOSTING . '/banners/' . ceil((int)$id / 1000) . '/' . (int)$id . '_' . (int)$width . 'x' . (int)$height . '.' . $type;
  }

  if (isset($_POST['delete_banner']) && isset($_GET['id'])) {
    $banner_id = (int)$_GET['id'];
    if($banners->deleteBanner($banner_id)) {
      ?><h3>Баннер #<?=$banner_id?> удален</h3><?php
    } else {
      ?><h3>Ошибка! Баннер #<?=$banner_id?> не удален</h3><?php
    }
    unset($_POST);

  }
  if (isset($_POST['addBanner']) || isset($_POST['updateBanner'])) {
   
    $paysiteId = (int)$_POST['paysite'];
    $text = trim($_POST['text'] ?? '');
    $specialLink = trim($_POST['specialLink'] ?? '');
    if(isset($_FILES['file']['name']) && $_FILES['file']['name'] !== "") {
      $banner_from_local = true;
      $url = $_FILES['file']['tmp_name'];
    } else {
      $banner_from_local = false;
      $url = trim($_POST['url']);
    }
    if (isset($_REQUEST['addBanner'])) {

      if ($url !== "" && $bannerId = $banners->addBanner($url, $paysiteId, $text, $specialLink, $banner_from_local)) {
        echo $bannerId . " добавлен<br />";
      } else {
        echo "Баннер не добавлен";
        if ($banners->add_error) echo ": " . banner_html($banners->add_error);
        echo "<br />";
      }

    } else {
      $bannerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
      if ($bannerId && $banners->switchBanner($bannerId)) {
        $metaUpdated = $banners->updateBanner($paysiteId, $text, $specialLink);
        $imageUpdated = true;

        if ($url !== "") {
          $imageUpdated = $banners->replaceBannerImage($bannerId, $url, $banner_from_local);
        }

        if ($metaUpdated && $imageUpdated) {
          echo "Баннер #".$bannerId." обновлен<br />";
        } else {
          echo "Баннер #".$bannerId." обновлен не полностью";
          if ($banners->add_error) echo ": " . banner_html($banners->add_error);
          echo "<br />";
        }
      } else {
        echo "Баннер не найден<br />";
      }
    }
  }

  if (isset($_GET['page']) && (intval($_GET['page']) - 1) > 0) $page = (intval($_GET['page']) - 1);
  else $page = 0;

  if (isset($_GET['errors'])) {
    if (isset($_GET['clear'])) {
      if ($cache_worker->clearErrorBanners($page)) {
?>
        <script>
          alert("Очищена база ошибок спотов");
          window.location.href='index.php?act=banners&errors=1';
        </script>
<?php        
      }

    }
    $error_banners = $cache_worker->getErrorBanners($page);
    $items_count = intval($cache_worker->countErrorBanners());
    $spots = new CCurentBannerSpot($db->_db);
    $sources = new Sources($db->_db);
    if ($items_count) $pages = ceil($items_count / 50);
    else $pages = 0;
?>
  <a href="index.php?act=banners">Перейти к базе</a> | <a href="index.php?act=banners&amp;errors=1&amp;clear=yes">Очистить ошибки</a><br />
    Платники с отсутствующими баннерами в спотах. Всего отсутствует <?=$items_count?><br>
    <div style="width: 800px; margin 40px; padding 20px; border: red solid 2px; display: block">
<?php    
    foreach ($error_banners as $no_banner) {
      $paysite = $sources->getSource($no_banner['paysite']);
      if ($paysite && $spots->switchSpots($no_banner['spot'])) {

        $max_width = $spots->getMaxWidth();
        $max_height = $spots->getMaxHeight();
        $min_width = $spots->getMinWidth();
        $min_height = $spots->getMinHeight();
?>
        <div style="margin: 10px; float: left; display: block-inline; width: 100%; height: 12px; text-align: left;">
          <?=$paysite['name']?> размер <b><?php if ($min_width == $max_width) echo $min_width; else echo $min_width."-".$max_width;?>x<?=$min_height?>-<?=$max_height?></b>
          <a href="index.php?act=spots&amp;id=<?=$no_banner['spot']?>">Перейти к споту #<?=$no_banner['spot']?></a>
        </div>
<?php        
      } else {
        echo "Кака-то ошибка";
        var_dump($paysite);
        var_dump($spots->switchSpots($no_banner['spot']));
        echo "<br><br>";
      }
    }
?>  
      <div style="clear: both;"></div>
<?php
    if ($pages > 1) {      
      for ($i=1; $i<=$pages; $i++) {
        if ($i !== $page) {
?>
          <a href="index.php?act=banners&amp;errors=1&amp;page=<?=$i?>"><?=$i?></a>&nbsp;|&nbsp;
<?php         
        } else echo " ".$i. " | ";
      }  
    }
?>    
    </div>
<?php    
  } else {
    // start

  $thumbUrlPre = HOSTING . "/banners";
  if ((isset($_GET['query']) && $_GET['query'] == 'add') || (isset ($_GET['id']))) {
      if (isset ($_GET['id']) && $banners->switchBanner($_GET['id'])) {
        $id = $banners->getId();
        $paysiteId = $banners->getPaysiteId();
        $width = $banners->getWidth();
        $height = $banners->getHeight();
        $type = $banners->getType();
        $text = $banners->getText();
        $specialLink = $banners->getSpecialLink();
        $paysiteName = $sources->getSourceNameById($paysiteId);
      } else {
        $paysiteId = false;
        $paysiteName = false;
        $width = false;
        $height = false;
        $type = false;
        $text = false;
        $specialLink = false;
      }
?>
      <form enctype="multipart/form-data" action="<?=$_SERVER['SCRIPT_NAME'] . "?". $_SERVER['QUERY_STRING']?>" method="post">
        <div align="center">
        <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
          <tr>
            <td bgcolor="#e4e4e4">Платник: </td>
            <td bgcolor="#e4e4e4">
              <select name="paysite">
                <?php if ($paysiteId) $default->PaysitesListing ("<option value=\"#PAYSITE_ID#\" #CHECKED#>#PAYSITE#</option>",$paysiteId);
                      else $default->AllPaysitesToStringNoCount("<option value=\"#PAYSITE_ID#\">#PAYSITE#</option>");
                ?>
              </select>
            </td>
          </tr>
<?php 
          if ($type) {
?>  
          <tr>
            <td bgcolor="#e4e4e4">Тип: </td>
            <td bgcolor="#e4e4e4">
              <?=$type?>
            </td>
          </tr>
<?php
          }          
          if ($width && $height) {
?>  
          <tr>
            <td bgcolor="#e4e4e4">Размер: </td>
            <td bgcolor="#e4e4e4">
              <?=$width?> x <?=$height?>
            </td>
          </tr>
<?php
          }          
?>         
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Текст:
            </td>
            <td bgcolor="#e4e4e4">
              <input size="60" name="text" value="<?=banner_html($text)?>">
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Спец. линка:
            </td>
            <td bgcolor="#e4e4e4">
              <input size="60" name="specialLink" value="<?=banner_html($specialLink)?>">
            </td>
          </tr>
<?php          
          if (isset($id) && $id && $width && $height && $type) {
              $result_filename = banner_public_url($id, $width, $height, $type);
?>            
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Текущий баннер:
            </td>
            <td bgcolor="#e4e4e4" align="left">
              <img style="max-width: 360px; max-height: 220px;" src="<?=banner_html($result_filename)?>" />
            </td>
          </tr>  
<?php } ?>
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              <?php if (isset($id) && $id) { ?>Новый URL картинки:<?php } else { ?>URL картинки:<?php } ?>
            </td>
            <td bgcolor="#e4e4e4">
              <input size="30" name="url" value="">
              <?php if (isset($id) && $id) { ?><small>оставить пустым, если файл менять не нужно</small><?php } ?>
            </td>
          </tr>
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              <?php if (isset($id) && $id) { ?>Новый файл:<?php } else { ?>Файл:<?php } ?>
            </td>
            <td bgcolor="#e4e4e4">
              <input name="file" size="80" type="file" />
            </td>
          </tr>
        </table>
        <input type="submit" value="<?php if (isset($id)) { ?>Изменить баннер<?php } else { ?>Добавить баннер<?php } ?>" name="<?php if (isset($id)) { ?>updateBanner<?php } else { ?>addBanner<?php } ?>" />
        <?php if (isset($id)) { ?>
          <input type="submit" value="Удалить баннер" name="delete_banner" style="color: red; float: left;"/>
        <?php } ?>
        </div>
      </form>
<?php
    } else {
?>
    <a href="index.php?act=banners&amp;errors=1">Перейти к ошибкам</a><br />
<?php      
      $banners = new CBanners($db->_db);
      $filterPaysite = isset($_GET['paysite']) ? (int)$_GET['paysite'] : -1;
      $filterWidth = isset($_GET['width']) ? (int)$_GET['width'] : 0;
      $filterHeight = isset($_GET['height']) ? (int)$_GET['height'] : 0;
      $bannerIds = $banners->getAllBanners($filterPaysite >= 0 ? $filterPaysite : false);
      $bannerRows = array();

      foreach ($bannerIds as $id) {
        $banner = new CCurentBanner($db->_db);
        if (!$banner->switchBanner($id)) {
          continue;
        }

        $paysiteId = $banner->getPaysiteId();
        $width = (int)$banner->getWidth();
        $height = (int)$banner->getHeight();

        if ($filterWidth > 0 && $width !== $filterWidth) {
          continue;
        }

        if ($filterHeight > 0 && $height !== $filterHeight) {
          continue;
        }

        $bannerRows[] = array(
          'id' => $id,
          'paysite_id' => $paysiteId,
          'paysite_name' => $sources->getSourceNameById($paysiteId),
          'width' => $width,
          'height' => $height,
          'type' => $banner->getType(),
          'text' => $banner->getText(),
          'special_link' => $banner->getSpecialLink(),
        );
      }
?>
      <style>
        .banner-filters {
          width: 100%;
          max-width: 1280px;
          margin: 10px auto;
          padding: 8px;
          background: #eeeeee;
          border: 1px solid #bbbbbb;
          text-align: left;
        }
        .banner-filters input,
        .banner-filters select {
          margin-right: 8px;
        }
        .banner-text-cell {
          width: 92px;
          max-width: 92px;
          text-align: center;
          position: relative;
        }
        .banner-text-badge {
          display: inline-block;
          padding: 2px 7px;
          border: 1px solid #888888;
          background: #ffffff;
          color: #333333;
          cursor: default;
          font-size: 11px;
          line-height: 16px;
        }
        .banner-text-tooltip {
          display: none;
          position: absolute;
          z-index: 20;
          top: 22px;
          left: 0;
          width: 280px;
          padding: 8px;
          background: #fffde8;
          border: 1px solid #777777;
          box-shadow: 0 2px 6px rgba(0,0,0,0.25);
          color: #222222;
          text-align: left;
          overflow-wrap: anywhere;
          white-space: normal;
        }
        .banner-text-cell:hover .banner-text-tooltip {
          display: block;
        }
      </style>
      <form class="banner-filters" method="get" action="<?=$_SERVER['SCRIPT_NAME']?>">
        <input type="hidden" name="act" value="banners">
        <label>
          Платник:
          <select name="paysite">
            <option value="-1" <?php if ($filterPaysite < 0) echo 'selected'; ?>>Все</option>
            <option value="0" <?php if ($filterPaysite === 0) echo 'selected'; ?>>Ротация</option>
            <?php $default->AllPaysitesToStringNoCount("<option value=\"#PAYSITE_ID#\" #CHECKED#>#PAYSITE#</option>", 0, 0, $filterPaysite > 0 ? $filterPaysite : false); ?>
          </select>
        </label>
        <label>
          Ширина:
          <input type="number" name="width" value="<?php if ($filterWidth > 0) echo $filterWidth; ?>" min="1" style="width: 70px;">
        </label>
        <label>
          Высота:
          <input type="number" name="height" value="<?php if ($filterHeight > 0) echo $filterHeight; ?>" min="1" style="width: 70px;">
        </label>
        <input type="submit" value="Фильтровать">
        <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=banners">Сбросить</a>
        <span style="margin-left: 16px;">Найдено: <?=count($bannerRows)?></span>
      </form>
      <table class="disclaim" cellpadding="4" cellspacing="1" border="0" style="width: 100%; max-width: 1280px; margin: 10px auto; text-align: left;">
        <tr bgcolor="#d8d8d8">
          <th>ID</th>
          <th>Баннер</th>
          <th>Платник</th>
          <th>Размер</th>
          <th>Тип</th>
          <th>Текст</th>
          <th>Спец. линка</th>
          <th></th>
        </tr>
<?php
      if (empty($bannerRows)) {
?>
        <tr><td colspan="8">Баннеры не найдены</td></tr>
<?php
      }

      foreach ($bannerRows as $bannerRow) {
        $id = $bannerRow['id'];
        $width = $bannerRow['width'];
        $height = $bannerRow['height'];
        $type = $bannerRow['type'];
        $text = $bannerRow['text'];
        $specialLink = $bannerRow['special_link'];
        $paysiteName = $bannerRow['paysite_name'];
        $result_filename = banner_public_url($id, $width, $height, $type);
?>
        <tr bgcolor="#eeeeee">
          <td>#<?=$id?></td>
          <td style="width: 190px;">
            <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=banners&amp;id=<?=$id?>">
              <img style="max-width: 180px; max-height: 120px;" src="<?=banner_html($result_filename)?>" alt="banner <?=$id?>">
            </a>
          </td>
          <td><?=banner_html($paysiteName ?: 'Ротация')?></td>
          <td><strong><?=$width?> x <?=$height?></strong></td>
          <td><?=banner_html($type)?></td>
          <td class="banner-text-cell">
            <?php if (trim((string)$text) !== '') { ?>
              <span class="banner-text-badge">Посмотреть</span>
              <div class="banner-text-tooltip"><?=banner_html($text)?></div>
            <?php } else { ?>
              &mdash;
            <?php } ?>
          </td>
          <td style="max-width: 260px; overflow-wrap: anywhere;"><?=banner_html($specialLink)?></td>
          <td><a href="<?=$_SERVER['SCRIPT_NAME']?>?act=banners&amp;id=<?=$id?>">Редактировать / заменить</a></td>
        </tr>
<?php          
      }
?>
      </table>
<?php
    }
?>
      <div class="menu">
        >> <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=banners&query=add">Add new banner</a>
        <br />
        <hr>
      </div>
<?php
  // finish
  }
?>
