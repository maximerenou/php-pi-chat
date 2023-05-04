<?php

namespace MaximeRenou\PiChat;

use EventSource\Event;
use EventSource\EventSource;

class Conversation
{
    // Conversation IDs
    public $cookie;

    // Conversation data
    protected $current_started;
    protected $current_text;
    protected $ended = false;

    public function __construct($identifiers = null)
    {
        if (is_array($identifiers) && ! empty($identifiers['cookie']))
            $this->cookie = $identifiers['cookie'];

        if (! is_array($identifiers))
            $identifiers = $this->initConversation();

        $this->cookie = $identifiers['cookie'];
    }

    public function getIdentifiers()
    {
        return [
            'cookie' => $this->cookie
        ];
    }

    public function initConversation()
    {
        $headers = [
            'method: POST',
            'accept: application/json',
            'x-api-version: 2',
            "referer: https://heypi.com/talk",
            'content-type: application/json',
        ];

        if (! empty($this->cookie)) {
            $headers[] = "cookie: __Host-session={$this->cookie}";
        }

        $data = json_encode([]);

        list($data, $request, $url, $cookies) = Tools::request("https://heypi.com/api/chat/start", $headers, $data, true);
        $data = json_decode($data, true);

        if (! empty($cookies['__Host-session'])) {
            $this->cookie = $cookies['__Host-session'];
        }

        if (! is_array($data) || empty($data['latestMessage']))
            throw new \Exception("Failed to init conversation");

        return [
            'cookie' => $this->cookie
        ];
    }

    public function ask(Prompt $message, $callback = null)
    {
        $this->current_text = '';

        $es = new EventSource("https://heypi.com/api/chat");

        $data = ['text' => $message->text];

        $es->setCurlOptions([
            CURLOPT_HTTPHEADER => [
                'method: POST',
                'accept: text/event-stream',
                'Accept-Encoding: gzip, deflate, br',
                "referer: https://heypi.com/talk",
                'content-type: application/json',
                "cookie: __Host-session={$this->cookie}"
            ],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $es->onMessage(function (Event $event) use (&$callback) {
            $message = $this->handlePacket($event->data);

            if ($message === false || empty($message['text']))
                return;

            $tokens = substr($message['text'], strlen($this->current_text));
            $this->current_text = $message['text'];

            if (! is_null($callback)) {
                $callback($this->current_text, $tokens);
            }
        });

        @$es->connect();

        // Handle abort
        if (empty($this->current_text)) {
            $this->ended = true;
            $this->current_text = "I'm sorry, please start a new conversation!";

            if (! is_null($callback)) {
                $callback($this->current_text, $this->current_text);
            }
        }

        return $this->current_text;
    }

    protected function handlePacket($raw)
    {
        if (! preg_match('/(\{"text".+\})/', $raw, $matches)) {
            return false;
        }

        $data = json_decode($matches[1], true);

        if (! $data) {
            return false;
        }

        return $data;
    }

    public function ended()
    {
        return $this->ended;
    }
}
