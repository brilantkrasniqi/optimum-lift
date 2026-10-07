# Plan files: the checks that need the Docker stack (tickets 01 to 06 in this folder).
#
# Run from the repo root, on branch claude/project-thread-ij48ht, with the stack up:
#   powershell -ExecutionPolicy Bypass -File .scratch/plan-files/checks.ps1 *> plan-file-checks.txt
# then send plan-file-checks.txt back. It seeds demo Plans, imports and exports
# test Plans, and deletes what it created, except the published Plan it leaves
# for the Portal check at the end. It also regenerates the plugin's language
# files (commit those).

$ErrorActionPreference = 'Continue'
[Console]::OutputEncoding = [Text.Encoding]::UTF8

# -T: no TTY, so stdout and stderr stay apart and lines end in LF.
# All output, stderr included, as text lines, without Compose's container
# status lines and the empty stderr lines PowerShell 5.1 prints as
# "System.Management.Automation.RemoteException".
function Clean { process { $l = "$_"; if ($l -notmatch '^\s*Container ' -and $l -ne 'System.Management.Automation.RemoteException') { $l } } }
function wpc { docker compose --profile cli run --rm -T wpcli @args 2>&1 | Clean }
# Stdout only, for values.
function wpv { docker compose --profile cli run --rm -T wpcli @args 2>$null }
function Section([string] $title) { "`n=== $title" }
function Hash([string] $path) { if (Test-Path $path) { (Get-FileHash $path).Hash } else { "missing: $path" } }
function Check([string] $what, [bool] $ok) { if ($ok) { "PASS  $what" } else { "FAIL  $what" } }
function PlanCount { [int] (wpv post list --post_type=ol_training_plan --post_status=any --format=count) }
function CreatedId($lines) { foreach ($l in $lines) { if ("$l" -match 'Plan (\d+) created') { return [int] $Matches[1] } } return 0 }
# PHP run through /plans (content/plans), written as ASCII so PHP sees no BOM.
function EvalPhp([string] $name, [string] $php) {
    $path = "content/plans/x-$name.php"
    Set-Content -Path $path -Value $php -Encoding ascii
    wpc eval-file "/plans/x-$name.php"
    Remove-Item $path
}

$created = @()
$start   = PlanCount

Section 'Branch'
git fetch -q origin
git branch --show-current
git log --oneline -1

Section 'Ticket 01: field group unchanged'
$groupPhp = '<?php echo count(acf_get_local_fields("group_ol_training_plan")), " fields, md5 ", md5(serialize(acf_get_local_fields("group_ol_training_plan"))), "\n";'
git checkout origin/main -- plugins/optimum-lift-plans/src/Content/PlanFields.php
$groupMain = EvalPhp 'group' $groupPhp
git checkout HEAD -- plugins/optimum-lift-plans/src/Content/PlanFields.php
# Docker Desktop's bind mount can serve the old file for a while; every
# later check would then run main's PlanFields. Wait until it serves HEAD's.
$fieldsHash = (Get-FileHash plugins/optimum-lift-plans/src/Content/PlanFields.php -Algorithm MD5).Hash.ToLower()
for ($i = 0; $i -lt 30; $i++) {
    $seen = "$(docker compose exec -T wordpress md5sum wp-content/plugins/optimum-lift-plans/src/Content/PlanFields.php 2>$null)"
    if ($seen.StartsWith($fieldsHash)) { break }
    Start-Sleep -Seconds 1
}
Check 'the container serves the branch PlanFields.php again' ($seen.StartsWith($fieldsHash))
$groupBranch = EvalPhp 'group' $groupPhp
"main:   $groupMain"
"branch: $groupBranch"
Check 'the Plan field group is the same as on main' ("$groupMain" -eq "$groupBranch" -and "$groupMain" -match 'md5')

Section 'Seed the demo Plan (4 Weeks)'
$seed = wpc ol-plans seed
$seed
$demo = 0; foreach ($l in $seed) { if ("$l" -match 'Plan (\d+) \(4 Weeks\) and Product (\d+)') { $demo = [int] $Matches[1]; $demoProduct = [int] $Matches[2] } }
"demo Plan: $demo"

Section 'Ticket 02: export'
wpc ol-plans export-plan $demo --file=/plans/rt-a.json
wpc ol-plans export-plan $demo --file=/plans/rt-a2.json
Check 'exporting twice gives the same bytes' ((Hash content/plans/rt-a.json) -eq (Hash content/plans/rt-a2.json))
Check 'the export equals content/plans/demo-body-recomposition.json' ((Hash content/plans/rt-a.json) -eq (Hash content/plans/demo-body-recomposition.json))

Section 'Ticket 03: round trip of the demo'
$before = PlanCount
$imp = wpc ol-plans import-plan /plans/rt-a.json
$imp
$copy = CreatedId $imp; $created += $copy
wpc ol-plans export-plan $copy --file=/plans/rt-b.json
Check 'demo -> import -> export is byte-identical' ((Hash content/plans/rt-a.json) -eq (Hash content/plans/rt-b.json))
Check 'the copy is a draft' ((wpv post get $copy --field=post_status) -eq 'draft')

Section 'Ticket 03: uids of the copy'
EvalPhp 'uids' @"
<?php
function ol_uids(int `$id): array {
    global `$wpdb;
    return `$wpdb->get_col(`$wpdb->prepare('SELECT meta_value FROM ' . `$wpdb->postmeta . ' WHERE post_id = %d AND meta_key LIKE %s', `$id, 'weeks%uid'));
}
`$a = ol_uids($demo); `$b = ol_uids($copy);
`$uuid = array_filter(`$b, 'wp_is_uuid');
printf("source %d uids, copy %d uids, %d valid UUIDs, %d distinct, %d shared with the source\n", count(`$a), count(`$b), count(`$uuid), count(array_unique(`$b)), count(array_intersect(`$a, `$b)));
printf("copy uid fingerprint (for the Update check): %s\n", md5(implode(',', `$b)));
"@

Section 'Ticket 03: full.json round trip'
$imp = wpc ol-plans import-plan /plan-fixtures/full.json
$imp
$full = CreatedId $imp; $created += $full
wpc ol-plans export-plan $full --file=/plans/rt-full.json
Check 'full.json -> import -> export is byte-identical' ((Hash content/plans/rt-full.json) -eq (Hash tests/plan-files/full.json))

Section 'Ticket 03: dry runs write nothing'
$before = PlanCount
wpc ol-plans import-plan /plan-fixtures/full.json --dry-run
wpc ol-plans import-plan /plan-fixtures/minimal.json --dry-run
wpc ol-plans import-plan /plans/6-week-beginner-home-dumbbells.json --dry-run
wpc ol-plans import-plan /plans/demo-body-recomposition.json --dry-run
Check 'Plan count unchanged after dry runs' ((PlanCount) -eq $before)

Section 'Ticket 01: one problem each for bad files'
[IO.File]::WriteAllText("$PWD\content\plans\x-big.json", (' ' * 3MB))
Set-Content -Path content/plans/x-notjson.json -Value 'not json' -Encoding ascii
Get-Content tests/plan-files/minimal.json -Encoding UTF8 | Set-Content -Path content/plans/x-utf16.json -Encoding Unicode
Get-Content tests/plan-files/minimal.json -Encoding UTF8 | Set-Content -Path content/plans/x-bom.json -Encoding UTF8
foreach ($f in 'x-big', 'x-notjson', 'x-utf16', 'x-bom') {
    "-- $f"
    $out = @(wpc ol-plans import-plan "/plans/$f.json" --dry-run)
    $out
    if ($f -eq 'x-bom') { Check "$f passes" (($out -join "`n") -match 'Dry run: nothing was written') }
    else { Check "$f gives one problem" (($out -join "`n") -match 'Nothing was imported: 1 problem.') }
}
Remove-Item content/plans/x-big.json, content/plans/x-notjson.json, content/plans/x-utf16.json, content/plans/x-bom.json

Section 'Ticket 03: broken.json'
# `wp post delete` only trashes posts and pages, so trash the Exercise in PHP.
$yRaise = [int] (wpv post list --post_type=ol_exercise --meta_key=library_key --meta_value=band-y-raise --field=ID)
wpc eval "echo wp_trash_post($yRaise) ? 'Trashed Exercise $yRaise' : 'Could not trash Exercise $yRaise';"
# The expected messages are the English source strings; the site is Albanian.
Set-Content -Path mu-plugins/zz-plan-files-english-test.php -Encoding ascii -Value @'
<?php
add_filter('pre_determine_locale', static fn () => 'en_US');
'@
$before = PlanCount
$problems = @(docker compose --profile cli run --rm -T wpcli ol-plans import-plan /plan-fixtures/broken.json 2>$null | ForEach-Object { "$_" })
Remove-Item mu-plugins/zz-plan-files-english-test.php
$expected = @(Get-Content tests/plan-files/broken.expected.txt -Encoding UTF8)
$problems
Check 'broken.json prints exactly broken.expected.txt' (($problems -join "`n") -eq ($expected -join "`n"))
Check 'broken.json imports nothing' ((PlanCount) -eq $before)
wpc eval "wp_untrash_post($yRaise); wp_publish_post($yRaise); echo get_post_status($yRaise), PHP_EOL;"

Section 'Ticket 03: the same file twice'
$imp = wpc ol-plans import-plan /plans/rt-a.json
$imp
$created += (CreatedId $imp)

Section 'Ticket 03: a read-back difference deletes the draft'
Set-Content -Path mu-plugins/zz-plan-files-readback-test.php -Encoding ascii -Value @'
<?php
add_filter('acf/update_value/key=field_ol_prescription_notes', static fn ($value) => $value . ' (altered)');
'@
$before = PlanCount
wpc ol-plans import-plan /plan-fixtures/full.json
Remove-Item mu-plugins/zz-plan-files-readback-test.php
Check 'nothing kept after a read-back difference' ((PlanCount) -eq $before)

Section 'Ticket 03: 16-week Plan, timing and the input estimate'
$seed16 = wpc ol-plans seed --weeks=16
$seed16
$sixteen = 0; foreach ($l in $seed16) { if ("$l" -match 'Plan (\d+) \(16 Weeks\) and Product (\d+)') { $sixteen = [int] $Matches[1]; $sixteenProduct = [int] $Matches[2] } }
wpc ol-plans export-plan $sixteen --file=/plans/rt-16a.json
$script:imp16 = $null
$time = Measure-Command { $script:imp16 = wpc ol-plans import-plan /plans/rt-16a.json }
$imp16
"import of 16 Weeks took {0:N1} s (including the container start)" -f $time.TotalSeconds
$time0 = Measure-Command { wpc eval 'echo 1;' | Out-Null }
"an empty wp eval takes {0:N1} s, for comparison" -f $time0.TotalSeconds
$copy16 = CreatedId $imp16; $created += $copy16
wpc ol-plans export-plan $copy16 --file=/plans/rt-16b.json
Check '16-week round trip is byte-identical' ((Hash content/plans/rt-16a.json) -eq (Hash content/plans/rt-16b.json))
EvalPhp 'estimate' @"
<?php
`$p = $sixteen;
`$old = 60 + 4 * (int) get_post_meta(`$p, 'phases', true);
`$per = [];
for (`$w = 0; `$w < (int) get_post_meta(`$p, 'weeks', true); `$w++) {
    `$old += 2; `$per[`$w] = [];
    for (`$o = 0; `$o < (int) get_post_meta(`$p, 'weeks_' . `$w . '_workouts', true); `$o++) {
        `$n = (int) get_post_meta(`$p, 'weeks_' . `$w . '_workouts_' . `$o . '_prescriptions', true);
        `$old += 4 + 9 * `$n; `$per[`$w][] = `$n;
    }
}
printf("old estimate %d, PlanFields::estimateInputs %d\n", `$old, OptimumLift\Plans\Content\PlanFields::estimateInputs((int) get_post_meta(`$p, 'phases', true), `$per));
"@
"-- dry run with max_input_vars=1000 (expect the max_input_vars note)"
# The image runs the wp phar directly, which ignores WP_CLI_PHP_ARGS.
docker compose --profile cli run --rm -T --entrypoint php wpcli -d memory_limit=512M -d max_input_vars=1000 /usr/local/bin/wp ol-plans import-plan /plans/rt-16a.json --dry-run 2>&1 | Clean | Tee-Object -Variable lowLimit
Check 'the dry run warns about max_input_vars' (($lowLimit -join "`n") -match 'max_input_vars')

Section 'Ticket 06: language files'
$plugin = 'wp-content/plugins/optimum-lift-plans'
wpc i18n make-pot $plugin "$plugin/languages/optimum-lift-plans.pot" --domain=optimum-lift-plans '--exclude=vendor,assets,languages,data'
wpc i18n update-po "$plugin/languages/optimum-lift-plans.pot" "$plugin/languages/optimum-lift-plans-sq.po"
wpc i18n make-mo "$plugin/languages/optimum-lift-plans-sq.po" "$plugin/languages"
wpc i18n make-php "$plugin/languages/optimum-lift-plans-sq.po" "$plugin/languages"
EvalPhp 'po' @'
<?php
$po = file_get_contents(WP_PLUGIN_DIR . '/optimum-lift-plans/languages/optimum-lift-plans-sq.po');
$entries = preg_split('/\n\n+/', $po);
$untranslated = []; $fuzzy = 0; $total = 0;
foreach ($entries as $entry) {
    if (!preg_match('/^msgid "(.+)"$/m', $entry, $m) || str_starts_with(ltrim($entry), '#~')) { continue; }
    $total++;
    if (str_contains($entry, '#, fuzzy')) { $fuzzy++; }
    if (preg_match('/^msgstr(\[\d\])? ""$/m', $entry) && !preg_match('/^msgstr(\[\d\])? ""\n"/m', $entry)) { $untranslated[] = $m[1]; }
}
printf("%d strings, %d untranslated, %d fuzzy\n", $total, count($untranslated), $fuzzy);
foreach ($untranslated as $u) { echo "  untranslated: $u\n"; }
'@
git status --short plugins/optimum-lift-plans/languages

Section 'Clean up'
foreach ($id in $created) { if ($id -gt 0) { wpc post delete $id --force } }
foreach ($id in $sixteen, $demo) { if ($id -gt 0) { wpc post delete $id --force } }
foreach ($id in $sixteenProduct, $demoProduct) { if ($id -gt 0) { wpc post delete $id --force } }
Remove-Item content/plans/rt-*.json
Check 'Plan count back to where it started' ((PlanCount) -eq $start)

Section 'For the browser checks'
$imp = wpc ol-plans import-plan /plan-fixtures/full.json --status=publish
$imp
$review = CreatedId $imp
wpc eval "(new OptimumLift\Plans\Access\AccessRepository())->grant(1, $review, 0, 0); echo 'Access given to user 1';"
"Published Plan for the Portal and PDF check: $review"
"Edit it: http://localhost:8080/wp-admin/post.php?post=$review&action=edit"
"Portal:  http://localhost:8080/my-account/plans/$review/ (signed in as user 1)"
"Done."
