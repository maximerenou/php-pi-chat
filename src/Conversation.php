<?php

namespace MaximeRenou\PiChat;

use EventSource\Event;
use EventSource\EventSource;

class Conversation
{
    // Conversation IDs
    public $conversation_id;
    public $cookie;

    // Conversation data
    protected $current_started;
    protected $current_text;
    protected $ended = false;

    public function __construct($identifiers = null)
    {
        if (is_array($identifiers) && ! empty($identifiers['cookie']))
            $this->cookie = $identifiers['cookie'];

        if (is_array($identifiers) && ! empty($identifiers['conversation_id']))
            $this->conversation_id = $identifiers['conversation_id'];

        if (! is_array($identifiers)) {
            $identifiers = $this->initConversation();
            Tools::debug("initConversation identifiers", $identifiers);
        }

        $this->cookie = $identifiers['cookie'];
        $this->conversation_id = $identifiers['conversation_id'];
    }

    public function getIdentifiers()
    {
        return [
            'cookie' => $this->cookie,
            'conversation_id' => $this->conversation_id,
        ];
    }

    public function initConversation()
    {
        $headers = [
            'method: POST',
            'accept: application/json',
            'X-Api-Version: 3',
            "referer: https://pi.ai/onboarding",
            'content-type: application/json',
        ];

        if (! empty($this->cookie)) {
            $headers[] = "cookie: {$this->cookie}";
        }

        $data = json_encode([]);

        list($data, $request, $url, $cookies, $cookie_string) = Tools::request("https://pi.ai/api/chat/start", $headers, $data, true);
        $data = json_decode($data, true);

        Tools::debug("initConversation result", $data);

        if (! is_array($data) || empty($data['mainConversation']))
            throw new \Exception("Failed to init conversation");

        return [
            'conversation_id' => $data['mainConversation']['sid'],
            'cookie' => $cookie_string,
        ];
    }

    public function ask(Prompt $message, $callback = null)
    {
        $this->current_text = '';

        $es = new EventSource("https://pi.ai/api/chat");

        $data = [
            'conversation' => $this->conversation_id,
            'text' => $message->text
        ];

        Tools::debug("ask", $data);

        $es->setCurlOptions([
            CURLOPT_HTTPHEADER => [
                'method: POST',
                'Accept: text/event-stream',
                'Accept-Encoding: gzip, deflate, br, zstd',
                'Accept-Language: en-US;q=0.9,en;q=0.8',
                "Referer: https://pi.ai/discover",
                "Origin: https://pi.ai",
                'Content-Type: application/json',
                'X-Api-Version: 3',
                "Cookie: {$this->cookie}",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36"
            ],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $packets = [];

        $es->onMessage(function (Event $event) use (&$callback, &$packets) {
            $packets[] = $event->data;

            $this->handlePacket($event->data, function ($message) use (&$callback) {
                if ($message === false || empty($message['text']))
                    return;

                $this->current_text .= $message['text'];

                if (! is_null($callback)) {
                    $callback($this->current_text, $message['text']);
                }
            });
        });

        try {
            $es->connect();
        }
        catch (\Exception $exception) {
            Tools::debug("Failed to connect", $exception->getMessage());
        }

        // Handle abort
        if (empty($this->current_text)) {
            $this->ended = true;
            $this->current_text = "I'm sorry, please start a new conversation!";

            if (! is_null($callback)) {
                $callback($this->current_text, $this->current_text);
            }
        }

        Tools::debug("Packets", $packets);

        return $this->current_text;
    }

    protected function handlePacket($raw, $callback)
    {
        $packets = Tools::splitJsonStrings($raw);

        foreach ($packets as $packet) {
            $data = json_decode($packet, true);

            if ($data) {
                $callback($data);
            }
        }
    }

    public function ended()
    {
        return $this->ended;
    }
}
