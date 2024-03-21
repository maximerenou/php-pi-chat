<?php

namespace MaximeRenou\PiChat;

class Tools
{
    public static $debug = false;

    public static function debug($message, $data = null)
    {
        if (self::$debug) {
            echo "[DEBUG] $message" . PHP_EOL;

            if (! empty($data)) {
                echo "[DEBUG DATA] " . print_r($data, true) . PHP_EOL;
            }
        }
    }

    public static function request($url, $headers = [], $data = null, $return_request = false)
    {
        $request = curl_init();
        
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($request, CURLOPT_URL, $url);
        curl_setopt($request, CURLOPT_HTTPHEADER, array_merge([
            'accept-language: en-US,en;q=0.9',
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/112.0.0.0 Safari/537.36',
            "origin: https://pi.ai",
        ], $headers));

        if (! is_null($data)) {
            curl_setopt($request, CURLOPT_POST, 1);
            curl_setopt($request, CURLOPT_POSTFIELDS, $data);
        }

        if ($return_request) {
            curl_setopt($request, CURLOPT_HEADER, 1);
        }

        $data = curl_exec($request);
        $url = curl_getinfo($request, CURLINFO_EFFECTIVE_URL);
        $header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
        curl_close($request);

        if ($return_request) {
            $headers = substr($data, 0, $header_size);
            $body = substr($data, $header_size);

            preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $headers, $matches);
            $cookies = [];

            foreach ($matches[1] as $item) {
                list($key, $value) = explode('=', $item);
                $cookies[$key] = $value;
            }

            self::debug("URL: $url");
            self::debug("Cookies: " . implode(',', array_keys($cookies)));
            self::debug("Body: $body");

            return [$body, $request, $url, $cookies, implode(';', $matches[1])];
        }

        return $data;
    }

    public static function splitJsonStrings($jsonString) 
    {
        // Dear ChatGPT, thank you for this code snippet.
        $results = [];
        $depth = 0;
        $str = '';
        $inString = false;

        for ($i = 0; $i < strlen($jsonString); $i++) {
            $char = $jsonString[$i];

            if ($char === '"' && ($i === 0 || $jsonString[$i - 1] !== '\\')) {
                $inString = ! $inString;
            }

            $str .= $char;

            if (! $inString) {
                if ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;
                }
            }

            if ($depth === 0 && ! $inString) {
                $results[] = trim($str);
                $str = '';
            }
        }

        return $results;
    }
}