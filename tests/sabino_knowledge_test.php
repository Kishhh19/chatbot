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
    ['what pairs well with food?', 'PB & J with a Latte'],
    ['do you deliver?', 'delivery or pickup'],
    ['what is a flat white?', 'microfoam'],
    ['what time are you open on Saturday?', '1:00 PM–9:00 PM'],
    ['show drinks menu', 'Tiramisu Latte — ₱200'],
    ['show food menu', 'Carbonara — ₱270'],
    ['how much is BBQ Jalapeño Burger?', '₱300'],
    ['how much are buffalo wings?', '₱240 solo / ₱550 sharing'],
    ['can you suggest a milky drink?', 'Latte (₱150)'],
    ['I want coffee with lots of milk', 'milk-forward'],
    ['suggest hard coffee', 'Americano (₱130)'],
    ['what is a strong coffee?', 'Americano (₱130)'],
    ['I am new to coffee, what should I try?', 'Start with a latte'],
    ['suggest a fruity drink', 'Italian Sodas (₱150)'],
    ['recommend a chocolate drink', 'Dark Chocolate (₱190)'],
    ['recommend matcha', 'Dirty Matcha (₱200)'],
    ['does strong coffee always have more caffeine?', 'not the same thing'],
    ['does dark roast have more caffeine?', 'not the same thing'],
    ['will coffee affect my sleep?', 'several hours'],
    ['what is cold brew vs iced coffee?', 'hot-brewed coffee'],
    ['what is an americano vs brewed coffee?', 'espresso diluted with water'],
    ['flat white vs cappuccino', 'silky microfoam'],
    ['what is a cortado?', 'roughly equal amount'],
    ['what is ristretto?', 'shorter espresso extraction'],
    ['what is lungo?', 'more water'],
    ['what is microfoam?', 'extremely small bubbles'],
    ['explain light medium and dark roast', 'Light roast'],
    ['arabica vs robusta', 'higher in caffeine'],
    ['what is coffee acidity?', 'lively brightness'],
    ['what does coffee body mean?', 'how heavy or rich'],
    ['what are coffee tasting notes?', 'naturally reminds'],
    ['what is crema?', 'golden-brown foam'],
    ['why does my coffee taste sour?', 'under-extracted'],
    ['why does coffee taste bitter?', 'over-extracted'],
    ['how should I store coffee beans?', 'airtight, opaque container'],
    ['what grind size should I use?', 'fine grind for espresso'],
    ['what water temperature is best for coffee?', '90–96°C'],
    ['what coffee to water ratio should I use?', '1 part coffee to 15–17 parts water'],
    ['what can you teach me about coffee?', 'one more detail'],
    ['blorb coffee question', 'one more detail'],
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

