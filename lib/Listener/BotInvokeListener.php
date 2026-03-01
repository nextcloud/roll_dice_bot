<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\RollDiceBot\Listener;

use OCA\RollDiceBot\AppInfo\Application;
use OCA\Talk\Events\BotInvokeEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event>
 */
class BotInvokeListener implements IEventListener {
	public function handle(Event $event): void {
		if (!$event instanceof BotInvokeEvent) {
			return;
		}

		if ($event->getBotUrl() !== 'nextcloudapp://' . Application::APP_ID) {
			return;
		}

		$chatMessage = $event->getMessage();
		if ($chatMessage['type'] !== 'Create') {
			return;
		}

		$content = json_decode($chatMessage['object']['content'], true);
		if (!str_starts_with($content['message'], '/roll')) {
			return;
		}

		$detailsString = trim(substr($content['message'], strlen('/roll')));
		$details = explode('d', $detailsString, 2);
		$amount = (int)trim($details[0] ?: '1');
		$dice = (int)trim($details[1] ?? '6');

		$rolledResults = [];
		for ($i = 0; $i < $amount; $i++) {
			$rolledResults[] = random_int(1, $dice);
		}

		$records = implode(' + ', $rolledResults);
		$result = array_sum($rolledResults);

		if ($amount === 1 && $dice === 6) {
			$message = '{actor} rolled a dice: {result}';
		} elseif ($dice === 6) {
			$message = '{actor} rolled {amount} dice: {records} = {result}';
		} elseif ($amount === 1) {
			$message = '{actor} rolled a d{dice}: {result}';
		} else {
			$message = '{actor} rolled {amount}d{dice}: {records} = {result}';
		}

		$event->addAnswer(
			'🎲 ' . str_replace(
				['{actor}', '{amount}', '{dice}', '{records}', '{result}'],
				[$this->getSender($chatMessage['actor']), $amount, $dice, $records, '**' . $result . '**'],
				$message,
			),
			(int)$chatMessage['object']['id'],
		);
	}

	protected function getSender(array $actor): string {
		[$type, $id] = explode('/', $actor['id'], 2);
		$type = rtrim($type, 's');
		if ($type === 'user') {
			return '@"' . $id . '"';
		}
		return '@"' . $type . '/' . $id . '"';
	}
}
