# Pi chatbot client

[![License](https://img.shields.io/github/license/mashape/apistatus.svg)](https://opensource.org/licenses/MIT)
[![Latest Stable Version](https://img.shields.io/github/v/release/maximerenou/php-pi-chat)](https://packagist.org/packages/maximerenou/pi-chat)
[![PHP version](https://img.shields.io/packagist/dependency-v/maximerenou/pi-chat/php)](https://packagist.org/packages/maximerenou/pi-chat)
[![cURL extension required](https://img.shields.io/packagist/dependency-v/maximerenou/pi-chat/ext-curl)](https://packagist.org/packages/maximerenou/pi-chat)

This is an unofficial PHP client for **Pi** chatbot (from **Inflection AI**). It doesn't require authentication.

> This package isn't actively maintained and may be unstable due to Inflection AI's Cloudflare configuration.

> This package is stricly meant for educational purpose. Use at your own risks.

## Installation

    composer require maximerenou/pi-chat

## Demo

Clone this repo, run `composer install` and run `examples/chat.php` to test it.

![Prompt Demo](examples/demo.gif)

## Usage

```php
use MaximeRenou\PiChat\Client as PiChat;
use MaximeRenou\PiChat\Prompt;

$chatbot = new PiChat();

$conversation = $chatbot->createConversation();

// $answer - full answer
$answer = $conversation->ask(new Prompt("Hello World"));
```

<details>
  <summary>Real-time / progressive answer</summary>

You may pass a function as second argument to get real-time progression:

```php
// $current_answer - incomplete answer
// $tokens - last tokens received
$final_answer = $conversation->ask($prompt, function ($current_answer, $tokens) {
    echo $tokens;
});
```

</details> 

<details>
  <summary>Resume a conversation</summary>  

If you want to resume a previous conversation, you can retrieve its identifiers:

```php
// Get current identifiers
$identifiers = $conversation->getIdentifiers();

// ...
// Resume conversation with $identifiers parameter
$conversation = $chatbot->resumeConversation($identifiers);
```

</details> 

<details>
  <summary>Error handling</summary>

The code throws exceptions when it receives an error from Pi. You can therefore use a try/catch block to handle errors.

</details>

---------------------------------------

#### Disclaimer

This code is for educational purpose only. Using Pi outside pi.ai may violate Inflection AI terms.
