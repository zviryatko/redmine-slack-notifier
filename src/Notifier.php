<?php
/**
 * @file
 * Contains zviryatko\RedmineSlackNotifier\Notifier.
 */

namespace zviryatko\RedmineSlackNotifier;

use CL\Slack\Exception\SlackException;
use CL\Slack\Payload\AuthTestPayload;
use CL\Slack\Payload\AuthTestPayloadResponse;
use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Payload\ChatPostMessagePayloadResponse;
use Redmine\Client as RedmineClient;
use CL\Slack\Transport\ApiClientInterface as SlackClient;

/**
 * Send live notification to Slack chat based on redmine working hours.
 *
 * @package zviryatko\RedmineSlackNotifier
 * @author Alex Davyskiba <sanya.davyskiba@gmail.com>
 */
class Notifier implements NotifierInterface
{
    /**
     * Redmine API client.
     *
     * @var \Redmine\Client
     */
    protected $redmine_client;

    /**
     * Slack API client.
     *
     * @var \CL\Slack\Transport\ApiClientInterface
     */
    protected $slack_client;

    /**
     * @param \Redmine\Client $redmine
     * @param \CL\Slack\Transport\ApiClientInterface $slack
     */
    public function __construct(RedmineClient $redmine, SlackClient $slack)
    {
        $this->redmine_client = $redmine;
        $this->slack_client = $slack;
    }

    /**
     * {@inheritdoc}
     */
    public function redmineUserId()
    {
        $data = $this->redmine_client->user->getCurrentUser();
        if (!isset($data['user']) && !isset($data['user']['id'])) {
            throw new NotifierException("Can't access to redmine api, check credentials or external url.");
        }

        return $data['user']['id'];
    }

    /**
     * {@inheritdoc}
     */
    public function totalWorkedHours($user_id)
    {
        $total = 0;
        $data = $this->redmine_client->time_entry->all(['spent_on' => 't', 'user_id' => $user_id]);
        if ($data === false) {
            throw new NotifierException('Redmine api returns nothing, possibly something went wrong.');
        }
        foreach ($data['time_entries'] as $time_entry) {
            $total += $time_entry['hours'];
        }
        return $total;
    }

    /**
     * {@inheritdoc}
     */
    public function slackUsername()
    {
        try {
            $payload = new AuthTestPayload();
            /** @var AuthTestPayloadResponse $response */
            $response = $this->slack_client->send($payload);
            if (!$response->isOk()) {
                throw new NotifierException(
                    sprintf('Slack api error: %s (%s).', $response->getError(), $response->getErrorExplanation())
                );
            }
            return $response->getUsername();
        } catch (SlackException $exception) {
            throw new NotifierException("Can't access to slack api, check credentials.", null, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function notifySlackUser($from, $to, $message)
    {
        try {
            $payload = new ChatPostMessagePayload();
            $payload->setChannel("@{$to}");
            $payload->setUsername($from);
            $payload->setText($message);
            /** @var ChatPostMessagePayloadResponse $response */
            $response = $this->slack_client->send($payload);
            if (!$response->isOk()) {
                throw new NotifierException(
                    sprintf("Can't send message to slack user: %s (%s)", $response->getError(),
                        $response->getErrorExplanation())
                );
            }
            return true;
        } catch (SlackException $exception) {
            throw new NotifierException("Can't access to slack api, check credentials.", null, $exception);
        }
    }
}