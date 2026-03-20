<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

// Retorna l'ID de l'usuari autenticat (des del filtre auth)
if (!function_exists('user_id')) {
    function user_id(): ?int
    {
        $request = service('request');
        return $request->usuariId ?? null;
    }
}
