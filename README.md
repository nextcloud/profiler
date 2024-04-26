<!--
  - SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: CC0-1.0
-->
# Profiler

[![REUSE status](https://api.reuse.software/badge/github.com/nextcloud/profiler)](https://api.reuse.software/info/github.com/nextcloud/profiler)

This app provides a profiler to find performance related issues

Don't use in a production system.

## How to use (short introduction)

1. Clone the app and checkout the relevant `stableX` branch.
1. Build the JavaScript with `npm i && npm run build`
1. Enable the app `occ app:enable profiler`
1. Enable profiling by running `occ profiler:enable`
1. Open nextcloud with an admin account, you should see the profiler toolbar

## How to use (long introduction)

A tutorial page is available in the [developer documentation](https://docs.nextcloud.com/server/latest/developer_manual/digging_deeper/profiler.html)
