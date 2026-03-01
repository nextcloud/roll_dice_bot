<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\RollDiceBot\Migration;

use OCA\RollDiceBot\AppInfo\Application;
use OCA\Talk\Events\BotInstallEvent;
use OCA\Talk\Model\Bot;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Security\ISecureRandom;

/**
 * @psalm-api
 */
class InstallBot implements IRepairStep {
	public function __construct(
		protected IEventDispatcher $dispatcher,
		protected ISecureRandom $secureRandom,
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Install as Talk bot';
	}

	#[\Override]
	public function run(IOutput $output): void {
		if (!class_exists(BotInstallEvent::class)) {
			$output->warning('Talk not found, not installing bots');
			return;
		}

		$secret = $this->appConfig->getAppValueString('secret');
		if ($secret === '') {
			$secret = $this->secureRandom->generate(128);
			$this->appConfig->setAppValueString('secret', $secret, sensitive: true);
		}

		$event = new BotInstallEvent(
			'Roll dice',
			$secret,
			'nextcloudapp://' . Application::APP_ID,
			'Roll a die by posting a chat message with the content `/roll`. YOu can also roll multiple dice by specifying the amount `/roll 3` as well as specifying the dice sides e.g. `/roll d20` to roll a 20-sided die or `/roll 3d20` to roll 3-times a 20-sided die in a row.',
			Bot::FEATURE_EVENT
		);
		$this->dispatcher->dispatchTyped($event);
	}
}
