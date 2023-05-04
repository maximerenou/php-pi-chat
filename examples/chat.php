<?php
require __DIR__ . '/../vendor/autoload.php';

\MaximeRenou\PiChat\Tools::$debug = false; // Set true for verbose

$chatbot = new \MaximeRenou\PiChat\Client();

$conversation = $chatbot->createConversation();

echo 'Type "q" to quit' . PHP_EOL;

while (! $conversation->ended()) {
    echo PHP_EOL . "> ";

    $text = rtrim(fgets(STDIN));

    if ($text == 'q')
        break;

    $prompt = new \MaximeRenou\PiChat\Prompt($text);

    echo "- ";

    try {
        $full_answer = $conversation->ask($prompt, function ($answer, $tokens) {
            echo $tokens;
        });
    }
    catch (\Exception $exception) {
        echo "Sorry, something went wrong: {$exception->getMessage()}.";
    }
}

exit(0);
