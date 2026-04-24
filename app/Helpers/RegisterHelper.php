<?php

use App\Models\Register;

if (!function_exists('getOpenRegister')) {
    /**
     * Get the currently open register for the authenticated user
     * 
     * @return Register|null
     */
    function getOpenRegister()
    {
        return Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->first();
    }
}

if (!function_exists('hasOpenRegister')) {
    /**
     * Check if user has an open register
     * 
     * @return bool
     */
    function hasOpenRegister()
    {
        return Register::where('user_id', auth()->id())
            ->whereNull('closed_at')
            ->exists();
    }
}

if (!function_exists('getActiveRegisterId')) {
    /**
     * Get the active register ID for the authenticated user
     * 
     * @return int|null
     */
    function getActiveRegisterId()
    {
        $register = getOpenRegister();
        return $register ? $register->id : null;
    }
}
