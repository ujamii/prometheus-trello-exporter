<?php

namespace Ujamii\OpenMetrics\Trello;

use OpenMetricsPhp\Exposition\Text\Collections\GaugeCollection;
use OpenMetricsPhp\Exposition\Text\HttpResponse;
use OpenMetricsPhp\Exposition\Text\Metrics\Gauge;
use OpenMetricsPhp\Exposition\Text\Types\Label;
use OpenMetricsPhp\Exposition\Text\Types\MetricName;
use Stevenmaguire\Services\Trello\Client;

/**
 * Class TrelloExporter
 * @package Ujamii\OpenMetrics\Trello
 */
class TrelloExporter
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $organizationId;

    /**
     * TrelloExporter constructor.
     *
     * @param string $apiKey
     * @param string $apiToken
     * @param string $organizationId
     */
    public function __construct(string $apiKey, string $apiToken, string $organizationId = '')
    {
        $this->client         = new Client([
            'key'   => $apiKey,
            'token' => $apiToken,
        ]);
        $this->organizationId = $organizationId;
    }

    /**
     * @return void
     */
    public function run(): void
    {
        if ('' !== $this->organizationId) {
            $boards = $this->client->getOrganizationBoards($this->organizationId, ['filter' => 'open']);
        } else {
            $boards = $this->client->getCurrentUserBoards(['filter' => 'open']);
        }

        $gaugesTotal   = GaugeCollection::withMetricName(MetricName::fromString('trello_cards_in_list_total'))->withHelp('Number of cards per list.');
        $gaugesLabels  = GaugeCollection::withMetricName(MetricName::fromString('trello_labeled_cards_on_board'))->withHelp('Number of cards per board and label.');
        $gaugesMembers = GaugeCollection::withMetricName(MetricName::fromString('trello_cards_per_board_member'))->withHelp('Number of cards per board and member.');

        foreach ($boards as $board) {
            if ($board->closed) {
                continue;
            }

            $cardsPerMember = [];
            $labelsOnBoard  = [];
            $lists          = $this->client->getBoardLists($board->id, ['filter' => 'open', 'cards' => 'open']);
            foreach ($lists as $list) {
                if ($list->closed) {
                    continue;
                }

                $gaugesTotal->add(
                    Gauge::fromValue((float)count($list->cards))->withLabels(
                        Label::fromNameAndValue('board', $board->name),
                        Label::fromNameAndValue('list', $list->name)
                    )
                );

                foreach ($list->cards as $card) {
                    foreach ($card->labels as $label) {
                        if (empty($label->name)) {
                            continue;
                        }
                        if ( ! isset($labelsOnBoard[$label->name])) {
                            $labelsOnBoard[$label->name] = 0;
                        }
                        $labelsOnBoard[$label->name]++;
                    }

                    foreach ($card->idMembers as $memberId) {
                        if ( ! isset($cardsPerMember[$memberId])) {
                            $cardsPerMember[$memberId] = 0;
                        }
                        $cardsPerMember[$memberId]++;
                    }
                }
            }

            foreach ($labelsOnBoard as $labelName => $labelCount) {
                $gaugesLabels->add(
                    Gauge::fromValue((float)$labelCount)->withLabels(
                        Label::fromNameAndValue('board', $board->name),
                        Label::fromNameAndValue('label', $labelName)
                    )
                );
            }

            $members = $this->client->getBoardMembers($board->id);
            foreach ($members as $member) {
                $gaugesMembers->add(
                    Gauge::fromValue((float)$cardsPerMember[$member->id])->withLabels(
                        Label::fromNameAndValue('board', $board->name),
                        Label::fromNameAndValue('member', $member->fullName)
                    )
                );
            }
        }

        HttpResponse::fromMetricCollections($gaugesTotal, $gaugesLabels, $gaugesMembers)->withHeader('Content-Type', 'text/plain; charset=utf-8')->respond();
    }
}
