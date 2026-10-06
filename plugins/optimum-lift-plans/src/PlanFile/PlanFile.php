<?php

/**
 * A Plan file (format version 1): one Training Plan as JSON, naming Exercises
 * by library key. Read from a string and checked as a whole; a file with any
 * problem exposes no content, only its problems, so a broken file never
 * half-imports.
 *
 * Format checks need nothing but the string. Whether each Exercise exists on
 * this site is a separate step, resolveExercises(), because it needs the
 * database. It checks every well-formed key even when the file has other
 * problems, so one run reports everything.
 *
 * Spec: .scratch/plan-files/spec.md.
 */

declare(strict_types=1);

namespace OptimumLift\Plans\PlanFile;

use OptimumLift\Plans\Content\Choices;
use OptimumLift\Plans\Content\LibraryKeys;
use OptimumLift\Plans\Content\PlanFields;
use stdClass;

final class PlanFile
{
    public const FORMAT    = 'optimum-lift-plan';
    public const VERSION   = 1;
    public const MAX_BYTES = 2 * 1024 * 1024;

    private const PLAN_PROPERTIES         = ['title', 'summary', 'goal', 'target_audience', 'difficulty', 'phases', 'weeks'];
    private const PHASE_PROPERTIES        = ['name', 'first_week', 'last_week'];
    private const WEEK_PROPERTIES         = ['workouts'];
    private const WORKOUT_PROPERTIES      = ['name', 'prescriptions'];
    private const PRESCRIPTION_PROPERTIES = ['exercise', 'sets', 'target_type', 'target', 'intensity', 'rest_seconds', 'notes'];

    /** @var list<string> problems found while parsing */
    private array $found = [];

    /** @var array<string, list<string>> well-formed library key => places it is used, found while parsing */
    private array $foundUses = [];

    /**
     * @param list<string>                $problems
     * @param array<string, int>          $exerciseIds Library key => Exercise post ID, once resolveExercises() has run.
     * @param array<string, list<string>> $uses        Library key => places it is used.
     */
    private function __construct(
        public readonly ?PlanContent $content,
        public readonly array $problems,
        public readonly array $exerciseIds = [],
        private readonly array $uses = [],
    ) {
    }

    public static function parse(string $bytes): self
    {
        if (strlen($bytes) > self::MAX_BYTES) {
            return self::refused(__('The file is larger than 2 MB, which no Plan file needs. Check that it is the right file.', 'optimum-lift-plans'));
        }

        if (str_starts_with($bytes, "\xFF\xFE") || str_starts_with($bytes, "\xFE\xFF") || str_contains($bytes, "\0")) {
            return self::refused(__('The file is not UTF-8 text (it looks like UTF-16). Save the file as UTF-8.', 'optimum-lift-plans'));
        }

        // Windows editors start UTF-8 files with a byte-order mark.
        if (str_starts_with($bytes, "\xEF\xBB\xBF")) {
            $bytes = substr($bytes, 3);
        }

        try {
            $data = json_decode($bytes, false, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            /* translators: %s: the JSON parser's error message, in English */
            return self::refused(sprintf(__('The file is not valid JSON: %s.', 'optimum-lift-plans'), rtrim($e->getMessage(), '.')));
        }

        if (!$data instanceof stdClass) {
            return self::refused(__('The file must hold one JSON object, starting with {.', 'optimum-lift-plans'));
        }

        $parser  = new self(null, []);
        $content = $parser->file($data);

        return new self($parser->found === [] ? $content : null, $parser->found, [], $parser->foundUses);
    }

    /**
     * Turns every library key into this site's Exercise post ID. Each key that
     * is missing, in the trash or not published is one problem, naming every
     * place it is used.
     */
    public function resolveExercises(LibraryKeys $keys): self
    {
        $problems = [];
        $ids      = [];

        foreach ($this->uses as $key => $places) {
            // A key of digits only becomes an integer array key.
            $key    = (string) $key;
            $id     = $keys->find($key);
            $status = $id === null ? null : get_post_status($id);

            if ($id !== null && $status === 'publish') {
                $ids[$key] = $id;
                continue;
            }

            $problems[] = sprintf(
                match ($status) {
                    /* translators: 1: library key, 2: places in the Plan, e.g. Week 1, Workout 2, Prescription 3 */
                    null    => __('Exercise "%1$s" does not exist on this site. Used in %2$s.', 'optimum-lift-plans'),
                    /* translators: 1: library key, 2: places in the Plan, e.g. Week 1, Workout 2, Prescription 3 */
                    'trash' => __('Exercise "%1$s" is in the trash. Used in %2$s.', 'optimum-lift-plans'),
                    /* translators: 1: library key, 2: places in the Plan, e.g. Week 1, Workout 2, Prescription 3 */
                    default => __('Exercise "%1$s" is not published. Used in %2$s.', 'optimum-lift-plans'),
                },
                $key,
                Places::list($places)
            );
        }

        $problems = [...$this->problems, ...$problems];

        return new self($problems === [] ? $this->content : null, $problems, $problems === [] ? $ids : [], $this->uses);
    }

    /**
     * The exporter's output: every property, in the format's order, pretty
     * printed with LF line endings and a final newline, so an unchanged Plan
     * always exports to the same bytes.
     */
    public static function encode(PlanContent $content): string
    {
        $file = [
            'format'  => self::FORMAT,
            'version' => self::VERSION,
            'plan'    => $content->toArray(),
        ];

        return json_encode($file, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    }

    /**
     * Multi-line text the way it is stored: tags stripped, LF line endings.
     */
    public static function multiline(string $value): string
    {
        return sanitize_textarea_field(str_replace(["\r\n", "\r"], "\n", $value));
    }

    private static function refused(string $problem): self
    {
        return new self(null, [$problem]);
    }

    private function file(stdClass $data): ?PlanContent
    {
        $props = get_object_vars($data);
        $place = Places::file();

        $this->unknown($props, ['format', 'version', 'plan'], $place);

        if (($props['format'] ?? null) !== self::FORMAT) {
            /* translators: 1: property name, 2: the only value allowed */
            $this->problem($place, sprintf(__('"%1$s" must be "%2$s".', 'optimum-lift-plans'), 'format', self::FORMAT));
        }

        $version = $props['version'] ?? null;

        if (is_int($version) && $version > self::VERSION) {
            $this->found[] = __('This file was made by a newer version of the plugin. Update the plugin on this site, then import it again.', 'optimum-lift-plans');
        } elseif ($version !== self::VERSION) {
            /* translators: 1: property name, 2: the only number allowed */
            $this->problem($place, sprintf(__('"%1$s" must be %2$d.', 'optimum-lift-plans'), 'version', self::VERSION));
        }

        if (!array_key_exists('plan', $props)) {
            $this->missing($place, 'plan');

            return null;
        }

        if (!$props['plan'] instanceof stdClass) {
            $this->problem(Places::plan(), __('must be an object.', 'optimum-lift-plans'));

            return null;
        }

        return $this->plan($props['plan']);
    }

    private function plan(stdClass $data): ?PlanContent
    {
        $props = get_object_vars($data);
        $place = Places::plan();

        $this->unknown($props, self::PLAN_PROPERTIES, $place);

        $title          = $this->text($props, 'title', $place, required: true);
        $summary        = $this->text($props, 'summary', $place, multiline: true);
        $goal           = $this->text($props, 'goal', $place);
        $targetAudience = $this->text($props, 'target_audience', $place);
        $difficulty     = $this->choice($props, 'difficulty', $place, ['' => ''] + Choices::difficulty(), '');

        $weekItems = $this->items($props, 'weeks', $place, required: true);
        $phases    = $this->phases($this->items($props, 'phases', $place) ?? [], $weekItems === null ? null : count($weekItems));
        $weeks     = [];

        foreach ($weekItems ?? [] as $index => $item) {
            $week = $this->week($item, $index + 1);

            if ($week !== null) {
                $weeks[] = $week;
            }
        }

        if ($this->found !== []) {
            return null;
        }

        return new PlanContent($title, $summary, $goal, $targetAudience, $difficulty, $phases, $weeks);
    }

    /**
     * The Phase rules of the wp-admin form (PlanFields::validate()), with the
     * same messages.
     *
     * @param list<mixed> $items
     * @return list<PhaseRow>
     */
    private function phases(array $items, ?int $weekCount): array
    {
        $phases = [];
        $taken  = [];

        foreach ($items as $index => $item) {
            $place = Places::phase($index + 1);

            if (!$item instanceof stdClass) {
                $this->problem($place, __('must be an object.', 'optimum-lift-plans'));
                continue;
            }

            $props = get_object_vars($item);
            $this->unknown($props, self::PHASE_PROPERTIES, $place);

            $name  = $this->text($props, 'name', $place, required: true);
            $first = $this->integer($props, 'first_week', $place);
            $last  = $this->integer($props, 'last_week', $place);

            if ($first === null || $last === null) {
                continue;
            }

            if ($first < 1 || $last < $first) {
                $this->problem($place, __('A Phase must end on or after the Week it starts.', 'optimum-lift-plans'));
                continue;
            }

            if ($weekCount !== null && $last > $weekCount) {
                /* translators: %d: number of Weeks in the Plan */
                $this->problem($place, sprintf(__('This Plan only has %d Weeks.', 'optimum-lift-plans'), $weekCount));
                continue;
            }

            for ($week = $first; $week <= $last; $week++) {
                if (isset($taken[$week])) {
                    /* translators: %d: Week number */
                    $this->problem($place, sprintf(__('Phases overlap at Week %d.', 'optimum-lift-plans'), $week));
                    continue 2;
                }

                $taken[$week] = true;
            }

            $phases[] = new PhaseRow($name, $first, $last);
        }

        return $phases;
    }

    private function week(mixed $item, int $number): ?WeekRow
    {
        $place = Places::week($number);

        if (!$item instanceof stdClass) {
            $this->problem($place, __('must be an object.', 'optimum-lift-plans'));

            return null;
        }

        $props = get_object_vars($item);
        $this->unknown($props, self::WEEK_PROPERTIES, $place);

        $workouts = [];

        foreach ($this->items($props, 'workouts', $place, required: true) ?? [] as $index => $workoutItem) {
            $workout = $this->workout($workoutItem, $number, $index + 1);

            if ($workout !== null) {
                $workouts[] = $workout;
            }
        }

        return new WeekRow($workouts);
    }

    private function workout(mixed $item, int $week, int $number): ?WorkoutRow
    {
        if (!$item instanceof stdClass) {
            $this->problem(Places::workout($week, $number, ''), __('must be an object.', 'optimum-lift-plans'));

            return null;
        }

        $props = get_object_vars($item);
        // Name the Workout in every message about it, when it has a usable name.
        $label = is_string($props['name'] ?? null) ? sanitize_text_field($props['name']) : '';
        $place = Places::workout($week, $number, $label);

        $this->unknown($props, self::WORKOUT_PROPERTIES, $place);

        $name          = $this->text($props, 'name', $place, required: true);
        $prescriptions = [];

        foreach ($this->items($props, 'prescriptions', $place, required: true) ?? [] as $index => $prescriptionItem) {
            $prescription = $this->prescription($prescriptionItem, Places::prescription($week, $number, $label, $index + 1));

            if ($prescription !== null) {
                $prescriptions[] = $prescription;
            }
        }

        return new WorkoutRow($name, $prescriptions);
    }

    private function prescription(mixed $item, string $place): ?PrescriptionRow
    {
        if (!$item instanceof stdClass) {
            $this->problem($place, __('must be an object.', 'optimum-lift-plans'));

            return null;
        }

        $props = get_object_vars($item);
        $this->unknown($props, self::PRESCRIPTION_PROPERTIES, $place);

        $exercise = $this->libraryKey($props, $place);

        if ($exercise !== '') {
            $this->foundUses[$exercise][] = $place;
        }
        $sets     = $this->integer($props, 'sets', $place, 1, PlanFields::SETS_MAX);

        $targetType = $this->choice($props, 'target_type', $place, Choices::targetTypes(), 'reps');
        $target     = $this->text($props, 'target', $place, required: true, maxLength: PlanFields::TARGET_MAX_LENGTH);
        $intensity  = $this->text($props, 'intensity', $place, maxLength: PlanFields::INTENSITY_MAX_LENGTH);
        $rest       = $this->rest($props, $place);
        $notes      = $this->text($props, 'notes', $place, multiline: true);

        if ($sets === null) {
            return null;
        }

        return new PrescriptionRow($exercise, $sets, $targetType, $target, $intensity, $rest, $notes);
    }

    /**
     * @param array<string, mixed> $props
     */
    private function libraryKey(array $props, string $place): string
    {
        if (!array_key_exists('exercise', $props)) {
            $this->missing($place, 'exercise');

            return '';
        }

        $key = $props['exercise'];

        if (!is_string($key) || !(new LibraryKeys())->isValid($key)) {
            $this->problem($place, sprintf(
                /* translators: %s: the value given for the Exercise */
                __('%s is not a library key. A key is lowercase letters and digits joined by hyphens, e.g. "barbell-back-squat".', 'optimum-lift-plans'),
                is_string($key) ? '"' . $key . '"' : (string) wp_json_encode($key)
            ));

            return '';
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $props
     */
    private function rest(array $props, string $place): ?int
    {
        $rest = $props['rest_seconds'] ?? null;

        if ($rest === null || (is_int($rest) && $rest >= 0)) {
            return $rest;
        }

        /* translators: %s: property name */
        $this->problem($place, sprintf(__('"%s" must be a whole number of 0 or more, or null.', 'optimum-lift-plans'), 'rest_seconds'));

        return null;
    }

    /**
     * A string property, sanitised the way it will be stored. A string that
     * changes under sanitising is not a problem.
     *
     * @param array<string, mixed> $props
     */
    private function text(array $props, string $name, string $place, bool $required = false, bool $multiline = false, int $maxLength = 0): string
    {
        if (!array_key_exists($name, $props)) {
            if ($required) {
                $this->missing($place, $name);
            }

            return '';
        }

        $value = $props[$name];

        if (!is_string($value)) {
            /* translators: %s: property name */
            $this->problem($place, sprintf(__('"%s" must be text.', 'optimum-lift-plans'), $name));

            return '';
        }

        $value = $multiline ? self::multiline($value) : sanitize_text_field($value);

        if ($required && $value === '') {
            $this->empty($place, $name);
        }

        if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
            /* translators: 1: property name, 2: maximum number of characters */
            $this->problem($place, sprintf(__('"%1$s" must be at most %2$d characters.', 'optimum-lift-plans'), $name, $maxLength));
        }

        return $value;
    }

    /**
     * A JSON integer: "3" and 3.5 are problems. With a range, a number outside
     * it is a problem too.
     *
     * @param array<string, mixed> $props
     */
    private function integer(array $props, string $name, string $place, ?int $min = null, ?int $max = null): ?int
    {
        if (!array_key_exists($name, $props)) {
            $this->missing($place, $name);

            return null;
        }

        $value = $props[$name];

        if ($min !== null && $max !== null && (!is_int($value) || $value < $min || $value > $max)) {
            /* translators: 1: property name, 2: lowest number allowed, 3: highest number allowed */
            $this->problem($place, sprintf(__('"%1$s" must be a whole number from %2$d to %3$d.', 'optimum-lift-plans'), $name, $min, $max));

            return null;
        }

        if (!is_int($value)) {
            /* translators: %s: property name */
            $this->problem($place, sprintf(__('"%s" must be a whole number.', 'optimum-lift-plans'), $name));

            return null;
        }

        return $value;
    }

    /**
     * @param array<string, mixed>  $props
     * @param array<string, string> $choices
     */
    private function choice(array $props, string $name, string $place, array $choices, string $default): string
    {
        if (!array_key_exists($name, $props)) {
            return $default;
        }

        $value = $props[$name];

        if (is_string($value) && array_key_exists($value, $choices)) {
            return $value;
        }

        $this->problem($place, sprintf(
            /* translators: 1: property name, 2: the values allowed, e.g. "reps", "seconds" */
            __('"%1$s" must be one of: %2$s.', 'optimum-lift-plans'),
            $name,
            implode(', ', array_map(static fn (string $key): string => '"' . $key . '"', array_keys($choices)))
        ));

        return $default;
    }

    /**
     * A list property. Null when it is missing or not a list.
     *
     * @param array<string, mixed> $props
     * @return list<mixed>|null
     */
    private function items(array $props, string $name, string $place, bool $required = false): ?array
    {
        if (!array_key_exists($name, $props)) {
            if ($required) {
                $this->missing($place, $name);
            }

            return null;
        }

        $value = $props[$name];

        if (!is_array($value) || !array_is_list($value)) {
            /* translators: %s: property name */
            $this->problem($place, sprintf(__('"%s" must be a list, in [ ].', 'optimum-lift-plans'), $name));

            return null;
        }

        if ($required && $value === []) {
            $this->empty($place, $name);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $props
     * @param list<string>         $allowed
     */
    private function unknown(array $props, array $allowed, string $place): void
    {
        foreach (array_keys($props) as $name) {
            if (!in_array((string) $name, $allowed, true)) {
                /* translators: %s: property name */
                $this->problem($place, sprintf(__('unknown property "%s".', 'optimum-lift-plans'), $name));
            }
        }
    }

    private function missing(string $place, string $name): void
    {
        /* translators: %s: property name */
        $this->problem($place, sprintf(__('"%s" is missing.', 'optimum-lift-plans'), $name));
    }

    private function empty(string $place, string $name): void
    {
        /* translators: %s: property name */
        $this->problem($place, sprintf(__('"%s" must not be empty.', 'optimum-lift-plans'), $name));
    }

    private function problem(string $place, string $message): void
    {
        $this->found[] = Places::at($place, $message);
    }
}
