<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/';   
    $classPath = str_replace('\\', '/', $class);
    
    $file = $baseDir . $classPath . '.php';
    if (file_exists($file)) {
        require $file;
    } else {
        throw new Exception("Класс [$class] не найден: $file");
    }
});
