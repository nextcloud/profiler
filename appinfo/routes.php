<?php

// Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
// SPDX-FileCopyrightText: 2022 Carl Schwan <carl@carlschwan.eu>
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
	'routes' => [
		['name' => 'main#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'database_profiler#explain', 'url' => '/explain/{token}/{query}', 'verb' => 'GET'],
		['name' => 'main#profiler', 'url' => '/profiler/{profiler}/{token}/', 'verb' => 'GET'],
		['name' => 'main#profileInfo', 'url' => '/profile/{token}/', 'verb' => 'GET'],
	],
];
