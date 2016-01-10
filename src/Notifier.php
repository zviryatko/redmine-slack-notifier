<?php
/**
 * @file
 * Contains zviryatko\RedmineSlackNotifier\Notifier.
 */

namespace zviryatko\RedmineSlackNotifier;

use CL\Slack\Payload\ChatPostMessagePayload;
use CL\Slack\Payload\UsersListPayload;
use Redmine\Client as RedmineClient;
use CL\Slack\Transport\ApiClientInterface as SlackClient;
use Psr\Log\LoggerInterface;

/**
 * Send live notification to Slack chat based on redmine working hours.
 *
 * @package zviryatko\RedmineSlackNotifier
 */
class Notifier implements NotifierInterface
{
    /**
     * Working day hours.
     */
    const WORKING_DAY = 8;

    /**
     * The logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

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

    protected $cache;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Redmine\Client $redmine
     */
    public function __construct(LoggerInterface $logger, RedmineClient $redmine, SlackClient $slack)
    {
        $this->logger = $logger;
        $this->redmine_client = $redmine;
        $this->slack_client = $slack;
    }

    /**
     * {@inheritdoc}
     */
    public function notify(array $users)
    {
        $time_entry_users = [];
        $data = $this->redmine_client->time_entry->all(['spent_on' => 't']);
        if ($data['total_count'] > 0) {
            foreach ($data['time_entries'] as $time_entry) {
                $name = $time_entry['user']['name'];
                $hours = $time_entry['hours'];
                $time_entry_users[$name] = !isset($time_entry_users[$name]) ? $hours : $time_entry_users[$name] + $hours;
            }
        }
        $users = array_filter($users, function ($user) use ($time_entry_users) {
            if (!isset($time_entry_users[$user])) {
                return true;
            }
            return $time_entry_users[$user] < Notifier::WORKING_DAY;
        });
        $payload = new UsersListPayload();
        $response = $this->slack_client->send($payload);
        $slack_users = [];
        if ($response->isOk() && method_exists($response, 'getUsers')) {
            foreach ($response->getUsers() as $user) {
                /** @var \CL\Slack\Model\User $user */
                /** @var \CL\Slack\Model\UserProfile $profile */
                $profile = $user->getProfile();
                $slack_users[$profile->getRealName()] = $user->getName();
            }
        } else {
            $this->logger->error($response->getError());
            $this->logger->error($response->getErrorExplanation());
        }
        $users = array_filter($users, function ($user) use ($slack_users) {
            return isset($slack_users[$user]);
        });
        $self = $this;
        array_map(function ($user) use ($self, $slack_users) {
            $payload = new ChatPostMessagePayload();
            $payload->setChannel("@{$slack_users[$user]}");
            $payload->setUsername('Redminder');
            $payload->setText(':redminder:Redmine - log in time.');
            $response = $this->slack_client->send($payload);
            if (!$response->isOk()) {
                $this->logger->error($response->getError());
                $this->logger->error($response->getErrorExplanation());
            }
        }, $users);
    }
}