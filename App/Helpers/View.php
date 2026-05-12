<?php

namespace App\Helpers;

class View
{
    /**
     * Рендеринг представления
     *
     * @param string $view Имя представления (например, "home.index")
     * @param array $data Данные для передачи в представление
     * @return void
     */
    public static function render(string $view, array $data = [])
    {
        $viewPath = __DIR__ . "/../../resources/views/" . str_replace('.', '/', $view) . ".php";

        if (!file_exists($viewPath)) {
            throw new \Exception("View [$view] не найдено в пути: $viewPath");
        }
        extract($data);


        require $viewPath;
    }
}