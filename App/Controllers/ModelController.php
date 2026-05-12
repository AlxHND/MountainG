<?php

namespace App\Controllers;

use App\Helpers\View;

class ModelController {

    public function index()
    {
        echo "index";
    }

    public function create()
    {
        $data = [
            'title' => 'Добро пожаловать в MVC!',
            'message' => 'Это простой пример перехода на MVC на PHP.',
        ];

        View::render('models.create', $data);
    }
}