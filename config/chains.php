<?php

/*
|--------------------------------------------------------------------------
| Chains
|--------------------------------------------------------------------------
|
| Keyed by slug (used in URLs and the locations.chain column). "aliases" are
| matched against submitted location names after normalisation (lower case,
| punctuation stripped), so "Raising Cane's Chicken Fingers", "Cane's" and
| "raising canes" all resolve to raising-canes. ice_type is the ice the chain
| is known for; null means "varies / not known for good ice" and is kept so
| the chain can still be grouped.
|
*/

return [
    'sonic' => ['name' => 'Sonic Drive-In', 'aliases' => ['sonic drive in', 'sonic'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'chick-fil-a' => ['name' => 'Chick-fil-A', 'aliases' => ['chick fil a', 'chickfila'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'raising-canes' => ['name' => "Raising Cane's", 'aliases' => ['raising canes', 'canes'], 'ice_type' => 'pebble', 'category' => 'fast_food'],
    'zaxbys' => ['name' => "Zaxby's", 'aliases' => ['zaxbys'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'cook-out' => ['name' => 'Cook Out', 'aliases' => ['cook out', 'cookout'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'whataburger' => ['name' => 'Whataburger', 'aliases' => ['whataburger'], 'ice_type' => null, 'category' => 'fast_food'],
    'jimmy-johns' => ['name' => "Jimmy John's", 'aliases' => ['jimmy johns'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'mod-pizza' => ['name' => 'MOD Pizza', 'aliases' => ['mod pizza'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'torchys' => ['name' => "Torchy's Tacos", 'aliases' => ['torchys tacos', 'torchys'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'daves-hot-chicken' => ['name' => "Dave's Hot Chicken", 'aliases' => ['daves hot chicken'], 'ice_type' => 'nugget', 'category' => 'fast_food'],
    'nandos' => ['name' => "Nando's", 'aliases' => ['nandos', 'nandos peri peri'], 'ice_type' => null, 'category' => 'fast_food'],
    'quiktrip' => ['name' => 'QuikTrip', 'aliases' => ['quiktrip', 'quik trip', 'qt'], 'ice_type' => 'nugget', 'category' => 'convenience'],
    'buc-ees' => ['name' => "Buc-ee's", 'aliases' => ['buc ees', 'bucees'], 'ice_type' => 'nugget', 'category' => 'convenience'],
    'sheetz' => ['name' => 'Sheetz', 'aliases' => ['sheetz'], 'ice_type' => 'nugget', 'category' => 'convenience'],
    'wawa' => ['name' => 'Wawa', 'aliases' => ['wawa'], 'ice_type' => null, 'category' => 'convenience'],
    'racetrac' => ['name' => 'RaceTrac', 'aliases' => ['racetrac', 'race trac'], 'ice_type' => 'nugget', 'category' => 'convenience'],
    '7-eleven' => ['name' => '7-Eleven', 'aliases' => ['7 eleven', '7eleven', 'seven eleven'], 'ice_type' => null, 'category' => 'convenience'],
    'circle-k' => ['name' => 'Circle K', 'aliases' => ['circle k'], 'ice_type' => null, 'category' => 'convenience'],
    'caseys' => ['name' => "Casey's", 'aliases' => ['caseys', 'caseys general store'], 'ice_type' => null, 'category' => 'convenience'],
    'loves' => ['name' => "Love's Travel Stop", 'aliases' => ['loves travel stop', 'loves'], 'ice_type' => null, 'category' => 'convenience'],
    'speedway' => ['name' => 'Speedway', 'aliases' => ['speedway'], 'ice_type' => null, 'category' => 'convenience'],
    'shell' => ['name' => 'Shell', 'aliases' => ['shell'], 'ice_type' => null, 'category' => 'convenience'],
    'dutch-bros' => ['name' => 'Dutch Bros Coffee', 'aliases' => ['dutch bros coffee', 'dutch bros'], 'ice_type' => 'nugget', 'category' => 'coffee'],
    'swig' => ['name' => 'Swig', 'aliases' => ['swig'], 'ice_type' => 'pebble', 'category' => 'coffee'],
    'starbucks' => ['name' => 'Starbucks', 'aliases' => ['starbucks'], 'ice_type' => null, 'category' => 'coffee'],
    'dunkin' => ['name' => "Dunkin'", 'aliases' => ['dunkin', 'dunkin donuts'], 'ice_type' => null, 'category' => 'coffee'],
];
