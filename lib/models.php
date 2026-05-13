<?php

$zodiac_signs = array('aries', 'taurus', 'taurus', 'gemini', 'cancer', 'leo', 'virgo', 'libra', 'scorpio', 'saggitarius', 'capricorn', 'aquarius', 'pisces');
$sex_array = array('male', 'female', 'shemale');
$eyes_colors_array = array('amber', 'blue', 'brown', 'gray', 'green', 'hazel');
$body_types_array = array('skinny', 'thin', 'slim', 'athletic', 'muscular', 'bodybuilder', 'chubby', 'fat');
$hair_colors_array = array('blond', 'brunette', 'red', 'gray', 'white', 'bald', 'brown');
$ethnics_array = array('arab', 'american', 'euro', 'ebony', 'asian', 'latin', 'indian');

if (isset($_GET['fix_main_image'])) {
  $models = new CModels($db->_db);
  $galleries = new Galleries($db->_db);
  $no_image_models = $models->noMainImageModels();

  if ($no_image_models && is_array($no_image_models)) {
    $finfo = new finfo(FILEINFO_MIME);
    foreach ($no_image_models as $model) {
      $addedModelId = $model['id'];
      $url = $galleries->getImage($model['picture']);
      $zip = new Grabber_new();

      $destinationPath = $zip->fetchFile($hosting . $url, CONTENT_TYPE_IMAGE);
      if ($destinationPath) $destinationPath = TMPDIR . $destinationPath;
      $loggingFilename = $url;

      if (preg_match('#image\/(jpeg)#im', $finfo->file($destinationPath))) {
        chmod($destinationPath, 0777);
        $gallery = new Galleries($db->_db);
        if ($models->switchModel($addedModelId)) {
          if ($image_id = $gallery->uploadModelImage($addedModelId, $destinationPath, 'vertic')) {
            $updated = $models->updateVerticImage($image_id);
            echo "Изображение добавлено для модели: '" . intval($addedModelId) . "'";
            $cache_worker->server_cacheModel($addedModelId);
          } else {
            echo "Ошибка! Изображение не добавлено для модели: '" . intval($addedModelId) . "'";
          }
        } else echo "Can't switch to model: '" . $addedModelId . "'<br>";
      } else {
        unlink($destinationPath);
        $log = new Logger("Файл закачаный с " . $loggingFilename . " не является зипом!", true);
        echo "Файл не является зипом, ошибка!<br>";
      }
      $count++;
      if ($count > 55) break;
    }
  }
}



$thumbUrlPre = HOSTING . "/thumbs/p/240";
$thumbUrlPreSmall = HOSTING . "/thumbs/p/180";
$models = new CModels($db->_db);

if (isset($_REQUEST['deletemodel']) && intval($_REQUEST['deletemodel']) && $models->switchModel(intval($_REQUEST['deletemodel']))) {
  $model_galleries = $models->deleteModel();
  if ($model_galleries) {
    if (is_array($model_galleries)) {
      foreach ($model_galleries as $gal_id) {
        $cache_worker->server_updateGalleryModels($gal_id);
      }
    }
?>
    Модель ID:<strong><?= intval($_REQUEST['deletemodel']) ?></strong>, <strong><?= $models->getName() ?></strong> удалена
  <?php
  } else {
  ?>
    Модель ID:<strong><?= intval($_REQUEST['deletemodel']) ?></strong>, <strong><?= $models->getName() ?></strong> не удалена.
    Ошибка!
  <?php
  }
} else {
  if (isset($_GET['sex']) && $_GET['sex'] == 'ignore') $modelSex = false;
  elseif (isset($_GET['sex']) && $_GET['sex'] == 'male') $modelSex = 'male';
  elseif (isset($_GET['sex']) && $_GET['sex'] == 'female') $modelSex = 'female';
  elseif (isset($_GET['sex']) && $_GET['sex'] == 'shemale') $modelSex = 'shemale';
  else $modelSex = false;
  ?>
  <div style="display: block;  width: 1200px; text-align: left; font-size: 12px; margin: 15px;">
    Всего моделей: <?= $models_count = $models->modelsCount(); ?>,
    Моделей в кэше: <strong id="models_count_block"><?php
                                                    $models_cached = $cache_worker->modelsCount();
                                                    if ($models_cached && $models_cached == $models_count) echo $models_cached;
                                                    elseif (!$models_cached) {
                                                    ?>
        Пусто
      <?php
                                                    } else {
      ?>
        Количество моделей в кеше не совпадает: <i><?= $models_cached ?></i>
      <?php
                                                    }
      ?></strong>
    <input type="button" value="Пересобрать кeш моделей" id="init_models" onclick="init_models();">
  </div>
  <br />
  <div class="menu" style="margin: 20px; display: block; width: 1200px; height: 25px; padding: 4px;">
    <?php
    if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') {
    ?>
      <div style="float: left; padding-left: 15px;"><a href="index.php?act=models&amp;qrstring=<?= time() ?>">Показать все
          списком</a></div>
    <?php
    } else {
    ?>
      <div style="float: left; padding-left: 15px;"><a
          href="index.php?act=models&amp;thumbs=true&amp;qrstring=<?= time() ?>">Показать все тумбами</a></div>
    <?php
    }
    ?>
    <div style="float: left; padding-left: 15px; margin-left: 15px; border-left: solid 2px #000;">
      Тумбами:&nbsp;&nbsp;&nbsp;&nbsp;<a
        href="index.php?act=models&amp;thumbs=true&amp;sex=female&amp;qrstring=<?= time() ?>">Female</a></div>
    <div style="float: left; padding-left: 10px;"><a
        href="index.php?act=models&amp;thumbs=true&amp;sex=male&amp;qrstring=<?= time() ?>">Male</a></div>
    <div style="float: left; padding-left: 10px;"><a
        href="index.php?act=models&amp;thumbs=true&amp;sex=shemale&amp;qrstring=<?= time() ?>">Shemale</a></div>
    <div style="float: left; padding-left: 15px; margin-left: 15px; border-left: solid 2px #000;">
      Списком:&nbsp;&nbsp;&nbsp;&nbsp;<a
        href="index.php?act=models&amp;sex=female&amp;qrstring=<?= time() ?>">Female</a></div>
    <div style="float: left; padding-left: 10px;"><a
        href="index.php?act=models&amp;sex=male&amp;qrstring=<?= time() ?>">Male</a></div>
    <div style="float: left; padding-left: 10px;"><a
        href="index.php?act=models&amp;sex=shemale&amp;qrstring=<?= time() ?>">Shemale</a></div>
    <div style="padding-right: 30px; float: right; display: block-inline;">&lt; <a
        href="index.php?act=models_import&amp;qrstring=<?= time() ?>">Импорт моделей</a> | <a
        href="index.php?act=models&amp;query=add&amp;qrstring=<?= time() ?>">Добавить модель</a> &gt;</div>
  </div>
  <?php
  if (isset($_POST['addModel']) || isset($_POST['updateModel'])) {
    $name = $_POST['name'];
    $active = $_POST['active'];
    $sex = $_POST['sex'];
    $role = $_POST['role'];
    $hair = $_POST['hair'];
    $birth = $_POST['birth'];
    $body = $_POST['body'];
    $personal_site_id = $_POST['personal_site_id'];
    $height = (int)$_POST['height'];
    $size = (int)$_POST['size'];
    $picture = (int)$_POST['picture'];
    $info = $_POST['info'];
    $cock_boobs = $_POST['cock_boobs'];
    $country = trim($_POST['country']);
    $eyes = $_POST['eyes'];
    $ethnic = $_POST['ethnic'];
    $piercing = $_POST['piercing'];
    $tattoo = $_POST['tattoo'];
    $tattooDesc = $_POST['tattooDesc'];
    $classic = $_POST['classic'];
    $twitter = $_POST['twitter'];
    $facebook = $_POST['facebook'];
    $category_of_age = $_POST['category_of_age'];
    $zodiac = $_POST['zodiac'];

    $newPseudonims = array();
    if (isset($_REQUEST['newPseudonimName']) && is_array($_REQUEST['newPseudonimName'])) {
      foreach ($_REQUEST['newPseudonimName'] as $pseudo_name) {
        $newPseudonims[] = str_replace("&#39;", "'", $pseudo_name);
      }
    }
    if (isset($_POST['addModel'])) {
      $addedModelId = $models->addModel($name, $sex, $hair, $body, $active, $birth, $height, $size, $role, $info, $personal_site_id, $picture, $eyes, $cock_boobs, $ethnic, $piercing, $tattoo, $tattooDesc, $country, $newPseudonims, $classic, $twitter, $facebook, $category_of_age, $zodiac);
      if ($addedModelId) {
  ?>
        Добавлена модель #<a href="index.php?act=models&amp;id=<?= $addedModelId ?>"><?= $addedModelId ?></a>
    <?php
      }
    } elseif (isset($_POST['updateModel']) && isset($_GET['id']) && $models->switchModel($_GET['id'])) {
      $model_update_result = $models->updateModel($name, $sex, $hair, $body, $active, $birth, $height, $size, $role, $info, $personal_site_id, $picture, $eyes, $cock_boobs, $ethnic, $piercing, $tattoo, $tattooDesc, $country, $classic, $twitter, $facebook, false, false, $category_of_age, $zodiac);
      $addedModelId = intval($_GET['id']);
      if ($model_update_result) {
        echo "Модель " . $addedModelId . ", Апдейт ОК<br>";
      } else {
        echo "Ошибка апдейта модели " . $addedModelId . ", смотри логи<br>";
      }
    }

    if ($addedModelId && (isset($_POST['addModel']) || isset($_POST['updateModel']))) {
      $error = false;
      $zipFolderPath = TMPDIR . "/.models";
      $tempFileName = md5(time() . getmypid());
      $destinationPath = $zipFolderPath . "/" . intval($addedModelId) . "-" . $tempFileName . ".tmp";
      if ((isset($_POST['model_image_url']) && $_POST['model_image_url'] != "") || (isset($_FILES) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] != "" && $_FILES['file']['tmp_name'] != false)) {
        if (isset($_POST['model_image_url']) && $_POST['model_image_url'] != "") {
          $zip = new Grabber_new();
          $destinationPath = $zip->fetchFile($_POST['model_image_url'], CONTENT_TYPE_IMAGE);
          if ($destinationPath) $destinationPath = TMPDIR . $destinationPath;
          $loggingFilename = $url;
        } else {
          $tmp = $_FILES['file']['tmp_name'];
          if (!move_uploaded_file($tmp, $destinationPath)) {
            $error = "Проблема с записью временного файла в папку:" . $destinationPath;
          }
        }

        $loggingFilename = $tmp = $_FILES['file']['name'];
        if ($destinationPath && !$error) {
          // сохранение первичной информации
          $finfo = new finfo(FILEINFO_MIME);

          if (preg_match('#image\/(jpeg)#im', $finfo->file($destinationPath))) {
            chmod($destinationPath, 0777);
            $gallery = new Galleries($db->_db);
            if ($image_id = $gallery->uploadModelImage($addedModelId, $destinationPath, $_POST['layout'])) {
              echo "Изображение добавлено для модели: '" . intval($addedModelId) . "'";
            } else {
              echo "Ошибка! Изображение не добавлено для модели: '" . intval($addedModelId) . "'";
            }
          } else {
            unlink($destinationPath);
            $log = new Logger("Файл закачаный с " . $loggingFilename . " не является JPG!", true);
            echo "Файл не является JPG, ошибка!<br>";
          }
        } else {
          if (!$loggingFilename) $loggingFilename = "Unknown";
          $log = new Logger("Файл " . $loggingFilename . " не загружен! " . $error, true);
          echo "Файл не загружен, ошибка!<br>" . $error;
          // логгирование ошибки  
        }
      } else {
        $destinationPath = false;
      }
      $cache_worker->server_cacheModel($addedModelId);
    }
  }

  if ((isset($_GET['query']) && $_GET['query'] == 'add') || (isset($_GET['id']))) {
    if (isset($_GET['id']) && $models->switchModel($_GET['id'])) {
      $id = $models->getId();
      $name = $models->getName();
      $active = $models->getActive();
      $sex = $models->getSex();
      $role = $models->getRole();
      $hair = $models->getHair();
      $birth = $models->getBirth();
      $body = $models->getBody();
      $personal_site_id = $models->getPersonalSite();
      $height = $models->getHeight();
      $picture = $models->getPicture();
      $classic = $models->getIfClassic();

      // var_dump($personal_site_id);

      $twitter = $models->getTwitter();
      $facebook = $models->getFacebook();
      $all_images = $models->getAllImages();
      $category_of_age = $models->getCategoryOfAge();
      $zodiac = $models->getZodiac();

      if ($sex == 'male') $cock_boobs = $models->getCock();
      else $cock_boobs = $models->getBoobs();
      $size = $models->getSize();
      $info = $models->getInfo();
      $main_image_ar = $models->getMainVerticImageUrl('big');
      $thumbURL = $main_image_ar['url'];
      $main_thumb_id = $main_image_ar['model_image_id'];

      $country = $models->getCountry();
      if ($sex == 'male') $cock_boobs = $models->getCock();
      else $cock_boobs = $models->getBoobs();
      $eyes = $models->getEyes();
      $ethnic = $models->getEthnic();
      $piercing = $models->getPiercing();
      $tattoo = $models->getTattoo();
      $tattooDesc = $models->getTattooDesc();
      $pseudonims = $models->getPseudonims();
    } else {
      $id = false;
      if (isset($_GET['name'])) {
        $name = urldecode($_GET['name']);
        $name = preg_replace("/[^a-z\s\.\']/im", " ", $name);
      } else {
        $name = false;
      }
      $active = false;
      $sex = false;
      $role = false;
      $hair = false;
      $birth = false;
      $body = false;
      $personal_site_id = false;
      $height = false;
      $picture = false;
      $size = false;
      $info = false;
      $country = false;
      $cock_boobs = false;
      $eyes = false;
      $ethnic = false;
      $piercing = false;
      $tattoo = false;
      $tattooDesc = false;
      $pseudonims = false;
      $classic = false;
      $twitter = false;
      $facebook = false;
      $category_of_age = -1;
      $zodiac = false;
    }
    ?>
    <script>
      function hide_block(id) {
        var link = document.getElementById(id);
        if (link) {
          link.style.display = 'none';
          link.style.visibility = 'hidden';
        }
      }

      function remove_block(id) {
        var link = document.getElementById(id);
        if (link) {
          link.parentNode.removeChild(link);
        }
      }

      function show_block(id) {
        var link = document.getElementById(id);
        if (link) {
          link.style.display = 'inline';
          link.style.visibility = 'visible';
        }
      }

      function get_model_images(model_id, thumb_layout) {
        var $jq = jQuery.noConflict();
        var url_pre = '<?= HOSTING ?>';
        var check_if_opened = document.getElementById("model_image_block");

        if (!check_if_opened) {
          $jq.post("util/model.show_all_images.php", {
            model_id: model_id,
            thumb_layout: thumb_layout
          }, function(data) {
            if ('success' in data) {
              var node = document.getElementById('wrapper');
              var close_div = document.createElement('div');
              close_div.setAttribute("style",
                "width: 100%; position: absolute; top: 306px; left: 283px; color: white; background-color: rgb(255, 90, 90); width: 200px; height: 33px; text-align: center; font-size: 26px; cursor: pointer;"
              );
              close_div.setAttribute("onclick", "remove_block('model_image_block')");
              close_div.innerHTML = "Закрыть";

              var img_div = document.createElement('div');
              document.getElementById('wrapper').appendChild(img_div);



              img_div.setAttribute("style",
                "position:fixed; width: auto; height: auto; background-color: rgba(0, 0, 0, 0.6); border: solid 1px #000;"
              );
              img_div.setAttribute("id", "model_image_block");
              img_div.style.padding = '30px';
              img_div.style.top = '160px';
              img_div.style.padding = '30px';
              img_div.style.zIndex = "9999";

              for (var image in data.images) {
                img_div.innerHTML += '<img src=' + url_pre + data.images[image] +
                  ' onclick="set_model_main_image(' + model_id + ', ' + image + ', \'' + thumb_layout +
                  '\')" style="margin: 15px;" />';
              }
              img_div.appendChild(close_div);
            } else if ('error' in data) alert(data.error);
            else alert('Ошибка изменения статуса РСС для тумбы ' + id + '. Ошибка: ' + data);
          });
        } else {
          if (check_if_opened.style.display == 'hidden' || check_if_opened.style.display == 'none') {
            show_block("model_image_block");
          } else {
            remove_block('model_image_block');
            get_model_images(model_id, thumb_layout);
          }
        }

      }

      function set_model_main_image(model_id, image_id, thumb_layout) {
        var $jq = jQuery.noConflict();

        $jq.post("util/model.set_main_image.php", {
          model_id: model_id,
          image_id: image_id,
          thumb_layout: thumb_layout
        }, function(data) {
          if ('success' in data) {
            if (thumb_layout == 'vertic') div_id = 'select_vertic_tumb';
            if (thumb_layout == 'horiz') div_id = 'select_horiz_tumb';
            var select_thumb = document.getElementById(div_id);
            if (select_thumb) {
              select_thumb.innerHTML = '<img src="<?= HOSTING ?>' + data.image + '" />';
            } else alert("Ошибка переноса тумбы на место");
            remove_block('model_image_block');

          } else if ('error' in data) alert(data.error);
          else alert('Ошибка изменения статуса РСС для тумбы ' + id + '. Ошибка: ' + data);
        });
      }
    </script>
    <form enctype="multipart/form-data" action="<?= $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'] ?>" method="post"
      id="modelform">
      <div align="center">
        <?php
        if (isset($thumbURL)) {
        ?>
          <div style="float: left">
            <img src="<?= $thumbURL ?>" />
          </div>
        <?php
        }
        ?>
        <div style="float: right;">
          <table class="disclaim" cellpadding="3" cellspacing="3" border="0">
            <tr>
              <td bgcolor="#e4e4e4">Имя: </td>
              <td bgcolor="#e4e4e4"><input size="42" name="name" <?php if ($name) echo "value='" . $name . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Модель активна: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="active" value="no" <?php if ($active == 'no') echo " checked"; ?>>Нет
                <input type="radio" name="active" value="yes"
                  <?php if ($active == 'yes' || !$active) echo " checked"; ?>>Да
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Пол: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="sex" value="female"
                  <?php if ($sex == 'female' || !$sex) echo " checked"; ?>>Жен.
                <input type="radio" name="sex" value="male" <?php if ($sex == 'male') echo " checked"; ?>>Муж.
                <input type="radio" name="sex" value="shemale"
                  <?php if ($sex == 'shemale') echo " checked"; ?>>Шмель
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Classic: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="classic" value="0" <?php if (!$classic) echo " checked"; ?>>Нет
                <input type="radio" name="classic" value="1" <?php if ($classic) echo " checked"; ?>>Да
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Этник: </td>
              <td bgcolor="#e4e4e4">
                <select name="ethnic">
                  <option value="none"
                    <?php if (!$ethnic || ($ethnic && $ethnic == 'none')) echo " selected='selected'"; ?>>
                    Неизвестно</option>
                  <option value="american"
                    <?php if ($ethnic && $ethnic == 'american') echo " selected='selected'"; ?>>american
                  </option>
                  <option value="arab"
                    <?php if ($ethnic && $ethnic == 'arab') echo " selected='selected'"; ?>>arab</option>
                  <option value="asian"
                    <?php if ($ethnic && $ethnic == 'asian') echo " selected='selected'"; ?>>asian</option>
                  <option value="ebony"
                    <?php if ($ethnic && $ethnic == 'ebony') echo " selected='selected'"; ?>>ebony</option>
                  <option value="euro"
                    <?php if ($ethnic && $ethnic == 'euro') echo " selected='selected'"; ?>>euro</option>
                  <option value="indian"
                    <?php if ($ethnic && $ethnic == 'indian') echo " selected='selected'"; ?>>indian
                  </option>
                  <option value="latin"
                    <?php if ($ethnic && $ethnic == 'latin') echo " selected='selected'"; ?>>latin</option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Глаза: </td>
              <td bgcolor="#e4e4e4">
                <select name="eyes">
                  <option value="none"
                    <?php if (!$eyes || ($eyes && $eyes == 'none')) echo " selected='selected'"; ?>>
                    Неизвестно</option>
                  <option value="amber" <?php if ($eyes && $eyes == 'amber') echo " selected='selected'"; ?>>
                    amber</option>
                  <option value="blue" <?php if ($eyes && $eyes == 'blue') echo " selected='selected'"; ?>>
                    blue</option>
                  <option value="brown" <?php if ($eyes && $eyes == 'brown') echo " selected='selected'"; ?>>
                    brown</option>
                  <option value="gray" <?php if ($eyes && $eyes == 'gray') echo " selected='selected'"; ?>>
                    gray</option>
                  <option value="green" <?php if ($eyes && $eyes == 'green') echo " selected='selected'"; ?>>
                    green</option>
                  <option value="hazel" <?php if ($eyes && $eyes == 'hazel') echo " selected='selected'"; ?>>
                    hazel</option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Роль: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="role" value="bottom"
                  <?php if ($role == 'bottom') echo " checked"; ?>>Bottom
                <input type="radio" name="role" value="top" <?php if ($role == 'top') echo " checked"; ?>>Top
                <input type="radio" name="role" value="versatile"
                  <?php if ($role == 'versatile' || !$role) echo " checked"; ?>>Versatile
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Волосы: </td>
              <td bgcolor="#e4e4e4">
                <select name="hair">
                  <option value="bald" <?php if ($hair == 'bald') echo " selected='selected'"; ?>>bald
                  </option>
                  <option value="blond" <?php if ($hair == 'blond') echo " selected='selected'"; ?>>blond
                  </option>
                  <option value="brown" <?php if ($hair == 'brown' || !$hair) echo " selected='selected'"; ?>>
                    brown</option>
                  <option value="brunette" <?php if ($hair == 'brunette') echo " selected='selected'"; ?>>
                    brunette</option>
                  <option value="gray" <?php if ($hair == 'gray') echo " selected='selected'"; ?>>gray
                  </option>
                  <option value="red" <?php if ($hair == 'red') echo " selected='selected'"; ?>>red</option>
                  <option value="white" <?php if ($hair == 'white') echo " selected='selected'"; ?>>white
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Тип тела: </td>
              <td bgcolor="#e4e4e4">
                <select name="body">
                  <option value="none" <?php if ($body && $body == 'none') echo " selected='selected'"; ?>>
                    Неизвестно</option>
                  <option value="skinny"
                    <?php if ($body && $body == 'skinny') echo " selected='selected'"; ?>>skinny</option>
                  <option value="thin" <?php if ($body && $body == 'thin') echo " selected='selected'"; ?>>
                    thin</option>
                  <option value="slim" <?php if ($body && $body == 'slim') echo " selected='selected'"; ?>>
                    slim</option>
                  <option value="athletic"
                    <?php if ($body && $body == 'athletic') echo " selected='selected'"; ?>>athletic
                  </option>
                  <option value="muscular"
                    <?php if ($body && $body == 'muscular') echo " selected='selected'"; ?>>muscular
                  </option>
                  <option value="bodybuilder"
                    <?php if ($body && $body == 'bodybuilder') echo " selected='selected'"; ?>>bodybuilder
                  </option>
                  <option value="chubby"
                    <?php if ($body && $body == 'chubby') echo " selected='selected'"; ?>>chubby</option>
                  <option value="fat" <?php if ($body && $body == 'fat') echo " selected='selected'"; ?>>fat
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Знак зодиака: </td>
              <td bgcolor="#e4e4e4">
                <select name="zodiac">
                  <option value="none"
                    <?php if ($zodiac == 'none' || !$zodiac) echo " selected='selected'"; ?>>Неизвестно
                  </option>
                  <?php
                  foreach ($zodiac_signs as $zodiac_elem) { ?>
                    <option value="<?= $zodiac_elem ?>"
                      <?php if ($zodiac == $zodiac_elem) echo " selected='selected'"; ?>><?= $zodiac_elem ?>
                    </option>
                  <?php           }     ?>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Возрастная категория: </td>
              <td bgcolor="#e4e4e4">
                <select name="category_of_age">
                  <option value="Неизвестно"
                    <?php if ($category_of_age == -1) echo " selected='selected'"; ?>>Неизвестно</option>
                  <option value="20" <?php if ($category_of_age == 20) echo " selected='selected'"; ?>>18..20
                  </option>
                  <option value="25" <?php if ($category_of_age == 25) echo " selected='selected'"; ?>>20..25
                  </option>
                  <option value="35" <?php if ($category_of_age == 35) echo " selected='selected'"; ?>>25..35
                  </option>
                  <option value="45" <?php if ($category_of_age == 45) echo " selected='selected'"; ?>>35..45
                  </option>
                  <option value="55" <?php if ($category_of_age == 55) echo " selected='selected'"; ?>>45..55
                  </option>
                  <option value="65" <?php if ($category_of_age == 65) echo " selected='selected'"; ?>>55..65
                  </option>
                  <option value="90" <?php if ($category_of_age == 90) echo " selected='selected'"; ?>>65+
                  </option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Сиськи/Член: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="cock_boobs" value="none"
                  <?php if ($cock_boobs == 'none' || !$cock_boobs) echo " checked"; ?>>Неизвестно
                <input type="radio" name="cock_boobs" value="natural"
                  <?php if ($cock_boobs == 'natural') echo " checked"; ?>>Натуральные/Необрезан
                <input type="radio" name="cock_boobs" value="mod"
                  <?php if ($cock_boobs == 'mod') echo " checked"; ?>>Силикон/Обрезан
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Пирсинг: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="piercing" value="none"
                  <?php if ($piercing == 'none' || !$piercing) echo " checked"; ?>>Неизвестно
                <input type="radio" name="piercing" value="yes"
                  <?php if ($piercing == 'yes') echo " checked"; ?>>Есть
                <input type="radio" name="piercing" value="no"
                  <?php if ($piercing == 'no') echo " checked"; ?>>Нет
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Татту: </td>
              <td bgcolor="#e4e4e4">
                <input type="radio" name="tattoo" value="none"
                  <?php if ($tattoo == 'none' || !$tattoo) echo " checked"; ?>>Неизвестно
                <input type="radio" name="tattoo" value="yes"
                  <?php if ($tattoo == 'yes') echo " checked"; ?>>Есть
                <input type="radio" name="tattoo" value="no" <?php if ($tattoo == 'no') echo " checked"; ?>>Нет
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Описание тату: </td>
              <td bgcolor="#e4e4e4"><input size="42" name="tattooDesc"
                  <?php if ($tattooDesc) echo "value='" . $tattooDesc . "'"; ?>></td>
            </tr>

            <tr>
              <td bgcolor="#e4e4e4">Откуда модель(страна): </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="country" <?php if ($country) echo "value='" . $country . "'"; ?>>
              </td>
            </tr>

            <tr>
              <td bgcolor="#e4e4e4">Дата рождения (год-месяц-день): </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="birth" <?php if ($birth) echo "value='" . $birth . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4" align="left">
                Сайт:
              </td>
              <td bgcolor="#e4e4e4">
                <select name="personal_site_id">
                  <option value="0">No</option>
                  <?php if ($personal_site_id) $default->PaysitesListing("<option value=\"#PAYSITE_ID#\" #CHECKED#>#PAYSITE#</option>", $personal_site_id);
                  else echo $default->AllPaysitesToString("<option value=\"#PAYSITE_ID#\">#PAYSITE#</option>");
                  ?>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Рост: </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="height" <?php if ($height) echo "value='" . $height . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Размер (сиськи/член): </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="size" <?php if ($size) echo "value='" . $size . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4" colspan="2" style="text-align: center;">
                Выбрать главное изображение<br />
                <div style="margin:10px; float: left;" id="select_vertic_tumb"
                  onclick="get_model_images(<?= $id ?>, 'vertic')">
                  <?php $model_img = $models->getMainVerticImageUrl();
                  if (!$model_img || !isset($model_img['url'])) echo 'No image selected';
                  else { ?><img src="<?= $model_img['url'] ?>" /><?php } ?>
                </div>
                <div style="margin:10px; float: left;" id="select_horiz_tumb"
                  onclick="get_model_images(<?= $id ?>, 'horiz')">
                  <?php $model_img = $models->getMainHorizImageUrl();
                  if (!$model_img || !isset($model_img['url'])) echo 'No image selected';
                  else { ?><img src="<?= $model_img['url'] ?>" /><?php } ?>
                </div>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Выбрать главное изображение: </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="info" <?php if ($info) echo "value='" . $info . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">ID Изображения с галлереи: </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="picture" <?php if ($picture) echo "value='" . $picture . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Закачать с компа: </td>
              <td bgcolor="#e4e4e4">
                <input name="file" size="80" type="file" />
                <select name="layout" id="layout" style="float: right;">
                  <option value="vertic">Вертикальная</option>
                  <option value="horiz">Горизонтальная</option>
                </select>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Закачать с URL: </td>
              <td bgcolor="#e4e4e4">
                <input name="model_image_url" size="28" value="" />
                <select name="layout" id="layout" style="float: right;">
                  <option value="vertic">Вертикальная</option>
                  <option value="horiz">Горизонтальная</option>
                </select>
              </td>
            </tr>


            <tr>
              <td bgcolor="#e4e4e4">Твиттер</td>
              <td bgcolor="#e4e4e4">
                <input size="45" name="twitter" <?php if ($twitter) echo "value='" . $twitter . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Фейсбук</td>
              <td bgcolor="#e4e4e4">
                <input size="45" name="facebook" <?php if ($facebook) echo "value='" . $facebook . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Инфо: </td>
              <td bgcolor="#e4e4e4">
                <input size="30" name="info" <?php if ($info) echo "value='" . $info . "'"; ?>>
              </td>
            </tr>
            <tr>
              <td bgcolor="#e4e4e4">Псевдонимы: </td>
              <td bgcolor="#e4e4e4">
                <div id='pseudonims'>
                  <?php
                  if (isset($id) && $id) {
                    if ($pseudonims && is_array($pseudonims) && count($pseudonims) > 0) {
                      foreach ($pseudonims as $idX => $pseudonim) {
                        $pseudonim = str_replace("'", "&#39;", $pseudonim);
                  ?>
                        <div id="pseudonim<?= $idX ?>" style="width:100%; height: 34px; display: block-inline;">
                          <dd style="float: left; padding: 5px; margin: 2px;"><input size="30"
                              name='inputPseudonim<?= $idX ?>' id='inputPseudonim<?= $idX ?>'
                              onkeyup="preChangePseudonim(<?= $idX ?>)" value='<?= $pseudonim ?>'></dd>
                          <dd style="float: left; padding: 0; margin: 2px;"><img src="images/button_red_minus.png"
                              border=0 onclick="removePseudonim(<?= $idX ?>);" /></dd>
                        </div>
                    <?php
                      }
                    }
                    ?>
                    <div id="addPseudonim0" style="width:100%; height: 34px; display: block-inline;">
                      <dd style="float: left; padding: 5px; margin: 2px; display: block;"><input size="30"
                          name='addPseudonimName0' id='addPseudonimName0' value=''></dd>
                      <dd style="float: left; padding: 0; margin: 2px; display: block;"><img
                          src="images/add_button_small.png" border=0 onclick="addPseudonim(<?= $id ?>);" />
                      </dd>
                    </div>
                  <?php
                  } else {
                  ?>
                    <div id="addPseudonim0" style="width:100%; height: 34px; display: block-inline;">
                      <dd style="float: left; padding: 5px; margin: 2px; display: block;"><input size="30"
                          name='addPseudonimName0' id='addPseudonimName0' value=''></dd>
                      <dd style="float: left; padding: 0; margin: 2px; display: block;"><img
                          src="images/add_button_small.png" border=0 onclick="addPseudonim_newModel();" />
                      </dd>
                    </div>
                  <?php
                  }
                  ?>
                </div>
              </td>
            </tr>
          </table>

          <?php
          if (isset($_GET['id']) && intval($_GET['id'])) {
          ?>
            <div style="float: left; color: red;">Удалить модель <input type="checkbox" id="deletemodel"
                value="<?= intval($_GET['id']) ?>" name="deletemodel"></div>
          <?php
          }
          ?>
          <input type="submit" <?php if (isset($_GET['id'])) { ?> onclick="return checkModelDelete(<?= $_GET['id'] ?>)"
            <?php } ?> value="<?php if ($id) { ?>Изменить модель<?php } else { ?>Добавить модель<?php } ?>"
            name="<?php if ($id) { ?>updateModel<?php } else { ?>addModel<?php } ?>" />
        </div>
      </div>
      <div style="clear: both;"></div>
    </form>

    <?php
    if (isset($_GET['id'])) {
      $modelGals = $models->getModelGals();
      if (count($modelGals) > 0) {
    ?>
        <br />
        Список галлерей где есть модель:<br /><br />
        <?php
        $modelSeparatorFlag = false;
        if (is_array($modelGals) && count($modelGals)) {
          foreach ($modelGals as $gallery) {
            if ($modelSeparatorFlag) echo ", ";
            else $modelSeparatorFlag = true;
        ?>
            <a href="./index.php?act=galleries&amp;galid=<?= $gallery ?>"><?= $gallery ?></a>
      <?php
          }
        }
      }
    }
    if (isset($_GET['id'])) {
      ?>
      <form enctype="multipart/form-data"
        action="<?= $_SERVER['SCRIPT_NAME'] . "?act=models&id=" . $_GET['id'] . "&search=" . $_GET['id'] ?>" method="post">
        <br />
        <hr /><br />
        Найти все возможные галеры с участием модели, исключая уже добавленные: <input type="submit" value="Поиск">
        <br />
        <hr /><br />
      </form>
      <form enctype="multipart/form-data"
        action="<?= $_SERVER['SCRIPT_NAME'] . "?act=models&id=" . $_GET['id'] . "&search=" . $_GET['id'] . "&pseudo=true" ?>"
        method="post">
        <br />
        <hr /><br />
        Найти все возможные галеры ТОЛЬКО С ПСЕВДОНИМАМИ модели, исключая уже добавленные: <input type="submit"
          value="Поиск">
        <br />
        <hr /><br />
      </form>
      <br />
      <?php
    }
    if (isset($id, $_REQUEST['search']) && intval($_REQUEST['search']) && $id && (intval($_REQUEST['search']) == $id)) {

      if ($sex == 'male') $niche = 'Gay';
      elseif ($sex == 'shemale') $niche = 'Shemale';
      else $niche = 'Straight';

      $gal_worker = new Galleries($db->_db);

      if (isset($_REQUEST['pseudo']) && $_REQUEST['pseudo'] == true) {
        $pseudo_search = true;
        $galleries = $gal_worker->getGalleriesList_pseudoSearch(intval($_GET['id']), $niche);
      } else {
        $pseudo_search = false;
        $galleries = $gal_worker->getGalleriesList('asc', 'id', 300, false, false, false, false, false, false, 'titledesc', $niche, intval($_REQUEST['search']));
      }

      if (is_array($galleries) && count($galleries) > 0) {
        if ($pseudo_search) echo "Поиск по псевдонимам:<br />";
      ?>
        Найдено <?= $gal_worker->getCurrentGlasCount() ?>:<br /><br />
        <?php
        $modelSeparatorFlag = false;
        foreach ($galleries as $galId => $gallery) {
          if ($gallery['type'] == 'Movies') $thumbUrlPre = HOSTING . "/thumbs/m/320";
          else $thumbUrlPre = HOSTING . "/thumbs/p/180";
          $thumbId = $gallery['image'];
          if ($thumbId < 256000) {
            $folderId = (int)ceil($thumbId / 1000);
            $folder = "1/" . $folderId;
          } else {
            $mainFolder = (int)ceil($thumbId / 256000);
            $folderId = (int)ceil($thumbId / 1000);
            $folder = $mainFolder . "/" . $folderId;
          }
          $thumbURL = $thumbUrlPre . "/" . $folder . "/" . $thumbId . ".jpg";
        ?>
          <div
            style="margin:6px; padding: 5px; width: 1300px; height: 26px; border: 1px #000 solid; display: block-inline; text-align: left;">
            <div style='float:left; width: 800px; height: 24px; display: block-inline; overflow: hidden; '>
              <input type="button" id="showGalleryButton<?= $galId ?>" value="Показать все тумбы"
                onclick="showGalleryThumbs(<?= $galId ?>,'<?= $gallery['type'] ?>');">
              <strong>ID:</strong> <a onmouseover="over('<?= $thumbURL ?>')" onmousemove="move(event)" onmouseout="out()"
                href="./index.php?act=galleries&galid=<?= $galId ?>"><?= $galId ?></a> |
              <strong>Платник:</strong> <?= $gallery['paysite'] ?>
              <strong>Тайтл:</strong> <?= $gallery['title'] ?>
            </div>
            <div style='float:right;'>
              <input type="button" value="Добавить" id="addModelToGallery<?= $galId ?>"
                onclick="addModelToGallery(<?= $galId ?>,<?= $id ?>);">
              <input type="button" value="Удалить" id="deleteModelFromGallery<?= $galId ?>"
                onclick="deleteModelFromGallery(<?= $galId ?>,<?= $id ?>);" style="display: none;">
            </div>

          </div>
          <div style="margin:6px; margin-top: 1px; margin-bottom: 25px; padding: 5px; width: 1300px; height: auto; border: 1px #000 solid; display: none; text-align: left;"
            name="addModelBlock" id="addModelBlock<?= $galId ?>">

          </div>

        <?php
        }
      } else {
        ?>
        Галеры не найдены.
    <?php
      }
    }
  } else {

    $modelCounter = 0;
    $model_ids = false;
    // var_dump($_GET['eyes']);

    $site_id = isset($_REQUEST['site_id']) ? $_REQUEST['site_id'] : false;
    $by_sex = isset($_REQUEST['sex']) ? $_REQUEST['sex'] : false;
    $by_eyes_color = isset($_REQUEST['eyes_color']) ? $_REQUEST['eyes_color'] : false;
    $by_body_type = isset($_REQUEST['body_type']) ? $_REQUEST['body_type'] : false;
    $by_hair_color = isset($_REQUEST['hair_color']) ? $_REQUEST['hair_color'] : false;
    $by_ethnic = isset($_REQUEST['ethnic']) ? $_REQUEST['ethnic'] : false;
    $by_first_letter  = isset($_REQUEST['first_letter']) ? $_REQUEST['first_letter'] : false;
    ?>
    <form enctype="multipart/form-data" action="index.php?<?= http_build_query($_GET) ?>" method="post" id="model_filter">
      <select name="sex">
        <option value="0">Sex</option>
        <?php
        foreach ($sex_array as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_sex) ? ' selected' : false; ?>><?= $e_c ?></option>
        <?php   }
        ?>
      </select>
      <select name="eyes_color">
        <option value="0">Eyes</option>
        <?php
        foreach ($eyes_colors_array as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_eyes_color) ? ' selected' : false; ?>><?= $e_c ?></option>
        <?php   }
        ?>
      </select>
      <select name="body_type">
        <option value="0">Body</option>
        <?php
        foreach ($body_types_array as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_body_type) ? ' selected' : false; ?>><?= $e_c ?></option>
        <?php   }
        ?>
      </select>
      <select name="hair_color">
        <option value="0">Hair</option>
        <?php
        foreach ($hair_colors_array as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_hair_color) ? ' selected' : false; ?>><?= $e_c ?></option>
        <?php   }
        ?>
      </select>
      </select>
      <select name="ethnic">
        <option value="0">Ethnic</option>
        <?php
        foreach ($ethnics_array as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_ethnic) ? ' selected' : false; ?>><?= $e_c ?></option>
        <?php   }
        ?>
      </select>
      <select name="first_letter">
        <option value="0">Letter</option>
        <?php
        foreach (range('a', 'z') as $e_c) { ?>
          <option value="<?= $e_c ?>" <?= ($e_c == $by_first_letter) ? ' selected' : false; ?>><?= ucfirst($e_c) ?></option>
        <?php   }
        ?>
      </select>

      <select name="sort">
        <option value="name">По имени</option>
        <option value="id">По ID</option>
      </select>
      <select name="order">
        <option value="asc">A-Z</option>
        <option value="desc">Z-A</option>
      </select>
      <input type="submit" value="Filter" name="filterModels" />
    </form>
    <?php

    $models->setSearchFilter($modelSex, $by_eyes_color, $by_body_type, $by_hair_color, $by_ethnic, $by_first_letter, $site_id);


    $sort = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : 'name';
    $order = isset($_REQUEST['order']) ? $_REQUEST['order'] : 'asc';
    $thumb_size = 'medium';

    $models_info = $models->getModelsList($modelSex, true, $model_ids, $thumb_size, $order);

    if ($models_info && is_array($models_info) && count($models_info)) {
      $sources = new Sources($db->_db);
      foreach ($models_info as $model) {
        $paysite = $model['personal_site_id'] ? $sources->getSource($model['personal_site_id']) : false;
        $thumbId = $model['picture'];
        // $thumbURL = HOSTING. $models->getMainImageUrl();



        if (isset($_GET['thumbs']) && $_GET['thumbs'] == 'true') {
          // var_dump($model);
          $thumbURL = HOSTING . $model['image_url'];
    ?>
          <a href="index.php?act=models&amp;id=<?= $model['id_model'] ?>">
            <div
              style="margin:6px; padding: 3px; width: 186px; height: <?= $site_id ? 295 : 276 ?>px; border: 1px #000 solid; display: block-inline; float:left; text-align: center;">
              <img src="<?= $thumbURL ?>" style="border: solid 1px #000;">
              <?= $model['name'] ?><br />
              <?php if ($site_id) { ?>
                Галер на сайте: <?= $model['total_count'] ?>
                <br>Pageviews:<?= $model['pageviews'] ?>, Likes:<?= $model['likes'] ?>

              <?php } else {
              ?>
                <?= $models->getModelGalsCount($model['id_model']) ?> | ID: <?= $model['id_model'] ?>
              <?php
              } ?>
            </div>
          </a>
        <?php
        } else {
          $thumbURL = HOSTING . $model['image_url'];
          $modelCounter++;
        ?>
          <div style="margin:6px; padding: 5px; width: 1200px; height: 18px; border: 1px #000 solid; display: block-inline;">
            <div style='float:left;'>
              <a onmouseover="over('<?= $thumbURL ?>')" onmousemove="move(event)" onmouseout="out()"
                href="<?= $_SERVER['SCRIPT_NAME'] ?>?act=models&id=<?= $model['id_model'] ?>">Edit model</a> |
              id модели: <?= $model['id_model'] ?>
              Имя: <?= $model['name'] ?> |
              пол: <strong><?= $model['sex'] ?></strong>
              снимается: <?= $model['active'] ?>
              роль: <strong><?= $model['role'] ?></strong>
              цвет волос: <?= $model['hair'] ?>
              дата рождения: <?= $model['birth'] ?>
            </div>
            <div style='float:right'>
              Платник:
              <?= $model['personal_site_id'] ? "<a href=\"index.php?act=paysites&amp;siteid=" . $model['personal_site_id'] . "&amp;edit\">" . $paysite['name'] . "</a>" : 'Нет' ?>,
              Галер: <strong><?= $models->getModelGalsCount($model['id_model']) ?></strong>
            </div>
          </div>

    <?php
        }
      }
    }
    ?>
    <div style="clear: both;"></div>
    <div class="menu" style="margin: 20px; display: block-inline; width: 100%; height: auto; padding: 4px;">
      >> <a href="<?= $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'] ?>&query=add">Добавить модель</a>
    </div>
<?php
  }
}
echo $models->formattedListing('#TAG_NAME#|#TAG_URL_NAME#<br>', 'male');
?>