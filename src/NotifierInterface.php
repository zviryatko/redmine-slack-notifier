<?php
/**
 * @file
 * Contains zviryatko\RedmineSlackNotifier\NotifierInterface.
 */

namespace zviryatko\RedmineSlackNotifier;


interface NotifierInterface {
  /**
   * Notify the slack users.
   *
   * @param array $users
   */
  public function notify(array $users);
}