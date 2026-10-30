<?php

use App\Profiles\Models\TaxProfile;

test('a full name splits so the surname comes first on the forms', function (string $fullName, ?string $last, ?string $first) {
    expect((new TaxProfile(['full_name' => $fullName]))->nameParts())
        ->toBe(['last' => $last, 'first' => $first, 'middle' => null]);
})->with([
    'two words' => ['Ana Reyes', 'Reyes', 'Ana'],
    'surname prefix' => ['Juan Dela Cruz', 'Dela Cruz', 'Juan'],
    'middle name before a prefixed surname' => ['Juan Santos Dela Cruz', 'Dela Cruz', 'Juan Santos'],
    'two-word prefix' => ['Maria de los Reyes', 'de los Reyes', 'Maria'],
    'saint' => ['Pedro Sta. Maria', 'Sta. Maria', 'Pedro'],
    'suffix stays with the first name' => ['Jose Rizal Jr.', 'Rizal', 'Jose Jr.'],
    'already last name first' => ['Dela Cruz, Juan Santos', 'Dela Cruz', 'Juan Santos'],
    'extra spaces' => ['  Ana   Reyes ', 'Reyes', 'Ana'],
    'one word has no surname' => ['Cher', null, 'Cher'],
]);

test('name parts the taxpayer entered are used as they are', function () {
    $profile = new TaxProfile([
        'full_name' => 'Juan Santos Dela Cruz',
        'last_name' => 'Dela Cruz',
        'first_name' => 'Juan',
        'middle_name' => 'Santos',
    ]);

    expect($profile->nameParts())->toBe(['last' => 'Dela Cruz', 'first' => 'Juan', 'middle' => 'Santos']);
});
