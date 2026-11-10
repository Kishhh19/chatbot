<?php
require __DIR__ . '/../includes/sabino.php';

$cases = [
    ['do you know sir brent?', 'staff or owner directory'],
    ['do you know josh manalang', 'staff or owner directory'],
    ['can I customize my drinks?', 'sweetness, ice, milk'],
    ['what is the difference between a latte and cappuccino?', 'more steamed milk'],
    ['what coffee has less caffeine?', 'less caffeine'],
    ['I have a dairy allergy', 'cross-contact'],
    ['do you accept gcash?', 'payment methods'],
    ['what milk suits coffee?', 'Whole milk'],
    ['teach me coffee basics', 'espresso'],
    ['recommend a not too sweet drink', 'not too sweet'],
    ['what pairs with a croissant?', 'croissant with a latte'],
    ['do you deliver?', 'delivery or pickup'],
    ['what is a flat white?', 'microfoam'],
    ['what time are you open on Saturday?', '1:00 PM–9:00 PM'],
];

$failures = [];
foreach ($cases as [$question, $expected]) {
    $reply = sabino_reply($question)['content'];
    if (mb_stripos($reply, $expected) === false) {
        $failures[] = "$question\nExpected: $expected\nReceived: $reply";
    }
}

if ($failures) {
    fwrite(STDERR, implode("\n\n", $failures) . "\n");
    exit(1);
}

echo 'Passed ' . count($cases) . " Sabinox knowledge checks.\n";
