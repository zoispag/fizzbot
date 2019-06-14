<?php

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

class FizzBot
{
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.noopschallenge.com/fizzbot/questions/',
        ]);
    }

    private function getNextQuestion(string $url): array
    {
        $response = $this->client->request('GET', $url);

        if ($response->getStatusCode() === 200) {
            if (strstr($response->getHeaderLine('content-type'), 'application/json')) {
                $json = json_decode($response->getBody());

                echo $json->message . "\n";

                return [
                    $json->rules ?? [],
                    $json->numbers ?? [],
                ];
            }
        }
    }

    private function sendAnswer(string $url, string $answer): array
    {
        echo "\n👉🏻 Answer is: {$answer} \n\n ------------ \n\n";
        $response = $this->client->post($url, [
            'json'  => ["answer" => $answer]
        ]);

        if ($response->getStatusCode() === 200) {
            if (strstr($response->getHeaderLine('content-type'), 'application/json')) {
                $json = json_decode($response->getBody());
                return $json->nextQuestion
                    ? [true, str_replace('/fizzbot/questions/', '', $json->nextQuestion)]
                    : [false, ''];
            }
        }
    }

    private function calculate(array $rules, array $numbers): string
    {
        if (empty($rules) && empty($numbers)) {
            return "PHP";
        }

        foreach ($rules as $r) {
            $applyRules[$r->number] = $r->response;
        }

        $answer = '';
        foreach ($numbers as $n) {
            $part = '';
            $answer .= strlen($answer) ? ' ' : '';
            foreach ($rules as $r) {
                $part .= ($n % $r->number === 0) ? $r->response : '';
            }
            $answer .= (strlen($part) ? $part : $n);
        }

        return $answer;
    }

    public function play()
    {
        $resume = true;
        $q = '1'; # https://api.noopschallenge.com/fizzbot/questions/1
        while ($resume) {
            list($rules, $numbers) = $this->getNextQuestion($q);
            list($resume, $q)  = $this->sendAnswer($q, $this->calculate($rules, $numbers));
        }

        echo "Well done!! \n\n";
    }
}

$bot = new FizzBot();
$bot->play();