<?php
/**
 * @file
 * Contains zviryatko\RedmineSlackNotifier\Tests\NotifierTest.
 */

namespace zviryatko\RedmineSlackNotifier\Tests;

use CL\Slack\Exception\SlackException;
use CL\Slack\Payload\AuthTestPayload;
use CL\Slack\Payload\AuthTestPayloadResponse;
use CL\Slack\Payload\ChatPostMessagePayload;
use zviryatko\RedmineSlackNotifier\Notifier;
use Redmine\Client as RedmineClient;
use CL\Slack\Transport\ApiClientInterface as SlackClient;
use zviryatko\RedmineSlackNotifier\NotifierException;

class NotifierTest extends \PHPUnit_Framework_TestCase
{
    public function testRedmineUserId()
    {
        $user_id = mt_rand(0, 10);
        $redmine_user_stub = $this->getMockBuilder(\Redmine\Api\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_user_stub
            ->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn(['user' => ['id' => $user_id]]);
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_stub
            ->expects($this->once())
            ->method('__get')
            ->with('user')
            ->willReturn($redmine_user_stub);
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notifier = new Notifier($redmine_stub, $slack_stub);
        $this->assertEquals($user_id, $notifier->redmineUserId());
    }

    public function testExceptionRedmineUserId()
    {
        $redmine_user_stub = $this->getMockBuilder(\Redmine\Api\User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_user_stub
            ->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn(false);
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_stub
            ->expects($this->once())
            ->method('__get')
            ->with('user')
            ->willReturn($redmine_user_stub);
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(NotifierException::class);
        (new Notifier($redmine_stub, $slack_stub))->redmineUserId();
    }

    public function testTotalWorkedHours()
    {
        $user_id = mt_rand(0, 10);
        $redmine_time_entry_stub = $this->getMockBuilder(\Redmine\Api\TimeEntry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_time_entry_stub
            ->expects($this->once())
            ->method('all')
            ->with(['spent_on' => 't', 'user_id' => $user_id])
            ->willReturn([
                'time_entries' => [
                    ['hours' => 1],
                    ['hours' => 2],
                ]
            ]);
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_stub
            ->expects($this->once())
            ->method('__get')
            ->with('time_entry')
            ->willReturn($redmine_time_entry_stub);
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notifier = new Notifier($redmine_stub, $slack_stub);
        $this->assertEquals(3, $notifier->totalWorkedHours($user_id));
    }

    public function testExceptionTotalWorkedHours()
    {
        $user_id = mt_rand(0, 10);
        $redmine_time_entry_stub = $this->getMockBuilder(\Redmine\Api\TimeEntry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_time_entry_stub
            ->expects($this->once())
            ->method('all')
            ->willReturn(false);
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $redmine_stub
            ->expects($this->once())
            ->method('__get')
            ->with('time_entry')
            ->willReturn($redmine_time_entry_stub);
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectException(NotifierException::class);
        (new Notifier($redmine_stub, $slack_stub))->totalWorkedHours($user_id);
    }

    public function testSlackUsername()
    {
        $test_username = 'test_username';
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub = $this->getMockBuilder(AuthTestPayloadResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub
            ->expects($this->once())
            ->method('isOk')
            ->willReturn(true);
        $slack_response_stub
            ->expects($this->once())
            ->method('getUsername')
            ->willReturn($test_username);
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(AuthTestPayload::class))
            ->willReturn($slack_response_stub);

        $notifier = new Notifier($redmine_stub, $slack_stub);
        $this->assertEquals($test_username, $notifier->slackUsername());
    }

    public function testIsNotOkExceptionSlackUsername()
    {
        $error = 'error';
        $explanation = 'error_explanation';
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub = $this->getMockBuilder(AuthTestPayloadResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub
            ->expects($this->once())
            ->method('isOk')
            ->willReturn(false);
        $slack_response_stub
            ->expects($this->once())
            ->method('getError')
            ->willReturn($error);
        $slack_response_stub
            ->expects($this->once())
            ->method('getErrorExplanation')
            ->willReturn($explanation);
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(AuthTestPayload::class))
            ->willReturn($slack_response_stub);

        $this->expectException(NotifierException::class);
        $this->expectExceptionMessage(sprintf('Slack api error: %s (%s).', $error, $explanation));
        (new Notifier($redmine_stub, $slack_stub))->slackUsername();
    }

    public function testExceptionSlackUsername()
    {
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(AuthTestPayload::class))
            ->willThrowException(new SlackException());

        $this->expectException(NotifierException::class);
        $this->expectExceptionMessage("Can't access to slack api, check credentials.");
        (new Notifier($redmine_stub, $slack_stub))->slackUsername();
    }

    public function testNotifySlackUser()
    {
        $from = 'test_from';
        $to = 'test_to';
        $message = 'test_message';
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub = $this->getMockBuilder(AuthTestPayloadResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub
            ->expects($this->once())
            ->method('isOk')
            ->willReturn(true);
        $payload = new ChatPostMessagePayload();
        $payload->setChannel("@{$to}");
        $payload->setUsername($from);
        $payload->setText($message);
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($payload)
            ->willReturn($slack_response_stub);

        $notifier = new Notifier($redmine_stub, $slack_stub);
        $this->assertTrue($notifier->notifySlackUser($from, $to, $message));
    }

    public function testIsNotOkNotifySlackUser()
    {
        $error = 'error';
        $explanation = 'error_explanation';
        $from = 'test_from';
        $to = 'test_to';
        $message = 'test_message';
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub = $this->getMockBuilder(AuthTestPayloadResponse::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_response_stub
            ->expects($this->once())
            ->method('isOk')
            ->willReturn(false);
        $slack_response_stub
            ->expects($this->once())
            ->method('getError')
            ->willReturn($error);
        $slack_response_stub
            ->expects($this->once())
            ->method('getErrorExplanation')
            ->willReturn($explanation);
        $payload = new ChatPostMessagePayload();
        $payload->setChannel("@{$to}");
        $payload->setUsername($from);
        $payload->setText($message);
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($payload)
            ->willReturn($slack_response_stub);

        $this->expectException(NotifierException::class);
        $this->expectExceptionMessage(sprintf("Can't send message to slack user: %s (%s)", $error, $explanation));
        (new Notifier($redmine_stub, $slack_stub))->notifySlackUser($from, $to, $message);
    }

    public function testExceptionNotifySlackUser()
    {
        $from = 'test_from';
        $to = 'test_to';
        $message = 'test_message';
        $redmine_stub = $this->getMockBuilder(RedmineClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $slack_stub = $this->getMockBuilder(SlackClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payload = new ChatPostMessagePayload();
        $payload->setChannel("@{$to}");
        $payload->setUsername($from);
        $payload->setText($message);
        $slack_stub
            ->expects($this->once())
            ->method('send')
            ->with($payload)
            ->willThrowException(new SlackException());

        $this->expectException(NotifierException::class);
        $this->expectExceptionMessage("Can't access to slack api, check credentials.");
        (new Notifier($redmine_stub, $slack_stub))->notifySlackUser($from, $to, $message);
    }
}
