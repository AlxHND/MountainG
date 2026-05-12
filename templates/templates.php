<?php
define("IS_MOBILE", 1);
define("NO_MOBILE", 0);

$sites = new Templates($db->_db);

if (isset($_POST['addTemplate']) || isset($_POST['updateTemplate'])) {
  $siteId = (int)$_POST['site'];
  $name = $_POST['name'];
  $text = trim($_POST['text']);
  $mobile = (int)$_POST['mobile'];
  $type = (int)$_POST['type'];
  $sub_template = trim($_POST['subtemplate']);

  if (isset($_POST['addTemplate'])) {
    if ($templateId = $sites->addTemplate($siteId, $name, $mobile, $type, $text, $sub_template)) {
      echo "Темплейт " . $templateId . " добавлен<br />";
      $template = $sites->rebuildTemplate($templateId);
      //echo $template;
      $templateKey = SCRIPT_PRE . "_" . $siteId . "_" . $templateId;
      $memcache = new Memcache;
      if ($memcache->connect('66.254.98.162', 11211)) {
        $template = htmlspecialchars_decode($template);
        $memcache->set($templateKey, $template, 0, 3000);
        $a = $memcache->get($templateKey);
        print_r($a);
      }
      unset($template);
    } else {
      echo "Темплейт не добавлен<br />";
    }
  } elseif (isset($_POST['updateTemplate']) && isset($_GET['id']) && $sites->getTemplate($_GET['id'])) {
    if ($sites->updateTemplate($_GET['id'], $siteId, $name, $mobile, $type, $text, $sub_template)) {
      echo "Темплейт " . $_GET['id'] . " изменен<br />";
      $template = $sites->rebuildTemplate($_GET['id']);
      // echo $template;
      $templateKey = SCRIPT_PRE . "_" . $siteId . "_" . $_GET['id'];
      // $memcache = new Memcache;  
      // if ($memcache->connect('66.254.98.162', 11211)) {
      //     $template = htmlspecialchars_decode($template);
      //     $memcache->set($templateKey, $template, 0, 30);
      //     $a = $memcache->get($templateKey);
      //     print_r($a);
      // }
      unset($template);
    } else {
      echo "Темплейт не добавлен<br />";
    }
  }
  /*
    $paysiteId = $_POST['paysite'];
    $text = trim($_POST['text']);
    $specialLink = trim($_POST['specialLink']);
    if (isset($_REQUEST['addBanner'])) {
      if ($bannerId = $banners->addBanner($url, $paysiteId, $text, $specialLink, true)) {
        echo $bannerId . " добавлен<br />";
      } else {
        echo "Баннер не добавлен<br />";
      }

    }
    */
  /*    
          $paysiteId = $_POST['paysite'];
          $width = $_POST['width'];
          $height = $_POST['height'];
          $role = $_POST['role'];
          $hair = $_POST['hair'];
          $birth = $_POST['birth'];
          $body = $_POST['body'];
          $personal_site_id = $_POST['paysite'];
          $height = $_POST['height'];
          $size = $_POST['size'];
          $picture = $_POST['picture'];
          $info = $_POST['info']; 
    if (isset($_POST['addModel'])) {
      $models->addModel($name, $sex, $hair, $body, $active, $birth, $height, $size, $role, $info, $personal_site_id, $picture);
    } elseif (isset($_POST['updateModel']) && isset($_GET['id']) && $models->switchModel($_GET['id'])) {
      $models->updateModel($name, $sex, $hair, $body, $active, $birth, $height, $size, $role, $info, $personal_site_id, $picture);
    } 
    */
}
//$name = 'Cody Cummings';
//$sex = 'male';
//$hair = 'brunette';
//$body = 'athletic';
//$model->addModel($name, $sex, $hair, $body, $active = 'yes', $birth = '1981-05-03', $height = 1752, $size=203, $role='top', $info='', $personal_site_id = 183, $picture=0);
//$models->switchModel(1);
//$models->updatePicture(135649);
if ((isset($_GET['query']) && $_GET['query'] == 'add') || (isset($_GET['id']))) {
  if (isset($_GET['id']) && $sites) {
    $template = $sites->getTemplate($_GET['id']);
    $id = $_GET['id'];
    $siteId = $template['site_id'];
    $siteName = $default->SiteInformation($siteId);
    $siteName = $siteName['name'];
    $templateName =  $template['name'];
    $mobile = (int)$template['mobile'];
    $type = (int)$template['type'];
    $text = $template['template'];
    $date = date("Y-m-d", $template['changed_on']);
    $sub_template = $template['sub_template'];
  } else {
    $id = false;
    $siteId = false;
    $siteName = false;
    $siteName = false;
    $templateName = false;
    $mobile = false;
    $type = false;
    $date = false;
    $text = false;
  }
  $sitesList = $default->SitesGetAll();
?>
  <form enctype="multipart/form-data" action="<?= $_SERVER['SCRIPT_NAME'] . "?" . $_SERVER['QUERY_STRING'] ?>" method="post">
    <div align="center">
      <table class="disclaim" cellpadding="2" cellspacing="2" border="0">
        <tr>
          <td bgcolor="#e4e4e4" align="left">
            Название темплейта:
          </td>
          <td bgcolor="#e4e4e4">
            <input size="30" name="name"
              value="<?php if (isset($templateName) && $templateName !== false) echo $templateName; ?>">
          </td>
        </tr>
        <?php
        if (isset($date) && $date) {
        ?>
          <tr>
            <td bgcolor="#e4e4e4" align="left">
              Изменен:
            </td>
            <td bgcolor="#e4e4e4">
              <?= $date ?>
            </td>
          </tr>
        <?php
        }
        ?>
        <tr>
          <td bgcolor="#e4e4e4">Сайт: </td>
          <td bgcolor="#e4e4e4">
            <select name="site">
              <?php
              foreach ($sitesList as $site) {
              ?>
                <option value="<?= $site['id'] ?>" <?php if ($siteId === $site['id']) echo "selected"; ?>>
                  <?= $site['name'] ?></option>
              <?php
              }
              ?>
            </select>
          </td>
        </tr>
        <tr>
          <td bgcolor="#e4e4e4">Тип: </td>
          <td bgcolor="#e4e4e4">
            <select name="type">
              <option value="1" <?php if ($type !== false && $type == IS_MOBILE) echo "selected"; ?>>Movies
              </option>
              <option value="0" <?php if ($type !== false && $type == NO_MOBILE) echo "selected"; ?>>Pics
              </option>
            </select>
          </td>
        </tr>

        <tr>
          <td bgcolor="#e4e4e4">Мобильный: </td>
          <td bgcolor="#e4e4e4">
            <select name="mobile">
              <option value="1" <?php if ($mobile !== false && $mobile == IS_MOBILE) echo "selected"; ?>>
                Мобильный</option>
              <option value="0" <?php if ($mobile !== false && $mobile == NO_MOBILE) echo "selected"; ?>>Не
                мобильный</option>
            </select>
          </td>
        </tr>

        <tr>
          <td bgcolor="#e4e4e4" align="left">
            Текст:
          </td>
          <td bgcolor="#e4e4e4">
            <textarea rows="15" cols="90"
              name="text"><?php if (isset($text) && $text !== false) echo $text; ?></textarea>
          </td>
        </tr>
        <tr>
          <td bgcolor="#e4e4e4" align="left">
            Саб-темплейт:
          </td>
          <td bgcolor="#e4e4e4">
            <textarea rows="5" cols="90"
              name="subtemplate"><?php if (isset($sub_template) && $text !== false) echo $sub_template; ?></textarea>
          </td>
        </tr>
      </table>
      <input type="submit" value="<?php if (isset($id)) { ?>Изменить баннер<?php } else { ?>Добавить баннер<?php } ?>"
        name="<?php if (isset($id) && $id) { ?>updateTemplate<?php } else { ?>addTemplate<?php } ?>" />
    </div>
  </form>
  <?php
} else {
  $allTemplates = $sites->getAllTemplates();
  if (is_array($allTemplates)) {
    foreach ($sites->getAllTemplates() as $id) {
      $id = $id['id'];
      $template = $sites->getTemplate($id);
      $siteId = $template['site_id'];
      $siteName = $default->SiteInformation($siteId);
      $siteName = $siteName['name'];
      $templateName =  $template['name'];
      $mobile = $template['mobile'] ? "Мобильный" : "Простой";
      $type = $template['type'] ? "Movies" : "Pics";
      $date = date("Y-m-d", $template['changed_on']);
  ?>
      <div style="margin:6px; padding: 5px; width: 1200px; height: 18px; border: 1px #000 solid; display: block-inline;">
        <div style='float:left;'>
          <a href="<?= $_SERVER['SCRIPT_NAME'] ?>?act=templates&id=<?= $id ?>">Edit template</a>
          -> ID: <strong><?= $id ?></strong> | <?= $templateName ?> | <?= $siteName ?> |
          <strong><?= $mobile ?></strong>, <strong><?= $type ?></strong>
        </div>
        <div style='float:right'>
          <?= $date ?>
        </div>
      </div>

<?php
    }
  }
}
?>
<div class="menu">
  >> <a href="<?= $_SERVER['SCRIPT_NAME'] ?>?act=templates&query=add">Add new template</a>
  <br />
  <hr>
</div>