# Exporter for trello data in prometheus format

This package uses the Trello [REST API](https://developers.trello.com/v1.0/reference) to query for some statistics
and outputs them in [OpenMetrics](https://github.com/OpenObservability/OpenMetrics) format, which can be scraped by
[Prometheus](https://prometheus.io/).

For easier deployment we also provide a [Docker container](#with-docker).

## API Keys
For authentication at the REST API this tool needs a pair of API keys, which you can create by signing into Trello
and visiting [trello.com/app-key](https://trello.com/app-key)

## Usage

You can either include this library into you own code, or run it as a container.

### with Composer
**Installation**

```shell
composer req ujamii/prometheus-trello-exporter
```

**Usage in your custom file**

```php
require 'vendor/autoload.php';

$exporter = new \Ujamii\OpenMetrics\Trello\TrelloExporter('<TRELLO_API_KEY>', '<TRELLO_API_TOKEN>', '<TRELLO_ORG>');
$exporter->run();
```

### with Docker

The image is based on `php:7.2-apache` and thus exposes data on port 80 by default. Assuming you fire this up with
`-p 80:80` on localhost, you can see the metrics at [http://localhost/metrics](http://localhost/metrics).

Configuration is done with three environment variables: `TRELLO_API_KEY` and `TRELLO_API_TOKEN` for authentication and
an optional `TRELLO_ORG` variable if you want to query the boards from an organization instead of the boards from the
user.

```shell
docker run -d --name trello-prometheus -e TRELLO_API_KEY=verylongfoobarkey -e TRELLO_API_TOKEN=foobarlongtoken -p "80:80" ujamii/prometheus-trello-exporter
```

Get the prebuild image [Docker Hub](https://hub.docker.com/r/ujamii/prometheus-trello-exporter)

### with Docker Compose

```bash
cp docker-compose.example.yml docker-copose.yml
vim docker-compose.yml #add env variables
docker-compose up -d
```

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
