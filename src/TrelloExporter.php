<?php

namespace Ujamii\OpenMetrics\Trello;

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\HttpResponse;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;

/**
 * Class TrelloExporter
 * @package Ujamii\OpenMetrics\Trello
 */
class TrelloExporter {

    /**
     * TrelloExporter constructor.
     *
     * @param string $apiKey
     * @param string $apiToken
     */
    public function __construct(string $apiKey, string $apiToken)
    {
        $client = new \Stevenmaguire\Services\Trello\Client([
            'key' => $apiKey,
            'token'  => $apiToken,
        ]);

        $boards = $client->getCurrentUserBoards(['filter' => 'open']);

        $gaugesTotal = GaugeCollection::withMetricName(MetricName::fromString('trello_cards_in_list_total'))->withHelp('Number of cards per list.');
        $gaugesLabels = GaugeCollection::withMetricName(MetricName::fromString('trello_labeled_cards_on_board'))->withHelp('Number of cards per board and label.');

        foreach($boards as $board) {
            if ($board->closed) {
                continue;
            }

            $labelsOnBoard = [];
            $lists = $client->getBoardLists($board->id, ['filter' => 'open', 'cards' => 'open']);
            foreach ($lists as $list) {
                if ($list->closed) {
                    continue;
                }

                $gaugesTotal->add(
                    Gauge::fromValue((float) count($list->cards))->withLabels(
                        Label::fromNameAndValue('board', $board->name),
                        Label::fromNameAndValue('list', $list->name)
                    )
                );

                foreach ($list->cards as $card) {
                    foreach ($card->labels as $label) {
                        if (empty($label->name)) {
                            continue;
                        }

                        if (!isset($labelsOnBoard[$label->name])) {
                            $labelsOnBoard[$label->name] = 0;
                        }
                        $labelsOnBoard[$label->name]++;
                    }
                }
            }

            foreach ($labelsOnBoard as $labelName => $labelCount) {
                $gaugesLabels->add(
                    Gauge::fromValue((float) $labelCount)->withLabels(
                        Label::fromNameAndValue('board', $board->name),
                        Label::fromNameAndValue('label', $labelName)
                    )
                );
            }

        }

        HttpResponse::fromMetricCollections($gaugesTotal, $gaugesLabels)->withHeader('Content-Type', 'text/plain; charset=utf-8')->respond();
    }

    public function run()
    {

    }

}


