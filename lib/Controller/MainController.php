<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @copyright Copyright (c) 2019 Janis Köhr <janiskoehr@icloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Janis Köhr <janis.koehr@novatec-gmbh.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Profiler\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IRequest;
use OCP\Profiler\IProfiler;

class MainController extends Controller {
	private IProfiler $profiler;

	private IInitialState $initialState;

	public function __construct(string $appName, IRequest $request, IProfiler $profiler, IInitialState $initialState) {
		parent::__construct($appName, $request);
		$this->profiler = $profiler;
		$this->initialState = $initialState;
	}

	/**
	 * @NoCSRFRequired
	 */
	public function index(): RedirectResponse {
		$profiles = $this->profiler->find(null, 1, null, null, null);

		return new RedirectResponse('/index.php/app/profiler/db/' . $profiles['token']);
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
