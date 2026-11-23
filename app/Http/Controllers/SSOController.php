<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SSOController extends Controller
{
    // Method minimal untuk bypass error
    public function redirect()
    {
        return redirect(
            'https://DARWINBOX-URL/sso?redirect=' .
            urlencode(route('sso.login'))
        );
    }

    
    public function callback()
    {
        return redirect('/dashboard');
    }
}