<?php

namespace App\Controllers;

class Home extends BaseController
{
    /**
     * Mostra la vista principal de l'aplicació.
     *
     * @return string Valor textual obtingut o generat pel mètode.
     */
    public function index(): string
    {
        return view('welcome_message');
    }
}
