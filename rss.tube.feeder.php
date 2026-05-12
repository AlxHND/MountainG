<?php

  function durationToSeconds($duration) { /* получает время в виде 00:00:00, возвращает unix time */
    if (strpos($duration, ":")) {
      $duration = explode(":", $duration);
      if (count($duration) == 3) {
        $duration = $duration[0]*3600 + $duration[1]*60 + $duration[2];
      } else {
        $duration = $duration[0]*60 + $duration[1];
      }
    } else $duration = (int)$duration;
    return $duration;
  }

// Tube RSS feeder
$reader = new XMLReader();
if ($reader->open("http://partners.xhamster.com/2export.php?ch=.106.115.80&pr=1&cnt=1&tmb=7&tcnt=8&vid=on&url=on&em=2&ttl=on&chs=on&sz=on&dt=on&dlm=%7C&fr=1")) {
      $prevLink = false;
      $niche = 'gay';
      $counter = 0;
      $elements = array();
      while ($reader->read()) {
        if ($reader->name == 'item') {
          $categories = array();
          $tags = array();
          $thumb = array();
          $cat = array();
          $tag = array();
          $duration = false;
          $id = false;
          $embed =false;
          $duration = false;
          $niche = false;
          $pubDate = false;
          $node = $reader->expand();
          $dom = new DomDocument();
          $n = $dom->importNode($node,true);
          $dom->appendChild($n);
          $title = $dom->getElementsByTagName("title");
          $link = $dom->getElementsByTagName("link");
          $description = $dom->getElementsByTagName("description");  // в description хрпнится вся основная инфа из rss хамстера
          if ($title->length && $link->length && $description->length) {
            $title = $title->item(0)->nodeValue;
            $link =  $link->item(0)->nodeValue;
            $description =  $description->item(0)->nodeValue;

            $elements[$counter]['link'] = $link;
          	$elements[$counter]['title'] = $title;
            if($prevLink == false) $prevLink = $elements[$counter]['link'];
            if ($prevLink != $elements[$counter]['link']) {
              $prevLink = $elements[$counter]['link'];
              $counter++;
            }
          	$description = explode ("<br>", $description);

            foreach ($description as $element) { // разбивка информации от хамстера
              $element = trim($element);
              if ($element !== ""){
                if (preg_match("#Embed[\s]*\:[\s]*(.*?)$#im", $element, $match)) { // Код Эмбеда
                  $elements[$counter]['embed'] = $match[1];
                } elseif (preg_match("#<img.*?src[\s]*=[\s]*[\"\'\s]{0,}([^\"^\'^\>]*?\.jpe?g)[\"\'\s]{0,}.*?>#im",$element,$match)) { // тумба (она одна в ряд у хамстера)
                  $elements[$counter]['thumb'][] = $match[1];
                } elseif (preg_match("#id\:([0-9]{1,})#im",$element,$match)) { // id галлереи
                  $elements[$counter]['id'] = $match[1];
                } elseif (preg_match("#Channels[\s]*\:[\s]*(.*?)$#im",$element,$match)) { // теги указанные в галере (обычно Men и Gays)
                	$tags_out = array();
                	$tags = explode(";", $match[1]);
                	foreach ($tags as $tag_name) {
                		$tag_name = trim($tag_name, "(Gay)");
                		$tag_name = trim($tag_name);
                		$tags_out[] = $tag_name;
                	}
                  $elements[$counter]['tag'] = $tags_out;
                }  elseif (preg_match("#Duration[\s]*\:[\s]*([0-9hms\:]*?)$#im",$element,$match)) { // duration 00h00m00s->00:00:00
                  $duration = $match[1];
                  $duration = str_replace("h",":",$duration);
                  $duration = str_replace("m",":",$duration);
                  $duration = str_replace("s","",$duration);
                  $elements[$counter]['duration'] = durationToSeconds($duration);
                } elseif (preg_match("#Added[\s]*\:[\s]*([0-9\-]*?)$#im",$element,$match)) { // когда добавлена
                  $elements[$counter]['pubDate'] = $match[1];
                }
              }
            }          
            for ($i = 0; $i < count($tag); $i++) {
              if (!in_array($tag[$i], $cat)) $elements[$counter]['cat'][] = strtolower($tag[$i]);
            }
            
            
          }
        }
      }
      if($elements) {
      	foreach ($elements as $element) {
      		if (isset(
                    $element['link'], $element['title'], $element['embed'], $element['id'], $element['duration'], $element['pubDate'], $element['thumb'], $element['tag']
                    )
            ) {
               echo $element['link']."|".$element['title']."|".$element['embed']."|".$element['id']."|".$element['duration']."|".$element['pubDate']."|".implode(",", $element['thumb'])."|".implode(",", $element['tag'])."\n";
            }
      	}
      }
    } else {
      echo "no0";
    }
?>