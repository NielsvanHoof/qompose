<?php

declare(strict_types=1);

/**
 * @return array{english: array<string, string>, dutch: array<string, string>}
 */
function loadTranslationFiles(string $projectRoot): array
{
    /** @var array<string, string> $englishTranslations */
    $englishTranslations = json_decode(
        (string) file_get_contents($projectRoot.'/lang/en.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    /** @var array<string, string> $dutchTranslations */
    $dutchTranslations = json_decode(
        (string) file_get_contents($projectRoot.'/lang/nl.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    return [
        'english' => $englishTranslations,
        'dutch' => $dutchTranslations,
    ];
}

/**
 * @return list<string>
 */
function translationKeysUsedInDirectory(string $directory): array
{
    $keys = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory),
    );

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $contents = (string) file_get_contents($file->getPathname());

        if (preg_match_all("/__\\('([^']+)'/", $contents, $matches) !== false) {
            foreach ($matches[1] as $key) {
                $keys[] = $key;
            }
        }
    }

    return array_values(array_unique($keys));
}

it('keeps English and Dutch translation keys fully aligned', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $translations = loadTranslationFiles($projectRoot);

    $englishKeys = array_keys($translations['english']);
    $dutchKeys = array_keys($translations['dutch']);

    // Fail with a clear diff when a key exists in English but not in Dutch.
    $missingInDutch = array_values(array_diff($englishKeys, $dutchKeys));

    // Fail with a clear diff when a key exists in Dutch but not in English.
    $missingInEnglish = array_values(array_diff($dutchKeys, $englishKeys));

    expect($missingInDutch)
        ->toBeEmpty('Missing keys in nl.json: '.json_encode($missingInDutch, JSON_THROW_ON_ERROR))
        ->and($missingInEnglish)
        ->toBeEmpty('Missing keys in en.json: '.json_encode($missingInEnglish, JSON_THROW_ON_ERROR));
});

it('covers every backend translation key in both locale files', function (): void {
    $projectRoot = dirname(__DIR__, 2);
    $translations = loadTranslationFiles($projectRoot);
    $englishKeys = array_keys($translations['english']);

    $missingInEnglish = array_values(array_diff(
        translationKeysUsedInDirectory($projectRoot.'/app'),
        $englishKeys,
    ));

    expect($missingInEnglish)
        ->toBeEmpty('Missing backend keys in en.json: '.json_encode($missingInEnglish, JSON_THROW_ON_ERROR));
});

/**
 * @return list<string>
 */
function flattenValidationKeys(array $lines): array
{
    $keys = [];

    foreach ($lines as $key => $value) {
        if ($key === 'custom' || $key === 'attributes') {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                if (is_string($nestedValue)) {
                    $keys[] = "{$key}.{$nestedKey}";
                }
            }

            continue;
        }

        if (is_string($value)) {
            $keys[] = $key;
        }
    }

    sort($keys);

    return $keys;
}

it('keeps English and Dutch validation rule keys aligned', function (): void {
    $projectRoot = dirname(__DIR__, 2);

    /** @var array<string, mixed> $englishValidation */
    $englishValidation = require $projectRoot.'/lang/en/validation.php';

    /** @var array<string, mixed> $dutchValidation */
    $dutchValidation = require $projectRoot.'/lang/nl/validation.php';

    $englishKeys = flattenValidationKeys($englishValidation);
    $dutchKeys = flattenValidationKeys($dutchValidation);

    $missingInDutch = array_values(array_diff($englishKeys, $dutchKeys));
    $missingInEnglish = array_values(array_diff($dutchKeys, $englishKeys));

    expect($missingInDutch)
        ->toBeEmpty('Missing validation keys in nl: '.json_encode($missingInDutch, JSON_THROW_ON_ERROR))
        ->and($missingInEnglish)
        ->toBeEmpty('Missing validation keys in en: '.json_encode($missingInEnglish, JSON_THROW_ON_ERROR));
});
