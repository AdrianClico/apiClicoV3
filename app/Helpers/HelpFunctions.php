<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class HelpFunctions
{
    public static function logData($data, $type)
    {
        $log = "_______________________________________________________________________________________________________________________________ \n ";

        switch ($type) {
            case "string":
                $log .= $data . "\n";
                break;
            case "array":
                $log .= print_r($data, true) . "\n";
                break;
            case "object":
                ob_start();
                var_dump($data);
                $log .= ob_get_clean() . "\n";
                break;
        }

        $log .= "_______________________________________________________________________________________________________________________________ \n ";

        Storage::append("request.log", $log);
    }

    /**
     * Verifica el token de reCAPTCHA v2.
     */
    public static function verificarRecaptcha(string $token, string $secret): bool
    {
        $res = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token
        ]);

        return $res->json('success') === true;
    }

    /**
     * Separa un nombre completo en firstname y lastname.
     */
    public static function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);
        return [
            'firstname' => $parts[0],
            'lastname' => $parts[1] ?? ''
        ];
    }
}
