<?php
/** Customer-facing Sabino facts and the official menu supplied by the shop. */
function sabino_data(): array {
    return [
        'name' => 'Sabino',
        'assistant' => 'Sabinox',
        'address' => '693 D. Fajardo Street, Salapungan, Angeles City, Pampanga',
        'phone' => '+63 916 653 9270',
        'phone_link' => 'tel:+639166539270',
        'instagram' => 'https://www.instagram.com/sabinowhoknows/',
        'maps' => 'https://www.google.com/maps/search/?api=1&query=693+D.+Fajardo+Street%2C+Salapungan%2C+Angeles+City%2C+Pampanga',
        'hours' => 'Monday–Friday, 9:00 AM–10:00 PM; Saturday, 1:00 PM–9:00 PM; Sunday closed. Please contact Sabino to confirm holiday or special-event hours.',
        'hours_short' => [
            ['days' => 'Monday–Friday', 'time' => '9:00 AM–10:00 PM'],
            ['days' => 'Saturday', 'time' => '1:00 PM–9:00 PM'],
            ['days' => 'Sunday', 'time' => 'Closed'],
        ],
        'menu' => [
            ['name'=>'Americano','price'=>130,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Latte','price'=>150,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Spanish Latte','price'=>160,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Caramel Macchiato','price'=>190,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Sea Salt Latte','price'=>190,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Tiramisu Latte','price'=>200,'kind'=>'Drinks','category'=>'Coffee'],
            ['name'=>'Lychee','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Strawberry','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Kiwi','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Green Apple','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Blueberry','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Grapes','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Peach','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Passion Fruit','price'=>150,'kind'=>'Drinks','category'=>'Italian Soda'],
            ['name'=>'Coke Float','price'=>150,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Apollo','price'=>180,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Dark Chocolate','price'=>190,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Matcha Latte','price'=>190,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Georgia','price'=>190,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Dirty Matcha','price'=>200,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Ube','price'=>200,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Pistachio Latte','price'=>250,'kind'=>'Drinks','category'=>'Specialty'],
            ['name'=>'Lychee Mango','price'=>150,'kind'=>'Drinks','category'=>'Tea'],
            ['name'=>'Peach Mango','price'=>150,'kind'=>'Drinks','category'=>'Tea'],
            ['name'=>'Strawberry Lemon','price'=>150,'kind'=>'Drinks','category'=>'Tea'],
            ['name'=>'Strawberry Peach','price'=>150,'kind'=>'Drinks','category'=>'Tea'],
            ['name'=>'Kiwi','price'=>150,'kind'=>'Drinks','category'=>'Tea'],
            ['name'=>'Mi Goreng','price'=>150,'kind'=>'Food','category'=>'Starters'],
            ['name'=>'Onion Rings','price'=>200,'kind'=>'Food','category'=>'Starters'],
            ['name'=>'Animal Style Fries','price'=>250,'kind'=>'Food','category'=>'Starters'],
            ['name'=>'Nachos','price'=>270,'kind'=>'Food','category'=>'Starters'],
            ['name'=>'Caesar Salad','price'=>250,'kind'=>'Food','category'=>'Salad'],
            ['name'=>'Mushroom','price'=>150,'kind'=>'Food','category'=>'Soup'],
            ['name'=>'Crab and Corn','price'=>150,'kind'=>'Food','category'=>'Soup'],
            ['name'=>'Spam','price'=>190,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Tocino','price'=>200,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Bacon','price'=>220,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Tapa','price'=>230,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Burger Steak','price'=>230,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Fried Chicken','price'=>230,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Breaded Porkchop','price'=>230,'kind'=>'Food','category'=>'Rice Meals'],
            ['name'=>'Carbonara','price'=>270,'kind'=>'Food','category'=>'Pasta'],
            ['name'=>'Aglio Olio','price'=>300,'kind'=>'Food','category'=>'Pasta'],
            ['name'=>'Chicken Pesto','price'=>300,'kind'=>'Food','category'=>'Pasta'],
            ['name'=>'Spaghetti & Meatballs','price'=>300,'kind'=>'Food','category'=>'Pasta'],
            ['name'=>'PB & J','price'=>150,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'Tuna','price'=>170,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'Cheeseburger','price'=>220,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'B.E.L.T.','price'=>250,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'Crispy Chicken Burger','price'=>250,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'Buffalo Chicken Burger','price'=>290,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'BBQ Jalapeño Burger','price'=>300,'kind'=>'Food','category'=>'Special Sandwiches'],
            ['name'=>'Rice','price'=>25,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Egg','price'=>25,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Garlic Mayo','price'=>30,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Gravy','price'=>30,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Garlic Bread','price'=>40,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Ranch','price'=>50,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Roasted Sesame','price'=>60,'kind'=>'Food','category'=>'Add-ons'],
            ['name'=>'Buffalo','price'=>240,'sharing_price'=>550,'kind'=>'Food','category'=>'Wings'],
            ['name'=>'Honey Sriracha','price'=>240,'sharing_price'=>550,'kind'=>'Food','category'=>'Wings'],
            ['name'=>'Soy Garlic','price'=>240,'sharing_price'=>550,'kind'=>'Food','category'=>'Wings'],
            ['name'=>'Garlic Parmesan','price'=>240,'sharing_price'=>550,'kind'=>'Food','category'=>'Wings'],
        ],
    ];
}

function sabino_menu_text(array $data, ?string $kind = null): string {
    $kinds = $kind ? [$kind] : ['Drinks', 'Food'];
    $sections = [];
    foreach ($kinds as $currentKind) {
        $groups = [];
        foreach ($data['menu'] as $item) {
            if ($item['kind'] === $currentKind) $groups[$item['category']][] = $item;
        }
        $blocks = ["**" . strtoupper($currentKind) . "**"];
        foreach ($groups as $category => $items) {
            $lines = ["**{$category}**"];
            foreach ($items as $item) {
                $price = '₱' . $item['price'];
                if (isset($item['sharing_price'])) $price .= ' solo / ₱' . $item['sharing_price'] . ' sharing';
                $lines[] = '- ' . $item['name'] . ' — ' . $price;
            }
            $blocks[] = implode("\n", $lines);
        }
        $sections[] = implode("\n\n", $blocks);
    }
    return implode("\n\n", $sections) . "\n\nAvailability can change. [Call or text Sabino]({$data['phone_link']}) for today’s items.";
}

function sabino_reply(string $question): array {
    $data = sabino_data();
    $q = mb_strtolower(trim($question));
    $reply = '';
    $matched = true;
    $suggestions = ['Full menu', 'Opening hours', 'Find Sabino'];

    if (preg_match('/\b(where|address|location|located|directions|map|find|visit|saan|nasaan|pupunta|salapungan)\b/u', $q)) {
        $reply = "Sabino is at **{$data['address']}**. [Open directions]({$data['maps']}).";
        $suggestions = ['Opening hours', 'Contact Sabino', 'Full menu'];
    } elseif (preg_match('/\b(hours?|opening|opens?|closing|closes?|closed|today|tomorrow|sunday|monday|saturday|oras|bukas|sarado|schedule)\b/u', $q)) {
        $reply = "**Reported hours:** {$data['hours']} [Call to confirm]({$data['phone_link']}).";
        $suggestions = ['Find Sabino', 'Contact Sabino', 'Full menu'];
    } elseif (preg_match('/\b(phone|number|contact|call|text|message|instagram|ig|social|tawag|telepono)\b/u', $q)) {
        $reply = "You can [call or text Sabino]({$data['phone_link']}) at **{$data['phone']}**, or visit [their Instagram]({$data['instagram']}).";
        $suggestions = ['Find Sabino', 'Opening hours', 'Full menu'];
    } elseif (preg_match('/\b(do you know|who is|who\'s|kilala mo|staff|owner|manager|barista|employee|crew)\b/u', $q)) {
        $reply = "I don’t have a verified staff or owner directory for Sabino, so I can’t confirm whether a named person works there. For a staff-related question, please [contact Sabino]({$data['phone_link']}) directly. I can still help with coffee choices and general café questions.";
        $suggestions = ['Recommend a drink', 'Coffee basics', 'Contact Sabino'];
    } elseif (preg_match('/\b(what milk|which milk|milk.*coffee|whole milk|skim milk)\b/u', $q)) {
        $reply = "**Whole milk** gives the richest texture, while lower-fat milk tastes lighter. **Oat milk** is creamy and naturally sweet, soy milk has more body and protein, and almond milk is lighter with a nutty taste. Availability and steaming results vary, so ask which milks Sabino currently carries and mention any allergy.";
        $suggestions = ['Can drinks be customized?', 'Latte vs cappuccino', 'Allergy information'];
    } elseif (preg_match('/\b(customi[sz]e|less sugar|more sugar|sweetness|no sugar|sugar.free|extra shot|add shot|milk option|oat milk|soy milk|almond milk|non.dairy|dairy.free|no ice|less ice|extra ice|syrup|whipped cream|substitute|adjust)\b/u', $q)) {
        $reply = "Most cafés can adjust **sweetness, ice, milk, syrups, and espresso shots**. You can ask for options such as less sweet, no added sugar, an extra shot, or a non-dairy milk. Sabino’s exact modifiers and possible add-on charges aren’t confirmed yet, so please ask the barista when ordering.";
        $suggestions = ['What milk suits coffee?', 'How strong is an extra shot?', 'Recommend a drink'];
    } elseif (preg_match('/\b(allerg\w*|lactose|dairy|gluten|vegan|vegetarian|ingredient|calories|nutrition|nut free|pregnan|medical)\b/u', $q)) {
        $reply = "For allergies or dietary needs, tell the barista the **specific ingredient you must avoid** before ordering. Milk, chocolate, flavored syrups, and pastries may contain dairy, nuts, soy, gluten, or other allergens, and shared tools can cause cross-contact. I don’t have Sabino’s verified ingredient or allergen list, so please [contact the shop]({$data['phone_link']}) for a safe recommendation.";
        $suggestions = ['Non-coffee options', 'Can drinks be customized?', 'Contact Sabino'];
    } elseif (preg_match('/\b(deliver(?:y|ies|ed)?|pickup|takeout|take away|order online|advance order|pre.order)\b/u', $q)) {
        $reply = "I can’t confirm Sabino’s current delivery or pickup setup yet. The quickest option is to [call or text {$data['phone']}]({$data['phone_link']}) and ask about same-day availability, preparation time, payment, and pickup instructions.";
        $suggestions = ['Contact Sabino', 'Full menu', 'Opening hours'];
    } elseif (preg_match('/\b(reserve|reservation|book|table|event|group|function)\b/u', $q)) {
        $reply = "For tables, groups, or events, please [contact Sabino]({$data['phone_link']}) first. Ask about party size, preferred date and time, minimum spend, deposits, and how long the table can be held. I don’t have a confirmed reservation policy yet.";
        $suggestions = ['Contact Sabino', 'Opening hours', 'Find Sabino'];
    } elseif (preg_match('/\b(payment|pay|gcash|maya|card|cash|credit|debit)\b/u', $q)) {
        $reply = "Sabino’s accepted payment methods haven’t been confirmed. It’s best to bring a backup payment method and [ask the shop]({$data['phone_link']}) whether they currently accept cash, GCash/Maya, or cards.";
        $suggestions = ['Contact Sabino', 'Full menu', 'Find Sabino'];
    } elseif (preg_match('/\b(wifi|wi.fi|parking|pets?|pet.friendly|outlet|socket|work|study|laptop|restroom|toilet|accessib)\b/u', $q)) {
        $reply = "I don’t have verified details yet about Sabino’s Wi-Fi, parking, outlets, pet policy, restrooms, accessibility, or laptop rules. Please [call or text the shop]({$data['phone_link']}) before visiting if one of these is essential. Tell me which amenity you need and I’ll help you form the right question.";
        $suggestions = ['Contact Sabino', 'Find Sabino', 'Opening hours'];
    } elseif (preg_match('/\b(strong(?:er|est)? coffee.*caffeine|caffeine.*strong(?:er|est)? coffee|dark roast.*caffeine|caffeine.*dark roast)\b/u', $q)) {
        $reply = "**Strong flavor and high caffeine are not the same thing.** Dark roast can taste bolder, but caffeine depends more on the bean, dose, number of espresso shots, and serving size. For more caffeine, ask about an extra shot; for a bold taste from Sabino’s menu, start with the **Americano (₱130)**.";
        $suggestions = ['Strong coffee recommendation', 'How many shots are in a drink?', 'Tell me about roast levels'];
    } elseif (preg_match('/\b(coffee.*sleep|sleep.*coffee|coffee.*bed|bedtime|how late.*coffee|caffeine.*hours?)\b/u', $q)) {
        $reply = "Caffeine can remain active for several hours and may affect sleep even when you no longer feel energized. If you’re sensitive, avoid coffee late in the day, ask whether decaf is available, or choose a caffeine-free drink. Individual responses vary, so follow medical advice for health concerns.";
        $suggestions = ['Low-caffeine choices', 'Non-coffee options', 'Show drinks menu'];
    } elseif (preg_match('/\b(caffeine|caffeinated|decaf|wake me|energy|strongest|more caffeine|less caffeine|low caffeine|how many shots|espresso shots?|how strong.*cold brew|cold brew.*strong)\b/u', $q)) {
        if (preg_match('/\b(decaf|less caffeine|low caffeine|sensitive|night|evening)\b/u', $q)) {
            $reply = "For less caffeine, ask whether **decaf espresso** is available, choose a single-shot milk drink, or pick a non-coffee option such as hot chocolate. Matcha still contains caffeine. Exact caffeine depends on the beans, dose, and number of shots, so ask the barista if you’re sensitive.";
        } else {
            $reply = "Caffeine depends on bean type, dose, and serving size. An espresso-based drink usually has the caffeine of its espresso shot even when milk is added; an extra shot increases it. Cold brew can be stronger because it is often brewed as a concentrate, but recipes vary. Ask Sabino how many shots are in the drink you choose.";
        }
        $suggestions = ['Strong coffee recommendation', 'Non-coffee options', 'Latte vs cappuccino'];
    } elseif (preg_match('/\b(latte vs cappuccino|cappuccino vs latte|difference.*latte.*cappuccino|difference.*cappuccino.*latte)\b/u', $q)) {
        $reply = "A **latte** has more steamed milk and only a light foam layer, so it tastes smoother and milder. A **cappuccino** uses less milk and more foam, so the espresso tastes more pronounced. Pick a latte for creamy and gentle; cappuccino for airy and coffee-forward.";
        $suggestions = ['What is a flat white?', 'Strong coffee recommendation', 'Can drinks be customized?'];
    } elseif (preg_match('/\b(flat white vs cappuccino|cappuccino vs flat white|difference.*flat white.*cappuccino)\b/u', $q)) {
        $reply = "A **flat white** has silky microfoam and less milk than a latte, so it is smooth but coffee-forward. A **cappuccino** has a thicker, airier foam cap. Flat white feels velvety; cappuccino feels lighter and foamier.";
        $suggestions = ['Latte vs cappuccino', 'What is microfoam?', 'Recommend a milky drink'];
    } elseif (preg_match('/\b(americano vs brewed coffee|brewed coffee vs americano|difference.*americano.*brewed)\b/u', $q)) {
        $reply = "An **Americano** is espresso diluted with water; **brewed coffee** is made by passing or steeping water through ground coffee. They can look similar, but an Americano usually has espresso’s concentrated aroma while brewed coffee reflects its filter method.";
        $suggestions = ['What is espresso?', 'Strong coffee recommendation', 'Show coffee menu'];
    } elseif (preg_match('/\b(cold brew vs iced coffee|iced coffee vs cold brew|difference.*cold brew.*iced)\b/u', $q)) {
        $reply = "**Iced coffee** is hot-brewed coffee cooled over ice, so it often tastes brighter. **Cold brew** steeps in cool water for many hours, usually making it smoother and less sharp. Either can be strong; caffeine depends on the recipe and serving size.";
        $suggestions = ['How much caffeine?', 'Hot or iced?', 'Recommend a refreshing drink'];
    } elseif (preg_match('/\b(what is|what\'s|difference|explain).*\b(americano|espresso|latte|cappuccino|flat white|macchiato|mocha|cold brew|spanish latte|matcha|cortado|ristretto|lungo|microfoam)\b/u', $q, $drinkMatch)) {
        $drink = $drinkMatch[2];
        $definitions = [
            'espresso' => 'Espresso is a small, concentrated coffee brewed under pressure. It has a bold flavor and forms the base of many café drinks.',
            'americano' => 'An Americano is espresso diluted with hot water. It tastes similar to black coffee while keeping espresso’s aroma and character.',
            'latte' => 'A latte combines espresso with plenty of steamed milk and a thin foam layer. It is creamy, mild, and easy to flavor.',
            'cappuccino' => 'A cappuccino combines espresso, steamed milk, and a thicker foam layer. It tastes stronger and airier than a latte.',
            'flat white' => 'A flat white uses espresso with a smaller amount of silky microfoam than a latte, giving it a stronger coffee taste and smooth texture.',
            'macchiato' => 'A traditional macchiato is espresso marked with a small amount of milk or foam. Café versions can vary, so ask how the shop prepares it.',
            'mocha' => 'A mocha is a latte-style drink with chocolate. It is creamy, sweet, and still has an espresso flavor.',
            'cold brew' => 'Cold brew is coffee steeped in cool water for many hours. It is usually smooth and less sharp, though its caffeine strength varies by recipe.',
            'spanish latte' => 'A Spanish latte is a sweet, creamy espresso drink commonly made with milk and sweetened condensed milk.',
            'matcha' => 'Matcha is finely ground green tea whisked with water or milk. It is not coffee, but it normally contains caffeine.',
            'cortado' => 'A cortado is espresso balanced with a small, roughly equal amount of warm milk. It is stronger and less milky than a latte.',
            'ristretto' => 'A ristretto is a shorter espresso extraction using less water. It is concentrated and often tastes sweeter and less bitter than a longer shot.',
            'lungo' => 'A lungo is an espresso pulled with more water for longer. It makes a larger, more diluted shot and can taste more bitter.',
            'microfoam' => 'Microfoam is steamed milk with extremely small bubbles, creating a glossy, velvety texture used in lattes and flat whites.',
        ];
        $reply = $definitions[$drink] ?? "That drink can vary by café, so ask the barista how Sabino prepares it.";
        $suggestions = ['Latte vs cappuccino', 'Recommend a drink', 'How much caffeine?'];
    } elseif (preg_match('/\b(light|medium|dark) roast|roast levels?|which roast/u', $q)) {
        $reply = "**Light roast** usually keeps brighter, fruity or floral notes; **medium roast** balances acidity, sweetness, and body; **dark roast** tastes bolder, roastier, and sometimes smoky. Roast level describes flavor more reliably than caffeine. Sabino’s current roast isn’t listed, so ask the barista.";
        $suggestions = ['Does dark roast have more caffeine?', 'Arabica vs robusta', 'What are tasting notes?'];
    } elseif (preg_match('/\b(arabica vs robusta|robusta vs arabica|difference.*arabica.*robusta)\b/u', $q)) {
        $reply = "**Arabica** is commonly sweeter, more aromatic, and more acidic. **Robusta** is usually earthier, more bitter, fuller-bodied, and higher in caffeine. Many espresso blends combine them for both aroma and strength.";
        $suggestions = ['Tell me about roast levels', 'What is coffee acidity?', 'Strong coffee recommendation'];
    } elseif (preg_match('/\b(acidity|coffee body|tasting notes?|flavo(?:u)?r notes?)\b/u', $q)) {
        $reply = "In coffee, **acidity** means a lively brightness—like citrus or fruit—not necessarily sourness. **Body** is how heavy or rich the drink feels. **Tasting notes** describe flavors the coffee naturally reminds you of, such as chocolate, nuts, caramel, or berries; they are not always added ingredients.";
        $suggestions = ['Why does coffee taste sour?', 'Arabica vs robusta', 'Tell me about roast levels'];
    } elseif (preg_match('/\b(crema|foam on espresso)\b/u', $q)) {
        $reply = "**Crema** is the golden-brown foam on freshly pulled espresso, formed by oils and trapped gas. It can add aroma and texture, but thick crema alone does not guarantee a delicious or well-extracted shot.";
        $suggestions = ['What is espresso?', 'Why is coffee bitter?', 'What is microfoam?'];
    } elseif (preg_match('/\b(sour coffee|coffee.*sour|bitter coffee|coffee.*bitter|under.?extract|over.?extract|extraction)\b/u', $q)) {
        $reply = "Coffee that tastes sharply **sour** may be under-extracted; coffee that tastes harsh, dry, or very **bitter** may be over-extracted. Grind size, water temperature, dose, and brew time all matter. Some pleasant acidity and bitterness are natural, so adjust one brewing variable at a time.";
        $suggestions = ['What grind size should I use?', 'What is coffee acidity?', 'How should I brew coffee?'];
    } elseif (preg_match('/\b(store coffee|coffee storage|keep beans|fresh beans|coffee freshness|freeze coffee)\b/u', $q)) {
        $reply = "Keep coffee beans in an **airtight, opaque container** away from heat, light, moisture, and strong odors. Buy amounts you can use while fresh and grind just before brewing. Avoid repeatedly opening frozen beans because condensation can damage flavor.";
        $suggestions = ['How fine should I grind coffee?', 'Tell me about coffee beans', 'How should I brew coffee?'];
    } elseif (preg_match('/\b(grind size|water temperature|brew temperature|coffee ratio|coffee.*water ratio)\b/u', $q)) {
        $reply = "Use a **fine grind for espresso**, medium for drip or pour-over, and coarse for French press or cold brew. A practical starting water temperature is about **90–96°C**, with roughly **1 part coffee to 15–17 parts water** for filter coffee; then adjust to taste.";
        $suggestions = ['Why does coffee taste sour?', 'How should I brew coffee?', 'How do I store beans?'];
    } elseif (preg_match('/\b(beans?|roast|arabica|robusta|single origin|blend)\b/u', $q)) {
        $reply = "Coffee flavor changes with the bean and roast. **Arabica** is often sweeter and more aromatic; **Robusta** is usually bolder and higher in caffeine. Lighter roasts can taste brighter or fruitier, while darker roasts lean toward chocolate, caramel, or smoky notes. I don’t have Sabino’s current bean or roaster details yet, so ask the barista what they are serving today.";
        $suggestions = ['What is acidity in coffee?', 'Strong coffee recommendation', 'How is coffee brewed?'];
    } elseif (preg_match('/\b(brew|pour.over|french press|aeropress|v60|drip|grind size|how.*make coffee)\b/u', $q)) {
        $reply = "Brewing changes the cup: **espresso** is concentrated and pressure-brewed, **pour-over** is clean and aromatic, **French press** is fuller-bodied, and **cold brew** is smooth from long steeping. Grind size, water temperature, dose, and brew time all affect flavor. Ask Sabino which brewing methods are currently available.";
        $suggestions = ['Tell me about coffee beans', 'What is cold brew?', 'Recommend a drink'];
    } elseif (preg_match('/\b(pair|pairs|paired|pairing|goes with|eat with|food with|pastry with|snack with)\b/u', $q)) {
        $reply = "From Sabino’s current menu, try **PB & J with a Latte**, **Carbonara with an Americano**, or **Wings with an Italian Soda or Tea**. Those pairings balance creamy, savory, and refreshing flavors, but you can mix them however you like.";
        $suggestions = ['Show food menu', 'Show drinks menu', 'Recommend a drink'];
    } elseif (preg_match('/\b(hot or iced|hot vs iced|iced or hot|temperature|too hot)\b/u', $q)) {
        $reply = "Choose **hot** for more aroma and a softer milk texture; choose **iced** for a colder, more refreshing drink. Ice can dilute the cup as it melts, so an iced drink may be brewed stronger. You can usually request less ice, but Sabino’s exact options should be confirmed when ordering.";
        $suggestions = ['Can drinks be customized?', 'Recommend a refreshing drink', 'How strong is cold brew?'];
    } elseif (preg_match('/\b(coffee basics|coffee 101|learn about coffee|new to coffee)\b/u', $q)) {
        $reply = "A quick coffee guide: **espresso** is the concentrated base; add water for an Americano, steamed milk for a latte, less milk plus more foam for a cappuccino, or chocolate for a mocha. Cold brew is steeped cold for hours. Start with a latte if you want mild and creamy, or an Americano if you prefer black coffee.";
        $suggestions = ['Latte vs cappuccino', 'Tell me about coffee beans', 'Recommend a drink'];
    } elseif (preg_match('/\b(recommend|suggest|best|sweet|strong|hard coffee|bold|intense|smooth|creamy|milky|milk.forward|lots? of milk|bitter|black coffee|fruity|chocolatey|refreshing|not too sweet|less sweet|budget|under|below|less than|cheap|favorite|favourite|beginner|first coffee|non.coffee|without coffee|hindi kape|matapang|matamis)\b/u', $q)) {
        if (preg_match('/(?:under|below|less than|budget(?: of)?|up to)\s*(?:₱|php\s*)?(\d{2,4})/u', $q, $budgetMatch)) {
            $budget = (int)$budgetMatch[1];
            $options = array_filter($data['menu'], fn($item) => $item['kind'] === 'Drinks' && $item['price'] <= $budget);
            $choices = array_map(fn($item) => $item['name'] . ' (₱' . $item['price'] . ')', $options);
            $choices = array_values(array_unique($choices));
            $reply = $choices
                ? 'For a drink budget of ₱' . $budget . ', Sabino currently lists ' . implode(', ', $choices) . '.'
                : 'Sabino’s current drink menu has no listed option within ₱' . $budget . '.';
        } elseif (preg_match('/\b(non.coffee|without coffee|hindi kape)\b/u', $q)) {
            $reply = "For a non-coffee choice, Sabino lists **Dark Chocolate (₱190)**, **Matcha Latte (₱190)**, **Ube (₱200)**, Italian Sodas (₱150), and fruit Teas (₱150). Matcha normally contains caffeine.";
        } elseif (preg_match('/\b(milky|milk.forward|lots? of milk|creamy|smooth|mild)\b/u', $q)) {
            $reply = "For a milky drink, start with **Latte (₱150)**—it is the mildest, most milk-forward coffee. Choose **Spanish Latte (₱160)** if you also want sweetness, or **Pistachio Latte (₱250)** for a richer flavored option.";
        } elseif (preg_match('/\b(hard coffee|strong|bold|intense|matapang|black coffee)\b/u', $q)) {
            $reply = "If by ‘hard coffee’ you mean bold and coffee-forward, choose **Americano (₱130)**. For a distinctive caffeinated mix, try **Dirty Matcha (₱200)**. Bold taste does not always mean more caffeine, so ask how many espresso shots are included or whether an extra shot is available.";
        } elseif (preg_match('/\b(beginner|first coffee|new to coffee)\b/u', $q)) {
            $reply = "For a first coffee, try **Latte (₱150)** for mild and creamy, or **Spanish Latte (₱160)** if you prefer sweet. If you already enjoy black coffee, begin with **Americano (₱130)**.";
        } elseif (preg_match('/\b(fruity|fruit drink)\b/u', $q)) {
            $reply = "For fruity drinks, Sabino has **Italian Sodas (₱150)** and **fruit Teas (₱150)**. Try Lychee or Peach for gentle sweetness, Green Apple or Kiwi for a brighter taste, or Strawberry Lemon for sweet-tart flavor.";
        } elseif (preg_match('/\b(chocolate|chocolatey)\b/u', $q)) {
            $reply = "For chocolate without coffee, choose **Dark Chocolate (₱190)**. If you want a dessert-like coffee instead, **Tiramisu Latte (₱200)** is the closest fit on Sabino’s listed menu.";
        } elseif (preg_match('/\b(matcha)\b/u', $q)) {
            $reply = "Choose **Matcha Latte (₱190)** for creamy green tea without coffee, or **Dirty Matcha (₱200)** for matcha plus espresso. Matcha still contains caffeine, though the amount varies by recipe.";
        } elseif (preg_match('/\b(not too sweet|less sweet)\b/u', $q)) {
            $reply = "For something not too sweet, start with an **Americano (₱130)** or **Latte (₱150)**. Ask the barista whether sweetness can be reduced for flavored drinks.";
        } elseif (preg_match('/\b(refreshing|cold|iced)\b/u', $q)) {
            $reply = "For a refreshing choice, try any **Italian Soda (₱150)** or fruit **Tea (₱150)**. Lychee, Green Apple, Peach, and Strawberry Lemon are easy starting points.";
        } elseif (preg_match('/\b(sweet|matamis|chocolate)\b/u', $q)) {
            $reply = "For a sweeter coffee, try **Spanish Latte (₱160)**, **Caramel Macchiato (₱190)**, or **Tiramisu Latte (₱200)**. For no coffee, try **Dark Chocolate (₱190)**.";
        } elseif (preg_match('/\b(bitter)\b/u', $q)) {
            $reply = "For a clean, unsweetened coffee with some bitterness, choose **Americano (₱130)**. Ask for no sugar. If you want strong flavor with less bitterness, a Latte (₱150) softens the espresso with milk.";
        } else {
            $reply = "A simple starting point from Sabino’s menu: **Americano (₱130)** for black coffee, **Latte (₱150)** for something creamy, or **Lychee Mango Tea (₱150)** for something fruity.";
        }
        $suggestions = ['Show drinks menu', 'Show food menu', 'Opening hours'];
    } elseif (preg_match('/\b(coffee|espresso|caffeine|beans?|roast|brew|kape)\b/u', $q) && !preg_match('/\b(show|menu|price|cost|how much|magkano)\b/u', $q)) {
        $reply = "I can answer that coffee question, but I may need one more detail. Are you asking about **choosing a drink, caffeine, beans and roast, brewing, milk, flavor, or customization**? For example: “Which Sabino drink is milky?”, “Why is coffee sour?”, or “Does dark roast have more caffeine?”";
        $suggestions = ['Recommend a milky drink', 'Strong coffee recommendation', 'Coffee basics'];
    } elseif (preg_match('/\b(menu|prices?|cost|much|drinks?|beverages?|coffee|latte|americano|soda|tea|specialty|food|starters?|salad|soup|rice meals?|pasta|sandwich|burger|wings?|add.ons?|presyo|magkano|kape|pagkain)\b/u', $q)) {
        $kind = null;
        if (preg_match('/\b(food|starters?|salad|soup|rice meals?|pasta|sandwich|burger|wings?|add.ons?|pagkain)\b/u', $q)) $kind = 'Food';
        elseif (preg_match('/\b(drinks?|beverages?|coffee|latte|americano|soda|tea|specialty|kape)\b/u', $q)) $kind = 'Drinks';
        $found = [];
        foreach ($data['menu'] as $item) {
            $needle = mb_strtolower($item['name']);
            if (mb_strlen($needle) >= 5 && str_contains($q, $needle)) $found[] = $item;
        }
        if ($found) {
            $lines = [];
            foreach ($found as $item) {
                $price = '₱' . $item['price'];
                if (isset($item['sharing_price'])) $price .= ' solo / ₱' . $item['sharing_price'] . ' sharing';
                $lines[] = '- **' . $item['name'] . '** — ' . $price . ' · ' . $item['category'];
            }
            $reply = "Here’s the exact price from Sabino’s menu:\n\n" . implode("\n", $lines);
        } else {
            $reply = sabino_menu_text($data, $kind);
        }
        $suggestions = $kind === 'Food' ? ['Show drinks menu', 'Recommend a drink', 'Find Sabino'] : ($kind === 'Drinks' ? ['Show food menu', 'Recommend a drink', 'Find Sabino'] : ['Recommend a drink', 'Show food menu', 'Find Sabino']);
    } elseif (preg_match('/\b(hello|hi|hey|good morning|good afternoon|kumusta|salamat|thanks|help|faq|sabinox|sabino|who are you)\b/u', $q)) {
        $reply = "Hi! I’m **Sabinox**, Sabino’s café helper. I can show Sabino’s current drinks and food menus with exact prices, recommend a drink, explain coffee, and help with location, hours, or contact details. What are you curious about?";
        $suggestions = ['Recommend a drink', 'Coffee basics', 'Visit Sabino'];
    } else {
        $matched = false;
        $reply = "I may not have understood the exact term, but I can help with **drink recommendations, coffee definitions, caffeine, beans and roast, brewing, customization, food pairings, or visiting Sabino**. Tell me the taste you want—milky, strong, sweet, bitter, fruity, or refreshing—or ask a specific coffee question. For a Sabino detail I can’t verify, [call or text the shop]({$data['phone_link']}).";
        $suggestions = ['Recommend a milky drink', 'Strong coffee recommendation', 'Coffee basics'];
    }
    return ['content' => $reply, 'suggestions' => $suggestions, 'matched' => $matched];
}

