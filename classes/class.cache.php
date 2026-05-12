<?php
class cache {
    private $cache;
    private $siteId;

    function __construct ($siteId = false) {
    	//echo "constr";
        if ($siteId === false) {
            if (defined('SITE_ID')) $this->siteId = SITE_ID;
            else $error = true;
        } else $this->siteId = intval($siteId);
        if (isset($error)) {
            $log = new Logger (__CLASS__ .", ". __METHOD__ ." Нет коннекта к memcached, SITE_ID и siteId одновременно не установлены", true);
            $this->cache = false;
        } else {
            $this->cache = new Memcache();
            if (!@$this->cache->connect('66.254.98.34', CACHE_PORT)) {
                $log = new Logger (__CLASS__ .", ". __METHOD__ ." Нет коннекта к memcached", true);
                $this->cache = false;
            }
        }
        //var_dump($this->siteId);
    }

    private function getKeyName($type, $id, $page = 0) {
        if ($page <= 1) $page = "";
        else $page = "_page:". $page;
        $key = md5(SCRIPT_PRE. "_". $this->siteId . "_all_new_".$type.":" . $id . $page); // собирается ключ для хранения
        return $key;
    }
    private function is_cacheType ($type) {
        if (preg_match('/^(gallery|banner|paysite|content|models|model_gals|tags|models_list|models_list_array|sources_list|tags_list|sources|sources_gals|ip_stoplist|template)$/', $type)) return true;
        elseif (preg_match('/^(models_list_array_(amber|blue|brown|gray|green|hazel)?_?(american|arab|asian|ebony|euro|indian|latin)?_?(skinny|thin|slim|athletic|muscular|bodybuilder|chubby|fat)?_?(bald|blond|brown|brunette|gray|red|white)?)/im', $type)) {
            //echo "Array ok";
            return true;
        }
        else return false;
    }

    public function get ($type, $id, $page = 0) {
        $content = false;
        $id = intval($id);
        $page = intval($page);
        if ($page > 1) $pageLog = " страница ".$page;
        else $pageLog = "";
        if ($this->cache !== false) {
            if ($this->is_cacheType($type)) {
                $key = $this->getKeyName($type, $id, $page);
                $content = $this->cache->get($key);
                if ($content == NULL) {
                    $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id .$pageLog." нет в кэше");
                }
            } else $log = new Logger (__CLASS__ .", ". __METHOD__ ." ".$type.", ".$id . $pageLog." ошибка типа кэшируемого объекта или id == 0|false", true);
        } else {
            $log = new Logger (__CLASS__ .", ". __METHOD__ ." Попытка вытащить из кэша данные при отсутствии коннекта к Мемкешу ", true);
        }
        return $content;
    }

    public function set ($type, $id, $content, $expiration = false, $page = 0) {
        $result = false;
        $id = intval($id);
        if ($page > 1) $pageLog = " страница ".$page;
        else $pageLog = "";
        if ((int)$expiration) $expiration = intval($expiration);
        elseif (defined('CACHE_EXPIRATION')) $expiration = CACHE_EXPIRATION;
        else $expiration = 36600;
        if (is_array($content)) $content = serialize ($content);
        elseif (is_object($content)) $content = false; // нельзя передавать объект, только строка и числа
        if ($this->cache !== false && $content !== false) {
            if ($this->is_cacheType($type)) {
                $key = $this->getKeyName($type, $id, $page);
                //var_dump($key);
                if (!$this->cache->set($key, $content,0, $expiration)) {
                    $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id .$pageLog." не добавлено в кэш", true);
                } else {
                    $result = true;
                    //var_dump($result);
                    if ($expiration < 0) $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id .$pageLog." сброшен");
                }
            }
        } else {
            $log = new Logger (__CLASS__ .", ". __METHOD__ ." Попытка добавить в кэш данные при отсутствии коннекта к Мемкешу ", true);
        }       
        return $result;
    }

    public function add ($type, $id, $content, $expiration = false, $page = 0) {
        $result = false;
        $id = intval($id);
        if ($page > 1) $pageLog = " страница ".$page;
        else $pageLog = "";
        if ((int)$expiration) $expiration = intval($expiration);
        elseif (defined('CACHE_EXPIRATION')) $expiration = CACHE_EXPIRATION;
        else $expiration = 36600;
        if (is_array($content)) $content = serialize ($content);
        elseif (is_object($content)) $content = false; // нельзя передавать объект, только строка и числа
        if ($this->cache !== false && $content !== false) {
            if ($this->is_cacheType($type)) {
                $key = $this->getKeyName($type, $id, $page);
                if (!$this->cache->add($key, $content,0, $expiration)) {
                    $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id .$pageLog." не возможно вставить в кэш, уже существует", true);
                } else {
                    $result = true;
                    if ($expiration < 0) $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id .$pageLog." сброшен");
                }
            }
        } else {
            $log = new Logger (__CLASS__ .", ". __METHOD__ ." Попытка добавить в кэш данные при отсутствии коннекта к Мемкешу ", true);
        }       
        return $result;
    }

    public function increment ($type, $id) {
        $result = false;
        $id = intval($id);
        if ($this->cache !== false) {
            if ($id && $this->is_cacheType($type)) {
                $key = $this->getKeyName($type, $id);
                $result = $this->cache->increment($key);
                if ($result === false)  $log = new Logger ("Ключ \"".$key."\": ".$type.", ".$id ." не возможно увеличить в кэше", true);
            }
        } else {
            $log = new Logger (__CLASS__ .", ". __METHOD__ ." Попытка изменить данные в кэше при отсутствии коннекта к Мемкешу ", true);
        }       
        return $result;        
    }

    public function reset ($type, $id, $page = 0) {
        if ($this->set ($type, $id, 0, -1, $page)) return true;
        else return false;
    }

    public function checkIPstoplist($ip) {
        $ip = ip2long($ip);
        $result = false;
        if ($ip && $this->cache !== false) {
            $type = 'ip_stoplist';
            $ipCounter = 0;
            $ipCounter = $this->get($type,$ip);
            if (!$ipCounter) {
                $res = $this->set($type,$ip,1,20);
            } else {
                $ipCounter = $this->increment($type,$ip);
                if ($ipCounter >= 15) {
                    $result = true;
                } 
            }
        }
        return $result;
    }

    public function connected() {
        if ($this->cache) return true;
        else return false;
    }
}