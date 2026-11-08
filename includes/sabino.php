<?php
/** Customer-facing Sabino facts. Replace sample_menu when the official menu arrives. */
function sabino_data(): array {
    return [
        'name' => 'Sabino',
        'assistant' => 'Sabinox',
        'address' => '693 D. Fajardo Street, Salapungan, Angeles City, Pampanga',
        'phone' => '+63 916 653 9270',
        'phone_link' => 'tel:+639166539270',
        'instagram' => 'https://www.instagram.com/sabinowhoknows/',
        'maps' => 'https://www.google.com/maps/search/?api=1&query=693+D.+Fajardo+Street%2C+Salapungan%2C+Angeles+City%2C+Pampanga',
        'hours' => 'Monday–Saturday; opening has been reported around 7:00 AM or 9:00 AM depending on the day. Sunday closed. Closing times are not confirmed. Please contact Sabino before visiting.',
        'sample_menu' => [
            ['name' => 'Americano', 'price' => 100, 'type' => 'Coffee', 'note' => 'Espresso and water'],
            ['name' => 'Café Latte', 'price' => 140, 'type' => 'Coffee', 'note' => 'Espresso with milk'],
            ['name' => 'Cappuccino', 'price' => 140, 'type' => 'Coffee', 'note' => 'Espresso, milk, and foam'],
            ['name' => 'Spanish Latte', 'price' => 155, 'type' => 'Coffee', 'note' => 'Sweet, creamy espresso drink'],
            ['name' => 'Mocha', 'price' => 155, 'type' => 'Coffee', 'note' => 'Espresso and chocolate'],
            ['name' => 'Cold Brew', 'price' => 145, 'type' => 'Coffee', 'note' => 'Slow-steeped coffee'],
            ['name' => 'Matcha Latte', 'price' => 155, 'type' => 'Non-coffee', 'note' => 'Matcha and milk'],
            ['name' => 'Hot Chocolate', 'price' => 125, 'type' => 'Non-coffee', 'note' => 'Chocolate drink'],
            ['name' => 'Butter Croissant', 'price' => 115, 'type' => 'Pastry', 'note' => 'Flaky pastry'],
            ['name' => 'Chocolate Cookie', 'price' => 85, 'type' => 'Pastry', 'note' => 'Sweet café snack'],
        ],
    ];
}

function sabino_menu_text(array $data, ?string $type = null): string {
    $items = array_filter($data['sample_menu'], fn($item) => !$type || $item['type'] === $type);
    $lines = array_map(fn($item) => '- ' . $item['name'] . ' — ₱' . $item['price'] . ' · ' . $item['note'], $items);
    return "**Sample menu and estimated prices only** — these are Philippine café examples, not confirmed Sabino offerings or prices.\n\n"
        . implode("\n", $lines)
        . "\n\nPlease call **{$data['phone']}** for Sabino’s current menu, availability, and exact prices.";
}

function sabino_reply(string $question): array {
    $data = sabino_data();
    $q = mb_strtolower(trim($question));
    $reply = '';
    $matched = true;
    $suggestions = ['Sample menu', 'Opening hours', 'Find Sabino'];

    if (preg_match('/\b(where|address|location|located|directions|map|find|saan|nasaan|pupunta|salapungan)\b/u', $q)) {
        $reply = "Sabino is at **{$data['address']}**. [Open directions]({$data['maps']}).";
        $suggestions = ['Opening hours', 'Contact Sabino', 'Sample menu'];
    } elseif (preg_match('/\b(hours?|opening|opens?|closing|closes?|closed|today|tomorrow|sunday|monday|saturday|oras|bukas|sarado|schedule)\b/u', $q)) {
        $reply = "**Reported hours:** {$data['hours']} [Call to confirm]({$data['phone_link']}).";
        $suggestions = ['Find Sabino', 'Contact Sabino', 'Sample menu'];
    } elseif (preg_match('/\b(phone|number|contact|call|text|message|instagram|ig|social|tawag|telepono)\b/u', $q)) {
        $reply = "You can [call or text Sabino]({$data['phone_link']}) at **{$data['phone']}**, or visit [their Instagram]({$data['instagram']}).";
        $suggestions = ['Find Sabino', 'Opening hours', 'Sample menu'];
    } elseif (preg_match('/\b(orders?|deliver(?:y|ies|ed)?|pickup|takeout|reserve|reservations?|book|payments?|pay|gcash|wifi|parking|pets?|allerg\w*|ingredients?|vegan|decaf)\b/u', $q)) {
        $reply = "I don’t have confirmed Sabino details for that yet. Please [contact the shop]({$data['phone_link']}) at **{$data['phone']}** so the team can give you an accurate answer. I can help with the location, reported hours, and a sample menu.";
        $suggestions = ['Contact Sabino', 'Find Sabino', 'Sample menu'];
    } elseif (preg_match('/\b(recommend|suggest|best|sweet|strong|budget|under|below|less than|cheap|favorite|favourite|non.coffee|without coffee|hindi kape|matapang|matamis)\b/u', $q)) {
        if (preg_match('/(?:under|below|less than|budget(?: of)?|up to)\s*(?:₱|php\s*)?(\d{2,4})/u', $q, $budgetMatch)) {
            $budget = (int)$budgetMatch[1];
            $options = array_filter($data['sample_menu'], fn($item) => $item['type'] !== 'Pastry' && $item['price'] <= $budget);
            $choices = array_map(fn($item) => $item['name'] . ' (sample ₱' . $item['price'] . ')', $options);
            $reply = $choices
                ? 'For a sample drink budget of ₱' . $budget . ', consider ' . implode(', ', $choices) . '. These are estimates only, not confirmed Sabino prices or offerings.'
                : 'The sample drink list has no option within ₱' . $budget . '. Please ask Sabino about its current menu and prices.';
        } elseif (preg_match('/\b(non.coffee|without coffee|hindi kape|matcha|chocolate)\b/u', $q)) {
            $reply = "If you want a drink without coffee, the **sample menu** has Matcha Latte (₱155) and Hot Chocolate (₱125). These are suggestions only; Sabino’s actual selection and prices are unconfirmed.";
        } elseif (preg_match('/\b(sweet|matamis|chocolate)\b/u', $q)) {
            $reply = "For a sweeter coffee idea, try a **Spanish Latte (sample ₱155)** or **Mocha (sample ₱155)**. Please check with Sabino for what they currently serve.";
        } elseif (preg_match('/\b(strong|matapang|black)\b/u', $q)) {
            $reply = "For a bold coffee idea, consider an **Americano (sample ₱100)** or **Cold Brew (sample ₱145)**. Actual Sabino availability and prices may differ.";
        } else {
            $reply = "A simple starting point: **Americano (sample ₱100)** for black coffee, **Café Latte (sample ₱140)** for something creamy, or **Matcha Latte (sample ₱155)** without coffee. These are Philippine café examples, not Sabino’s confirmed menu.";
        }
        $suggestions = ['Sample menu', 'Contact Sabino', 'Opening hours'];
    } elseif (preg_match('/\b(menu|prices?|cost|much|drinks?|coffee|latte|cappuccino|americano|mocha|pastries|pastry|croissant|cookie|food|snack|matcha|presyo|magkano|kape)\b/u', $q)) {
        $type = preg_match('/\b(pastry|pastries|croissant|cookie|food|snack)\b/u', $q) ? 'Pastry' : (preg_match('/\b(matcha|non.coffee|hot chocolate)\b/u', $q) ? 'Non-coffee' : null);
        $reply = sabino_menu_text($data, $type);
        $suggestions = ['Recommend a drink', 'Contact Sabino', 'Find Sabino'];
    } elseif (preg_match('/\b(hello|hi|hey|good morning|good afternoon|kumusta|salamat|thanks|help|faq|sabinox|sabino|who are you)\b/u', $q)) {
        $reply = "Hi! I’m **Sabinox**, Sabino’s café helper. I can share the shop’s address, contact details, reported hours, and a **clearly labeled sample menu** while the official menu is being prepared. What would you like to know?";
    } else {
        $matched = false;
        $reply = "I can help with **Sabino’s location, contact details, reported hours, and a sample menu**. I don’t have a confirmed answer to that question yet; for anything specific, [call or text Sabino]({$data['phone_link']}) at **{$data['phone']}**.";
    }
    return ['content' => $reply, 'suggestions' => $suggestions, 'matched' => $matched];
}
