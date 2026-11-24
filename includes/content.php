<?php
declare(strict_types=1);

/**
 * The site's own knowledge: services, areas, fault guides and error codes.
 * Facts (R250 call-out, free if we repair, 9-month guarantee, same-day, 7 days,
 * 20 areas) come from the company's flyer; the numbers are editable in
 * Admin → Settings → Business through biz().
 */

/** Brands named in our Google Ads. Every brand in an ad must also be on the landing page (/book). */
const REPAIR_BRANDS = 'Samsung, LG, Hisense, Defy, Bosch, Smeg and Whirlpool';

/** @return array<string, array> */
function services(): array
{
    static $s = null;
    return $s ??= [
        'fridge-repairs' => [
            'name' => 'Fridge & freezer repairs', 'short' => 'Fridge', 'icon' => 'fridge', 'lcd' => '28',
            'photo' => 'fridge-compressor', 'photos' => ['fridge-double-door-open', 'fridge-ice-drawer-repair', 'fridge-display-28', 'fridge-top-freezer-open', 'fridge-side-by-side-open', 'fridge-black-side-by-side'],
            'lead' => 'Warm fridge, frozen veg drawer, puddle on the floor or a compressor that clicks and gives up. We find the fault at your home, before the food in it goes off.',
            'body' => [
                ['Not cooling is a symptom, not a fault', 'A fridge that has gone warm can have a blocked airflow, a dead fan, a failed defrost heater, a tired compressor relay or a gas leak. Each has a different price. We test the fans, the defrost circuit, the thermostat or sensors and the compressor before we tell you what is wrong, so you do not pay for a re-gas when the real problem is a frozen-up fan.'],
                ['Every kind of fridge in a Joburg kitchen', 'Top-freezer, bottom-freezer, side-by-side, double-door with ice and water dispenser, bar fridges and chest freezers. We work on no-frost models with control boards as well as simple fridges with a dial thermostat.'],
                ['What to do while you wait', 'Keep the doors closed: a full freezer holds its cold far longer than an open one. Move meat and milk to a neighbour or a cooler box if the fridge has been above 5°C for a few hours. Do not chip ice with a knife; one slip can puncture the gas line and end the fridge.'],
            ],
            'common' => ['Compressor hot or clicking', 'Fan not running', 'Ice on the back wall', 'Water under the drawers', 'Door seal not closing', 'Dispenser or ice maker dead', 'Display showing a code', 'Trips the electricity'],
        ],
        'washing-machine-repairs' => [
            'name' => 'Washing machine repairs', 'short' => 'Washing machine', 'icon' => 'washer', 'lcd' => '5E',
            'photo' => 'washer-front-loader-opened', 'photos' => ['board-multimeter', 'washer-board-out', 'washer-samsung-front-loader', 'washer-hisense-top-loader', 'washer-13kg-top-loader', 'washer-turbodrum'],
            'lead' => 'Stuck full of water, refusing to spin, walking across the floor or flashing a code. Front loaders and top loaders, repaired where they stand.',
            'body' => [
                ['We read the code, then we test', 'A code such as 5E, OE or UE tells you which system complained, not which part failed. A drain code can be a coin in the pump filter or a dead pump. We open the machine, measure the part and only then talk about replacing it.'],
                ['Control boards and motors', 'Power cuts are hard on washing machine electronics. When a machine is dead or stops halfway we take the board out and check it on the bench or on the spot with a meter, because a board is often blamed when a door lock or a wire is at fault.'],
                ['Top loaders too', 'Twin tubs, automatic top loaders and large 13 kg machines have their own faults: suspension rods, drain actuators, lid switches and worn clutches. We repair those as well.'],
            ],
            'common' => ['Will not drain', 'Will not spin', 'Leaking on the floor', 'Door will not open', 'Shaking and banging', 'Not filling with water', 'Dead, no lights', 'Stops halfway'],
        ],
        'stove-repairs' => [
            'name' => 'Stove & hob repairs', 'short' => 'Stove', 'icon' => 'stove', 'lcd' => '0',
            'photo' => 'stove-multimeter', 'photos' => ['hob-oven-white', 'hob-oven-samet', 'board-multimeter'],
            'lead' => 'Dead plates, plates stuck on full, or a stove that trips the house the moment it warms up. We trace it with a meter and fix it in your kitchen.',
            'body' => [
                ['One dead plate is usually a small job', 'A single plate that stays cold is nearly always the plate itself, its switch, or a burnt connector under the hob. Testing each one takes minutes and avoids replacing a good part.'],
                ['Tripping is a safety sign', 'A stove that trips the earth leakage has current going where it should not, often through a plate or an element that has let moisture in. Leave it switched off at the wall until it has been tested.'],
                ['Built-in or free-standing', 'Free-standing four-plate stoves, built-in hobs, solid plates, spiral plates and glass ceramic hobs.'],
            ],
            'common' => ['Plate stays cold', 'Plate stuck on high', 'Trips the electricity', 'Switch or knob broken', 'Burnt wire smell', 'Cracked plate', 'Indicator light out', 'Only low heat works'],
        ],
        'oven-repairs' => [
            'name' => 'Oven repairs', 'short' => 'Oven', 'icon' => 'oven', 'lcd' => '180',
            'photo' => 'hob-oven-samet', 'photos' => ['hob-oven-white', 'stove-multimeter', 'board-multimeter'],
            'lead' => 'Cold oven, burnt-on-one-side baking, a thermostat that lies or a door that will not shut. Elements, thermostats, switches and seals, sorted at your home.',
            'body' => [
                ['Top, bottom or fan element', 'Most ovens have two or three elements. If food browns on top but stays raw underneath, one of them has failed. We test each element and the selector switch, and replace only the one that is dead.'],
                ['When the thermostat is wrong', 'Set to 180, baking at 230? A drifting thermostat ruins bread and burns the bottoms of cakes. We compare the dial with the real temperature and correct or replace it.'],
                ['Doors and seals', 'A loose hinge or a flattened seal lets heat out, makes the oven slow and cooks your cupboards. It is one of the cheapest fixes with the biggest difference.'],
            ],
            'common' => ['Oven does not heat', 'Only grill works', 'Uneven baking', 'Overheats', 'Door will not close', 'Fan noisy or dead', 'Trips the electricity', 'Light or timer dead'],
        ],
        'microwave-repairs' => [
            'name' => 'Microwave repairs', 'short' => 'Microwave', 'icon' => 'microwave', 'lcd' => '0:30',
            'photo' => 'microwave', 'photos' => ['board-multimeter'],
            'lead' => 'Runs but does not heat, sparks inside, a plate that will not turn or buttons that do nothing. We test it and tell you honestly whether it is worth repairing.',
            'body' => [
                ['Runs but does not heat', 'The light is on, the plate turns, the fan blows and the food stays cold. The fault is in the high-voltage part that makes the heat, and it needs a technician with the right meter.'],
                ['Sparks and burning smells', 'Sparks often come from a burnt or greasy wave guide cover, the small cardboard-like plate on the inside wall, or from metal in the oven. Switch it off at once. Left running, a small spark can burn through the wall of the oven.'],
                ['Please do not open it', 'A microwave holds a dangerous charge even after it has been unplugged. The cover must stay on until a technician has made it safe.'],
            ],
            'common' => ['Does not heat', 'Sparks inside', 'Plate does not turn', 'Buttons do not work', 'Door will not close', 'Dead, no lights', 'Stops after a few seconds', 'Loud humming'],
        ],
        'geyser-repairs' => [
            'name' => 'Geyser repairs', 'short' => 'Geyser', 'icon' => 'geyser', 'lcd' => '60',
            'photo' => 'geyser-pair', 'photos' => ['geysers', 'board-multimeter'],
            'lead' => 'No hot water, lukewarm water, a geyser that trips the board or an overflow that never stops dripping. Call and we will come out the same day.',
            'body' => [
                ['No hot water has a short list of causes', 'A tripped breaker, a failed thermostat, a burnt element or a broken connection. We check them in order with the power safely off, and tell you which one it is.'],
                ['Dripping outside the house', 'A slow drip from the overflow can be normal while the water heats. A steady stream, or hot water running all day, points to a valve or a thermostat and will show on your water and electricity bills.'],
                ['Switch it off first', 'If water is coming through the ceiling or the geyser keeps tripping, switch the geyser off at the distribution board, close the main water supply and then call us. Never open the cover over the thermostat yourself: those terminals carry full mains power.'],
            ],
            'common' => ['No hot water', 'Water only lukewarm', 'Trips the board', 'Overflow keeps dripping', 'Water too hot', 'Takes too long to heat', 'Noise from the geyser', 'Leak at the geyser'],
        ],
    ];
}

/** @return array<string, array> The 20 areas on the flyer. */
function areas(): array
{
    static $a = null;
    return $a ??= [
        'soweto' => ['Soweto', 'West of the city', 'Home ground. Soweto is where our name comes from, and we repair appliances everywhere from four-room houses with one hard-working family fridge to new double-storeys in Protea Glen.', ['Orlando East', 'Orlando West', 'Diepkloof', 'Meadowlands', 'Pimville', 'Protea Glen', 'Protea North', 'Dobsonville', 'Jabavu', 'Jabulani', 'Naledi', 'Zola', 'Zondi', 'Moroka', 'Rockville', 'Dube', 'Mofolo', 'Chiawelo', 'Kliptown', 'Klipspruit', 'Molapo', 'Senaoane', 'Phiri', 'Mapetla', 'Emdeni', 'Tladi', 'Dlamini'], ['lenasia', 'eldorado-park', 'roodepoort', 'johannesburg'], 'Backyard rooms often share one supply, so we always check whether a tripping appliance is the fault or whether the line is simply carrying too much.'],
        'roodepoort' => ['Roodepoort', 'West Rand', 'From Florida Lake to the complexes of Wilgeheuwel, Roodepoort mixes older houses with built-in kitchens in townhouses. Both bring their own repairs.', ['Florida', 'Florida Glen', 'Discovery', 'Wilgeheuwel', 'Weltevreden Park', 'Ruimsig', 'Strubens Valley', 'Little Falls', 'Horison', 'Helderkruin', 'Constantia Kloof', 'Witpoortjie', 'Allens Nek'], ['krugersdorp', 'randburg', 'soweto'], 'Complex access is easiest if you send us the gate procedure on WhatsApp when you book.'],
        'johannesburg' => ['Johannesburg', 'City and south', 'Flats in the inner city, family homes in the south and old houses on the ridges. We carry the tools up the stairs so the appliance does not have to come down.', ['Johannesburg CBD', 'Braamfontein', 'Hillbrow', 'Yeoville', 'Kensington', 'Turffontein', 'Rosettenville', 'Mondeor', 'Glenvista', 'Mulbarton', 'Melville', 'Auckland Park', 'Westdene', 'Ormonde', 'Northcliff', 'Rosebank'], ['soweto', 'sandton', 'germiston', 'randburg'], 'In a flat, please tell us the floor and whether the lift is working; it helps us plan the visit.'],
        'randburg' => ['Randburg', 'North-west Joburg', 'A second fridge in the garage and a washing machine in the courtyard both work harder in heat and dust than the maker planned. We repair them where they stand.', ['Ferndale', 'Blairgowrie', 'Bordeaux', 'Robindale', 'Fontainebleau', 'Northgate', 'North Riding', 'Boskruin', 'Randpark Ridge', 'Cresta', 'Windsor', 'Linden', 'Bromhof'], ['roodepoort', 'sandton', 'fourways'], 'Garage fridges fail more in summer. Leave a hand-width of space behind them so the heat can get out.'],
        'sandton' => ['Sandton', 'North Joburg', 'Integrated fridges, panelled doors and large side-by-sides with plumbing. Sandton kitchens hide their appliances well, so we protect the cabinetry before we slide anything out.', ['Bryanston', 'Morningside', 'Rivonia', 'Sandown', 'Parkmore', 'Hurlingham', 'Sunninghill', 'Wendywood', 'Gallo Manor', 'Paulshof', 'Lone Hill', 'Strathavon', 'Wynberg', 'Alexandra'], ['randburg', 'midrand', 'fourways'], 'Estates and office parks may need our name at the gate. Send the access code when you book.'],
        'midrand' => ['Midrand', 'Between Joburg and Tshwane', 'New complexes, busy families and a lot of machines installed in tight laundry cupboards. We come to the complex, so nothing has to be carried to a workshop.', ['Halfway House', 'Vorna Valley', 'Noordwyk', 'Carlswald', 'Kyalami', 'Waterfall', 'Country View', 'Glen Austin', 'Ebony Park', 'Ivory Park', 'Rabie Ridge', 'Blue Hills', 'Halfway Gardens'], ['sandton', 'fourways', 'kempton-park'], 'Stacked washers and dryers need two people to move. Tell us if yours is stacked.'],
        'fourways' => ['Fourways', 'North Joburg', 'Estates, townhouses and a growing number of apartments around the mall. A front loader showing a drain code in a tight laundry cupboard is exactly the kind of job we come out for.', ['Lonehill', 'Dainfern', 'Broadacres', 'Cedar Lakes', 'Craigavon', 'Douglasdale', 'Magaliessig', 'Norscot', 'Witkoppen', 'Chartwell', 'Diepsloot', 'Cosmo City'], ['sandton', 'randburg', 'midrand'], 'Send a photo of the model sticker when you book; it tells us which spares to load.'],
        'kempton-park' => ['Kempton Park', 'East Rand', 'Around the airport and out to Tembisa, we repair family fridges, washing machines, stoves and geysers in houses, flats and back rooms.', ['Birchleigh', 'Norkem Park', 'Glen Marais', 'Bonaero Park', 'Edleen', 'Aston Manor', 'Birch Acres', 'Rhodesfield', 'Terenure', 'Van Riebeeck Park', 'Pomona', 'Tembisa', 'Esselen Park'], ['edenvale', 'benoni', 'boksburg', 'midrand'], 'For shift workers: we are open seven days, so a weekend visit is no problem.'],
        'germiston' => ['Germiston', 'East Rand', 'In an older house with older wiring, a stove that trips is not always the stove. We test the appliance and tell you honestly which it is.', ['Primrose', 'Lambton', 'Elsburg', 'Wadeville', 'Dinwiddie', 'Delville', 'Parkhill Gardens', 'Katlehong', 'Palm Ridge', 'Fisher\'s Hill', 'Marlands', 'Klippoortje'], ['alberton', 'boksburg', 'bedfordview', 'johannesburg'], 'If a stove trips only when the oven is on, mention it when you book. It narrows the fault down.'],
        'boksburg' => ['Boksburg', 'East Rand', 'Family suburbs around the lake and the mall, plus Vosloorus and Reiger Park to the south, all within our round.', ['Sunward Park', 'Parkrand', 'Beyers Park', 'Bartlett', 'Impala Park', 'Ravenswood', 'Boksburg North', 'Reiger Park', 'Vosloorus', 'Windmill Park', 'Dawn Park', 'Freeway Park'], ['benoni', 'germiston', 'brakpan', 'kempton-park'], 'Highveld lightning is hard on control boards. Unplug washers and fridges with displays during a storm if you can.'],
        'lenasia' => ['Lenasia', 'South of Soweto', 'Big families, big pots and stoves that are on for hours. Plates, oven elements and chest freezers work hard here, and Lenasia is Soweto\'s next-door neighbour.', ['Lenasia Extension 1 to 13', 'Lenasia South', 'Zakariyya Park', 'Anchorville', 'Lawley', 'Vlakfontein', 'Azaadville Gardens', 'Thembelihle', 'Lehae'], ['soweto', 'eldorado-park', 'ennerdale'], 'Cooking for a function? Tell us the date and we will try to get to the stove before it.'],
        'randfontein' => ['Randfontein', 'Far West Rand', 'Further west, where a repair shop can be a long drive with a fridge on the bakkie. We bring the workshop to you instead.', ['Greenhills', 'Homelake', 'Helikon Park', 'Eikepark', 'Culemborg Park', 'Mohlakeng', 'Toekomsrus', 'Westergloor', 'Robinpark', 'Aureus'], ['krugersdorp', 'roodepoort', 'soweto'], 'Because of the distance, a photo or video of the fault on WhatsApp helps us arrive with the right spare first time.'],
        'krugersdorp' => ['Krugersdorp', 'West Rand', 'Hillside homes, old mine houses and new estates along Hendrik Potgieter. We cover Krugersdorp and Kagiso.', ['Noordheuwel', 'Monument', 'Kenmare', 'Rangeview', 'Mindalore', 'Dan Pienaarville', 'Krugersdorp North', 'Luipaardsvlei', 'Kagiso', 'Munsieville', 'Azaadville', 'Featherbrooke'], ['roodepoort', 'randfontein', 'soweto'], 'Please let us know if the appliance is upstairs; it helps us plan the visit.'],
        'alberton' => ['Alberton', 'Ekurhuleni south', 'From Brackenhurst to Thokoza, we repair fridges, ovens and geysers for families south of the city.', ['New Redruth', 'Brackenhurst', 'Brackendowns', 'Meyersdal', 'Randhart', 'Verwoerdpark', 'Florentia', 'Alberante', 'Raceview', 'Thokoza', 'Eden Park', 'Mayberry Park'], ['germiston', 'johannesburg', 'ennerdale'], 'Built-in ovens have to be unscrewed from the cabinet. Clearing the cupboard next to it saves time.'],
        'benoni' => ['Benoni', 'East Rand', 'Lakeside homes, farms at Putfontein and the townships of Daveyton and Wattville. Distance is no reason to live with a warm fridge.', ['Farrarmere', 'Northmead', 'Rynfield', 'Lakefield', 'Crystal Park', 'Western Extension', 'Actonville', 'Daveyton', 'Wattville', 'Etwatwa', 'Mackenzie Park', 'Alphen Park'], ['boksburg', 'brakpan', 'kempton-park'], 'On a plot or smallholding, send a location pin. It is quicker than directions.'],
        'edenvale' => ['Edenvale', 'East of Joburg', 'Compact suburbs and cluster homes close to the R24. Washing machines here often sit in narrow kitchens, and we repair them without dragging them through the house.', ['Greenstone Hill', 'Dowerglen', 'Dunvegan', 'Eden Glen', 'Illiondale', 'Marais Steyn Park', 'Hurleyvale', 'Sebenza', 'Modderfontein', 'Linksfield', 'Lyndhurst'], ['bedfordview', 'kempton-park', 'germiston'], 'Clusters often share one geyser supply board, so know which breaker is yours before you switch anything off.'],
        'bedfordview' => ['Bedfordview', 'East of Joburg', 'Large kitchens with double ovens and American-style fridges. Heavy appliances and polished floors need care, and we take it.', ['Bedfordview Central', 'St Andrews', 'Senderwood', 'Essexwold', 'Oriel', 'Bedford Gardens', 'Morninghill', 'Primrose Hill', 'Malvern East'], ['edenvale', 'germiston', 'johannesburg'], 'For plumbed-in fridges, please know where the water shut-off is. It is usually under the sink.'],
        'brakpan' => ['Brakpan', 'East Rand', 'A good appliance is worth repairing before it is worth replacing. If a part can be saved, we would rather save it.', ['Dalview', 'Brenthurst', 'Minnebron', 'Dalpark', 'Sonneveld', 'Larrendale', 'Brakpan North', 'Tsakane', 'Geluksdal', 'Langaville'], ['benoni', 'boksburg', 'germiston'], 'Chest freezers last longest when they are defrosted twice a year. Let the ice melt by itself; never chip it.'],
        'eldorado-park' => ['Eldorado Park', 'South of Soweto', 'Soweto\'s neighbour. A message on WhatsApp and a photo of the fault is all it takes to get a visit arranged.', ['Eldorado Park Proper', 'Eldorado Park Extension 1 to 9', 'Klipspruit West', 'Nancefield', 'Kliptown', 'Bushkoppies', 'Goudrand'], ['soweto', 'lenasia', 'ennerdale', 'johannesburg'], 'In flats without parking, tell us the block and stair; it saves a walk with the toolbox.'],
        'ennerdale' => ['Ennerdale', 'Deep south', 'Ennerdale, Orange Farm and the suburbs along the Golden Highway are a long way from most workshops, so we come to you.', ['Ennerdale Extension 1 to 14', 'Mid-Ennerdale', 'Finetown', 'Lawley', 'Orange Farm', 'Lakeside', 'Grasmere', 'Poortjie', 'Hopefield'], ['lenasia', 'eldorado-park', 'alberton'], 'Book early in the day for the best chance of a same-day slot.'],
    ];
}

/** @return array<string, array> Fault guides: one page per thing people type into Google. */
function faults(): array
{
    static $f = null;
    if ($f !== null) return $f;
    $f = require __DIR__ . '/faults.php';
    return $f;
}

/** @return array<int, array> [brand, appliance, code, meaning, what to try, service slug] */
function errorCodes(): array
{
    return [
        ['Samsung', 'Washing machine', '4E', 'Water supply problem: the machine is not filling in time', 'Open the tap fully, straighten the inlet hose and clean the small mesh filter where the hose meets the machine.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', '4C', 'Water supply problem (newer display for 4E)', 'Same as 4E: tap, hose, mesh filter. If water is running and the code stays, the inlet valve needs testing.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', '5E', 'Water is not draining', 'Clean the pump filter at the bottom front and check the drain hose for a kink. Coins and hair clips are the usual culprits.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', '5C', 'Water is not draining (newer display for 5E)', 'Same as 5E. If the filter is clean and the pump hums without pumping, the pump has failed.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'UE', 'Load is unbalanced, so the spin was stopped', 'Spread the washing out and add or remove an item. If it happens with every load, the suspension is worn.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'Ub', 'Load is unbalanced (newer display for UE)', 'Rearrange the load and check that the machine stands level on all four feet.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'dE', 'Door is not closed or not locking', 'Check for clothing caught in the seal and close the door firmly. If it clicks shut and the code stays, the door lock is faulty.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'dC', 'Door is not closed or not locking (newer display for dE)', 'Same as dE.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'HE', 'Water is not heating correctly', 'Try a cold programme. If cold works, the heater or its wiring needs testing.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'LE', 'Water leak or water level too low', 'Switch off, close the tap and look under the machine. Check that the drain hose is not hanging too low.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', '3E', 'Motor problem', 'Switch off for a few minutes and try a small load. If it returns, the motor, its sensor or the board needs testing.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'OE', 'Too much water in the drum', 'Switch off and close the tap. The inlet valve may be stuck open or the level sensor has failed.', 'washing-machine-repairs'],
        ['Samsung', 'Washing machine', 'tE', 'Temperature sensor problem', 'Restart once. If it returns, the sensor needs testing.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'IE', 'Water is not filling in time', 'Open the tap, straighten the hose and clean the inlet mesh.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'OE', 'Water is not draining', 'Clean the pump filter and check the drain hose.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'UE', 'Load is unbalanced', 'Spread the washing out. With every load: suspension or levelling.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'dE', 'Door open or not locking', 'Clear the seal and shut the door firmly. If it stays, the lock needs replacing.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'LE', 'Motor overload or motor sensor fault', 'Remove some of the load, switch off for half an hour and try again.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'FE', 'Overfilling', 'Switch off and close the tap. The inlet valve or level sensor needs testing.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'PE', 'Water level sensor fault', 'Restart once. If it returns, the sensor or its hose needs attention.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'tE', 'Temperature sensor or heating fault', 'Try a cold wash. If cold works, book a test of the sensor and heater.', 'washing-machine-repairs'],
        ['LG', 'Washing machine', 'CL', 'Child lock is on (not a fault)', 'Hold the child lock button for about three seconds.', 'washing-machine-repairs'],
        ['Hisense', 'Washing machine', 'F01', 'Water is not filling', 'Tap, hose and inlet mesh first.', 'washing-machine-repairs'],
        ['Hisense', 'Washing machine', 'F03', 'Water is not draining', 'Clean the pump filter and check the drain hose.', 'washing-machine-repairs'],
        ['Hisense', 'Washing machine', 'F13', 'Door lock fault', 'Check the seal for trapped clothing and close the door again.', 'washing-machine-repairs'],
        ['Hisense', 'Washing machine', 'Unb', 'Load is unbalanced', 'Rearrange the washing and check the feet are level.', 'washing-machine-repairs'],
        ['LG', 'Fridge', 'Er FF', 'Freezer fan is not turning', 'Often ice around the fan. Book a defrost-circuit test; a manual defrost only hides it for a while.', 'fridge-repairs'],
        ['LG', 'Fridge', 'Er rF', 'Fridge fan is not turning', 'Make sure nothing blocks the vents. If it returns the fan needs testing.', 'fridge-repairs'],
        ['LG', 'Fridge', 'Er dH', 'Defrost took too long or failed', 'The defrost heater or its sensor needs testing before the evaporator ices up.', 'fridge-repairs'],
        ['LG', 'Fridge', 'Er IF', 'Ice maker fan problem', 'Check that the ice compartment door closes fully. If it returns, ice is blocking the fan.', 'fridge-repairs'],
        ['LG', 'Fridge', 'Er CO', 'Boards are not communicating', 'Unplug for a few minutes. If it returns, the wiring or a board needs testing.', 'fridge-repairs'],
    ];
}

/** Home page and prices FAQ; the numbers come from settings. */
function siteFaqs(): array
{
    $c = callout();
    $g = biz('biz_guarantee');
    return [
        ["How much is the call-out?", "The call-out is R{$c}. If we repair the appliance, the call-out is free: you only pay for the repair."],
        ['Do you come to my home?', 'Yes. We are a mobile service. The technician comes to your house, flat or complex and repairs the appliance there, so you do not need a bakkie.'],
        ['Can you come today?', 'We offer same-day service, seven days a week. The earlier in the day you WhatsApp or call, the better the chance of an early slot.'],
        ['Is the repair guaranteed?', "Yes, our repairs carry a {$g}-month guarantee."],
        ['Which appliances do you repair?', 'Fridges and freezers, washing machines, stoves and hobs, ovens, microwaves and geysers.'],
        ['Which areas do you cover?', 'Soweto and 19 other areas around Johannesburg, from Randfontein in the west to Benoni and Brakpan in the east. See the full list on our areas page.'],
        ['What should I send you on WhatsApp?', 'Your area, what the appliance is doing (or not doing), and a photo of the appliance and its model sticker. A short video of the noise or the display helps a lot.'],
        ['Is it worth repairing an old appliance?', 'Often, yes. Many faults are a small part such as a fan, pump, plate or thermostat. Once we have tested it we will tell you the price so that you can decide.'],
    ];
}

/** Photos shown on the Our work page and in page headers: [file, alt, service]. */
function workPhotos(): array
{
    return [
        ['board-multimeter', 'Technician testing a washing machine control board with a multimeter', 'washing-machine-repairs'],
        ['fridge-compressor', 'New filter drier being fitted next to a fridge compressor', 'fridge-repairs'],
        ['stove-multimeter', 'Testing the plates of an electric stove with a multimeter', 'stove-repairs'],
        ['washer-front-loader-opened', 'Front loader washing machine with the lid off during a repair', 'washing-machine-repairs'],
        ['fridge-ice-drawer-repair', 'Freezer compartment opened for a defrost repair', 'fridge-repairs'],
        ['washer-board-out', 'Control board removed from a top loader washing machine', 'washing-machine-repairs'],
        ['fridge-display-28', 'Samsung fridge display showing 28 degrees before repair', 'fridge-repairs'],
        ['geysers', 'Two vertical geysers with copper piping in a plant room', 'geyser-repairs'],
        ['hob-oven-samet', 'Built-in hob and oven', 'oven-repairs'],
        ['fridge-double-door-open', 'Double door fridge with water dispenser', 'fridge-repairs'],
        ['washer-hisense-top-loader', 'Hisense top loader washing machine running a wash', 'washing-machine-repairs'],
        ['hob-oven-white', 'Built-in hob and oven in a white counter', 'stove-repairs'],
        ['fridge-top-freezer-open', 'Top freezer fridge with the freezer door open', 'fridge-repairs'],
        ['washer-samsung-front-loader', 'Samsung front loader washing machine spinning', 'washing-machine-repairs'],
        ['fridge-black-side-by-side', 'Black side-by-side fridge with dispenser', 'fridge-repairs'],
        ['washer-front-loader-display', 'Front loader washing machine display lit up during a wash', 'washing-machine-repairs'],
        ['fridge-display-14-9', 'Fridge display panel with freezer and fridge temperatures', 'fridge-repairs'],
        ['washer-13kg-top-loader', '13 kg top loader washing machine drum', 'washing-machine-repairs'],
        ['fridge-mirror-side-by-side', 'Mirror-finish side-by-side fridge in a kitchen', 'fridge-repairs'],
        ['washer-samsung-top-loader', 'Samsung top loader washing machine', 'washing-machine-repairs'],
        ['fridge-bottom-freezer', 'Bottom freezer fridge with water dispenser', 'fridge-repairs'],
        ['washer-turbodrum', 'Top loader washing machine filled with water', 'washing-machine-repairs'],
        ['fridge-side-by-side-open', 'Side-by-side fridge with the door open', 'fridge-repairs'],
        ['fridge-dispenser', 'Fridge water dispenser panel', 'fridge-repairs'],
    ];
}

function faultsFor(string $service): array
{
    return array_filter(faults(), fn($f) => $f['service'] === $service);
}
