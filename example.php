<?php

require 'vendor/autoload.php';

// get credentials from https://trello.com/app-key
$exporter = new \Ujamii\OpenMetrics\Trello\TrelloExporter('<TRELLO_API_KEY>', '<TRELLO_API_TOKEN>', '<TRELLO_ORG>');
$exporter->run();
