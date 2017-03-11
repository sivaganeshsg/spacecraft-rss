<?php
class helper
{

	public static function redirect($url)
    {
    	header("Location: $url");
    }
}