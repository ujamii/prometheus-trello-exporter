# Exporter for trello data in prometheus format

This package uses the [Trello](https://trello.com/) REST [api](https://developers.trello.com/v1.0/reference) to query for some statistics and 
outputs them in [OpenMetrics](https://github.com/OpenObservability/OpenMetrics) format to be scraped by [prometheus](https://prometheus.io/).

You can also fire it up as a [docker container](#with-docker).

## Usage

In both cases, you will need the api key and an auth token, which you can create via
`https://trello.com/app-key`

### with Composer

**Installation**

```shell
composer req ujamii/prometheus-trello-exporter
```

**Usage in your custom file**

```php
require 'vendor/autoload.php';

$exporter = new \Ujamii\OpenMetrics\Trello\TrelloExporter('<TRELLO_API_KEY>', '<TRELLO_API_TOKEN>');
$exporter->run();
```

### with Docker

The image is based on `php:7.2-apache` and thus exposes data on port 80 by default. Assuming you fire this up with `-p 80:80` on localhost,
you can see the metrics on http://localhost/metrics.

Configuration is done with 2 env variables: `TRELLO_API_KEY` and `TRELLO_API_TOKEN`.

```shell
docker run -d --name trello-prometheus -e TRELLO_API_KEY=verylongfoobarkey -e TRELLO_API_TOKEN=foobarlongtoken -p "80:80" ujamii/prometheus-trello-exporter
```

View on [Docker Hub](https://hub.docker.com/r/ujamii/prometheus-trello-exporter)

## Output

The script will generate something like:

```
# TYPE trello_cards_in_list_total gauge
# HELP trello_cards_in_list_total Number of cards per list.
trello_cards_in_list_total{board="FooBar Board", list="To Do"} 2.000000
trello_cards_in_list_total{board="FooBar Board", list="In Progress"} 0.000000
trello_cards_in_list_total{board="FooBar Board", list="Done"} 16.000000
# TYPE trello_labeled_cards_on_board gauge
# HELP trello_labeled_cards_on_board Number of cards per board and label.
trello_labeled_cards_on_board{board="FooBar Board", label="on hold"} 2.000000
trello_labeled_cards_on_board{board="FooBar Board", label="late"} 1.000000
trello_labeled_cards_on_board{board="FooBar Board", label="needs input"} 5.000000
# TYPE trello_cards_per_board_member gauge
# HELP trello_cards_per_board_member Number of cards per board and member.
trello_cards_per_board_member{board="FooBar Board", member="John Doe"} 3.000000
trello_cards_per_board_member{board="FooBar Board", member="Jane Doe"} 13.000000
...
```