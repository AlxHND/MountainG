<?php

            $url_addition = '';

            $userId = isset($_GET['uid']) ? (int)$_GET['uid'] : false;
            
            if(!isset($users)) {
                $users = new Users($db->_db);
            }


            $galleries = new Galleries($db->_db);
            
            
            if (isset($_GET['type']) && $_GET['type'] == 'updates') {
                $type = 'updates';
                $url_addition = '&type=updates';
            } else {
                $type = 'crop';
            }

            $year = (isset($_GET['year']) && intval($_GET['year']) >= 2012 && intval($_GET['year']) <= date('Y')) ? intval($_GET['year']) : date('Y');

            if (isset($_GET['month']) && (intval($_GET['month']) >= 1 && intval($_GET['month']) <= 12 )) {
                if (isset($_GET['day']) && (intval($_GET['day']) >=1 && intval($_GET['day'] <= 31))) {
                    
                    echo "<a href='{$_SERVER['SCRIPT_NAME']}?act=users&amp;uid=".$_GET['uid']."&amp;q=history&amp;year=".$year."&amp;month=".$_GET['month'].$url_addition."'>Back to month stats</a><br /><br />";
                    $daysHistory = $users->dayHistory($userId, $year,$_GET['month'], $_GET['day'], $type);

                    if ($daysHistory) {
                        //var_dump($daysHistory);
                        if (is_array($daysHistory['galleries'])) $galleriesCount = count($daysHistory['galleries']);
                        else $galleriesCount = 0;
                        if ($galleriesCount !== 0){
                            foreach ($daysHistory['galleries'] as $gallery) {
                                // var_dump($daysHistory,$gallery['gal_id']);
                                $gallery = $galleries->getMainGalleryInfo($gallery['gal_id']);

                                // var_dump($gallery ); die;
                                ?>

                                <div style="clear: both;"></div>                                
                                 <div style="margin:6px; padding: 5px; width: 1300px; height: 26px; border: 1px #000 solid; display: block-inline; text-align: left;">
                                  <div style='float:left; width: 800px; height: 24px; display: block-inline; overflow: hidden; '>
                                    <?php if ($user_type == 'admin') { ?>
                                        <input type="button" id="showGalleryButton<?=$gallery['id']?>" value="Показать все тумбы" onclick="showGalleryThumbs_crop(<?=$gallery['id']?>,'<?=$gallery['type']?>')  ;">
                                    <?php } ?>
                                    <strong>ID:</strong> <a href="<?=$_SERVER['SCRIPT_NAME']?>?act=galleries&amp;galid=<?=$gallery['id']?>"><?=$gallery['id']?></a> | 
                                    <strong>Тайтл:</strong> <?=$gallery['title']?>
                                    <?php if ($user_type == 'admin') { ?>
                        				<?php
                        					if ($type == 'crop' && $galleries->ifGalleryRecropped($gallery['id'])) echo "<strong>Рекроплена!</strong>";
                        				?>
                                    <?php } ?>
                                  </div>

                                    <?php if ($user_type == 'admin') { ?>
                                      <div style='float:right;'>
                                        <input type="button" value="Галеру в рекроп" id="galleryToRecrop<?=$gallery['id']?>" onclick="gallery_to_recrop(<?=$gallery['id']?>);">
                                      </div>
                                    <?php } ?>
                                </div>
                                <div style="margin:6px; margin-top: 1px; margin-bottom: 25px; padding: 5px; width: 1300px; height: auto; border: 1px #000 solid; display: none; text-align: left;" name="addModelBlock" id="addModelBlock<?=$gallery['id']?>">
                                </div>                  
                                <div style="display: none; width: 100%; border: 1px solid #000; height: auto;" id="gal_info_<?=$gallery['id']?>">
<?php
                                //echo " <a href='{$_SERVER['SCRIPT_NAME']}?act=galleries&amp;galid=".$gallery['id']."'>".$gallery['id']."</a> | ";
                                if ($type == 'crop') {
                                  //if ($galleries->ifGalleryRecropped($gallery['id'])) echo "<strong>Рекроплена!</strong>";
//                                  echo "<br>";
//                                  $crop_results = $users->getGalleryCropInfo($gallery['id']);
                                 /* foreach ($crop_results as $res) {
                                    //var_dump($galleries->getAllImagesWithCropInfo($gallery['id']));
                                    echo "Thumb Id:".$res['image_id'].", x:".$res['x_coord'].", y:".$res['y_coord'].", w:".$res['width'].", h:".$res['height'].", updated:".date("Y-d-m, h:i",$res['updated'])."<br>";
                                  } */
                                } else {
                                  if ($users->isAdmin() && $gallery_history = $userAuth->galleryUpdateHistory($gallery['id'])) { ?>
                                    <?php 
                                        $tags = $galleries->getTags($gallery['id'], true);
                                        if ($tags && is_array($tags)) {
                                          foreach ($tags as $tag_id => $tag_name) {
?>
                                            <div class="catt"><?=$tag_name?></div>
<?php
                                          }
                                        }
?>
                                        <div style="clear:both;"></div>
<?php                                        
                                        foreach ($gallery_history as $history_item) {
                                          echo date ("Y-m-d H-i",$history_item["updated"]). ": Тип изменения: <b>".$history_item["change_type"]."</b>";
                                          if ($history_item["item_id"]) echo ", Id измененного объекта: ".$history_item["item_id"];
                                          echo " | Работник #<b>".$history_item["user_id"]."</b><br>";
                                          
                                        }
                                  }         
                                }
?>                               
                                </div>                                 
                                <?php
                                echo "<br>"; echo "<br>";
                            }
                        } else {
                            echo "No galleries cropped this day<br>";
                        }


                    } else echo "No history for period<br>";
                } else {
                    echo "<a href='{$_SERVER['SCRIPT_NAME']}?act=users&amp;uid=".$userId."&amp;q=history&amp;year=" .$year. $url_addition."'>Back to year stat</a><br /><br />";
                    $monthHistory = $users->monthHistory($userId, $year,$_GET['month'], $type);
                    if ($monthHistory) {
                        foreach ($monthHistory as $day) {
                            if (is_array($day['galleries'])) {
                                $galleriesCount = count($day['galleries']);
                                echo "<a href='{$_SERVER['SCRIPT_NAME']}?act=users&amp;uid=".$userId."&amp;q=history&amp;year=".$day['year']."&amp;month=".$day['month']."&amp;day=".$day['day'].$url_addition."'>".$day['year'] ."/".$day['month']."/". $day['day']. "</a> : ".$galleriesCount."<br>";
                            } else {
                                $galleriesCount = 0;
                                echo $day['year'] ."/".$day['month']."/". $day['day']. " : ".$galleriesCount."<br>";
                            }
                            //var_dump($day['galleries']);
                        }

                    } else echo "No history for period<br>";
                }
            } else {
                echo "<a href='xacropper/'>Back to crop</a><br /><br />";
                $yearHistory = $users->yearHistory($userId, $year, $type);
                if ($yearHistory) {
                    foreach ($yearHistory as $month) {
                        if (is_array($month['galleries'])) {
                            $galleriesCount = count($month['galleries']);
                            echo "<a href='{$_SERVER['SCRIPT_NAME']}?act=users&amp;uid=".$userId."&amp;q=history&amp;year=".$month['year']."&amp;month=".$month['month'].$url_addition."'>".$month['year'] ."/".$month['month']."</a> : ".$galleriesCount."<br>";
                        } else {
                            $galleriesCount = 0;
                            echo $month['year'] ."/".$month['month']. " : ".$galleriesCount."<br>";
                        }
                    }
                } else echo "No history for period<br>";
            }