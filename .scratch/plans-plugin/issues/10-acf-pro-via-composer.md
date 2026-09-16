# ACF Pro is installed by hand, against ADR-0001

Type: task
Status: ready-for-human

## Problem

ADR-0003 makes ACF Pro load-bearing. It is in `plugins/advanced-custom-fields-pro-main/` by hand, so a fresh clone does not have it. ADR-0001 says the first premium plugin means moving to Composer + wpackagist.

## What to do

Install ACF Pro through its Composer repository (`connect.advancedcustomfields.com`, which needs the license key in `auth.json`, never committed). Move `plugins.txt` to Composer as ADR-0001 describes. This needs the license key, so it is a good fit for `/wizard`.

## Acceptance criteria

- [ ] A fresh clone plus `docker compose up` ends with ACF Pro active, and no plugin copied in by hand.
