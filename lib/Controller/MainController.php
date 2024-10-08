<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Profiler\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Profiler\IProfiler;

class MainController extends Controller {
	private IProfiler $profiler;

	private IInitialState $initialState;

	private IURLGenerator $urlGenerator;

	public function __construct(
		string $appName,
		IRequest $request,
		IProfiler $profiler,
		IInitialState $initialState,
		IURLGenerator $urlGenerator,
	) {
		parent::__construct($appName, $request);
		$this->profiler = $profiler;
		$this->initialState = $initialState;
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index(): RedirectResponse {
		$profiles = $this->profiler->find(null, 1, null, null, null);

		return new RedirectResponse($this->urlGenerator->linkToRoute('profiler.main.profiler', [
			'profiler' => 'db',
			'token' => $profiles['token'] ?? 'empty'
		]));
	}

	/**
	 * @NoCSRFRequired
	 */
	public function profiler(string $profiler, string $token): TemplateResponse {
		$profiles = $this->profiler->find(null, 20, null, null, null);

		\OCP\Util::addScript('profiler', 'profiler-main');
		$this->initialState->provideInitialState('recentProfiles', $profiles);
		$this->initialState->provideInitialState('token', $token);

		return new TemplateResponse('profiler', 'index', []);
	}

	public function profileInfo(string $token): DataResponse {
		return new DataResponse([
			'profile' => $this->profiler->loadProfile($token),
		]);
	}
}
