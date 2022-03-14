<?php
/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Alexey Pyltsyn <lex61rus@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
return [
	'routes' => [
		['name' => 'main#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'database_profiler#explain', 'url' => '/explain/{token}/{query}', 'verb' => 'GET'],
		['name' => 'main#profiler', 'url' => '/profiler/{profiler}/{token}/', 'verb' => 'GET'],
		['name' => 'main#profileInfo', 'url' => '/profile/{token}/', 'verb' => 'GET'],
	],
];
