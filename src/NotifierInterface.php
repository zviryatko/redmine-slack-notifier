<?php
/**
 * @file
 * Contains zviryatko\RedmineSlackNotifier\NotifierInterface.
 */

namespace zviryatko\RedmineSlackNotifier;

/**
 * Send live notification to Slack chat based on redmine working hours.
 *
 * @package zviryatko\RedmineSlackNotifier
 * @author Alex Davyskiba <sanya.davyskiba@gmail.com>
 */
interface NotifierInterface
{
    /**
     * Redmine user id.
     *
     * @throws NotifierException
     *
     * @return int
     */
    public function redmineUserId();


    /**
     * Total worked hours that logged today.
     *
     * @param int $user_id
     *
     * @throws NotifierException
     *
     * @return int
     */
    public function totalWorkedHours($user_id);


    /**
     * Slack target username.
     *
     * @throws NotifierException
     *
     * @return string
     */
    public function slackUsername();


    /**
     * Notify the slack user.
     *
     * @param string $from
     * @param string $to
     * @param string $message
     *
     * @throws NotifierException
     *
     * @return bool
     */
    public function notifySlackUser($from, $to, $message);
}