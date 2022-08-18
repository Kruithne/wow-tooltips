<?php
	// https://wow.tools/dbc/?dbc=enumeratedstring&build=2.5.1.39640#page=1&colFilter[4]=601
	define('ITEM_BINDING', [
		1 => 'Binds when picked up',
		2 => 'Binds when equipped',
		2 => 'Binds when used',
		4 => 'Quest Item'
	]);

	// SharedXML/FormattingUtil.lua:GetMoneyString()
	define('COPPER_PER_SILVER', 100);
	define('SILVER_PER_GOLD', 100);
	define('COPPER_PER_GOLD', COPPER_PER_SILVER * SILVER_PER_GOLD);

	define('ITEM_STAT_MANA', 0);
	define('ITEM_STAT_HEALTH', 1);
	define('ITEM_STAT_AGILITY', 3);
	define('ITEM_STAT_STRENGTH', 4);
	define('ITEM_STAT_INTELLECT', 5);
	define('ITEM_STAT_SPIRIT', 6);
	define('ITEM_STAT_STAMINA', 7);
	define('ITEM_STAT_DEFENSE_SKILL_RATING', 12);
	define('ITEM_STAT_DODGE_RATING', 13);
	define('ITEM_STAT_PARRY_RATING', 14);
	define('ITEM_STAT_BLOCK_RATING', 15);
	define('ITEM_STAT_HIT_MELEE_RATING', 16);
	define('ITEM_STAT_HIT_RANGED_RATING', 17);
	define('ITEM_STAT_HIT_SPELL_RATING', 18);
	define('ITEM_STAT_CRIT_MELEE_RATING', 19);
	define('ITEM_STAT_CRIT_RANGED_RATING', 20);
	define('ITEM_STAT_CRIT_SPELL_RATING', 21);
	define('ITEM_STAT_CORRUPTION', 22);
	define('ITEM_STAT_CORRUPTION_RESISTANCE', 23);
	define('ITEM_STAT_MODIFIED_CRAFTING_STAT_1', 24);
	define('ITEM_STAT_MODIFIED_CRAFTING_STAT_2', 25);
	define('ITEM_STAT_CRIT_TAKEN_RANGED_RATING', 26);
	define('ITEM_STAT_CRIT_TAKEN_SPELL_RATING', 27);
	define('ITEM_STAT_HASTE_MELEE_RATING', 28);
	define('ITEM_STAT_HASTE_RANGED_RATING', 29);
	define('ITEM_STAT_HASTE_SPELL_RATING', 30);
	define('ITEM_STAT_HIT_RATING', 31);
	define('ITEM_STAT_CRIT_RATING', 32);
	define('ITEM_STAT_HIT_TAKEN_RATING', 33);
	define('ITEM_STAT_CRIT_TAKEN_RATING', 34);
	define('ITEM_STAT_RESILIENCE_RATING', 35);
	define('ITEM_STAT_HASTE_RATING', 36);
	define('ITEM_STAT_EXPERTISE_RATING', 37);
	define('ITEM_STAT_ATTACK_POWER', 38);
	define('ITEM_STAT_RANGED_ATTACK_POWER', 39);
	define('ITEM_STAT_VERSATILITY', 40);
	define('ITEM_STAT_SPELL_HEALING_DONE', 41);
	define('ITEM_STAT_SPELL_DAMAGE_DONE', 42);
	define('ITEM_STAT_MANA_REGENERATION', 43);
	define('ITEM_STAT_ARMOR_PENETRATION_RATING', 44);
	define('ITEM_STAT_SPELL_POWER', 45);
	define('ITEM_STAT_HEALTH_REGEN', 46);
	define('ITEM_STAT_SPELL_PENETRATION', 47);
	define('ITEM_STAT_BLOCK_VALUE', 48);
	define('ITEM_STAT_MASTERY_RATING', 49);
	define('ITEM_STAT_EXTRA_ARMOR', 50);
	define('ITEM_STAT_FIRE_RESISTANCE', 51);
	define('ITEM_STAT_FROST_RESISTANCE', 52);
	define('ITEM_STAT_HOLY_RESISTANCE', 53);
	define('ITEM_STAT_SHADOW_RESISTANCE', 54);
	define('ITEM_STAT_NATURE_RESISTANCE', 55);
	define('ITEM_STAT_ARCANE_RESISTANCE', 56);
	define('ITEM_STAT_PVP_POWER', 57);
	define('ITEM_STAT_CR_AMPLIFY', 58);
	define('ITEM_STAT_CR_MULTISTRIKE', 59);
	define('ITEM_STAT_CR_READINESS', 60);
	define('ITEM_STAT_CR_SPEED', 61);
	define('ITEM_STAT_CR_LIFESTEAL', 62);
	define('ITEM_STAT_CR_AVOIDANCE', 63);
	define('ITEM_STAT_CR_STURDINESS', 64);
	define('ITEM_STAT_AGI_STR_INT', 71);
	define('ITEM_STAT_AGI_STR', 72);
	define('ITEM_STAT_AGI_INT', 73);
	define('ITEM_STAT_STR_INT', 74);

	define('ITEM_STAT_NAMES', [
		0 => 'Mana',
		1 => 'Health',
		3 => 'Agility',
		4 => 'Strength',
		5 => 'Intellect',
		6 => 'Spirit',
		7 => 'Stamina',
		12 => 'Defense',
		13 => 'Dodge',
		14 => 'Parry',
		15 => 'Block',
		16 => 'Hit (Melee)',
		17 => 'Hit (Ranged)',
		18 => 'Hit (Spell)',
		19 => 'Crit (Melee)',
		20 => 'Crit (Ranged)',
		21 => 'Crit (Spell)',
		22 => 'Corruption',
		23 => 'Corruption Resistance',
		24 => 'Random Stat 1',
		25 => 'Random Stat 2',
		26 => 'Critical Strike Avoidance (Ranged)',
		27 => 'Critical Strike Avoidance (Spell)',
		28 => 'Haste (Melee)',
		29 => 'Haste (Ranged)',
		30 => 'Haste (Spell)',
		31 => 'Hit',
		32 => 'Critical Strike',
		33 => 'Hit Avoidance',
		34 => 'Critical Strike Avoidance',
		35 => 'Resilience',
		36 => 'Haste',
		37 => 'Expertise',
		38 => 'Attack Power',
		39 => 'Attack Power (Ranged)',
		40 => 'Versatility',
		41 => 'Bonus Healing',
		42 => 'Bonus Damage',
		43 => 'Mana Regeneration',
		44 => 'Armor Penetration',
		45 => 'Spell Power',
		46 => 'Health Regen',
		47 => 'Spell Penetration',
		48 => 'Block',
		49 => 'Mastery',
		50 => 'Bonus Armor',
		51 => 'Fire Resistance',
		52 => 'Frost Resistance',
		53 => 'Holy Resistance',
		54 => 'Shadow Resistance',
		55 => 'Nature Resistance',
		56 => 'Arcane Resistance',
		57 => 'PvP Power',
		58 => 'Amplify',
		59 => 'Multistrike',
		60 => 'Readiness',
		61 => 'Speed',
		62 => 'Lifesteal',
		63 => 'Avoidance',
		64 => 'Sturdiness',
		65 => 'Unused (7)',
		66 => 'Cleave',
		67 => 'Versatility',
		68 => 'Unused (10)',
		69 => 'Unused (11)',
		70 => 'Unused (12)',
		71 => 'Agility | Strength | Intellect',
		72 => 'Agility | Strength',
		73 => 'Agility | Intellect',
		74 => 'Strength | Intellect'
	]);

	define('ITEM_TRIGGERS', [
		0 => 'Use',
		1 => 'Equip',
		2 => 'Chance on hit'
	]);

	define('COMBAT_RATING_STATS', [
		ITEM_STAT_DODGE_RATING,
		ITEM_STAT_PARRY_RATING,
		ITEM_STAT_BLOCK_RATING,
		ITEM_STAT_HIT_MELEE_RATING,
		ITEM_STAT_HIT_RANGED_RATING,
		ITEM_STAT_HIT_SPELL_RATING,
		ITEM_STAT_CRIT_MELEE_RATING,
		ITEM_STAT_CRIT_RANGED_RATING,
		ITEM_STAT_HIT_RATING,
		ITEM_STAT_CRIT_RATING,
		ITEM_STAT_RESILIENCE_RATING,
		ITEM_STAT_HASTE_RATING,
		ITEM_STAT_EXPERTISE_RATING,
		ITEM_STAT_VERSATILITY,
		ITEM_STAT_MASTERY_RATING,
		ITEM_STAT_CR_MULTISTRIKE,
		ITEM_STAT_CR_SPEED,
		ITEM_STAT_CR_LIFESTEAL,
		ITEM_STAT_CR_AVOIDANCE,
		ITEM_STAT_CR_STURDINESS,
	]);

	DEFINE('INVENTORY_TYPE_NONEQUIPPABLE', 0);
	DEFINE('INVENTORY_TYPE_HEAD', 1);
	DEFINE('INVENTORY_TYPE_NECK', 2);
	DEFINE('INVENTORY_TYPE_SHOULDER', 3);
	DEFINE('INVENTORY_TYPE_SHIRT', 4);
	DEFINE('INVENTORY_TYPE_CHEST', 5);
	DEFINE('INVENTORY_TYPE_WAIST', 6);
	DEFINE('INVENTORY_TYPE_LEGS', 7);
	DEFINE('INVENTORY_TYPE_FEET', 8);
	DEFINE('INVENTORY_TYPE_WRIST', 9);
	DEFINE('INVENTORY_TYPE_HANDS', 10);
	DEFINE('INVENTORY_TYPE_FINGER', 11);
	DEFINE('INVENTORY_TYPE_TRINKET', 12);
	DEFINE('INVENTORY_TYPE_ONEHAND', 13);
	DEFINE('INVENTORY_TYPE_SHIELD', 14);
	DEFINE('INVENTORY_TYPE_RANGED', 15);
	DEFINE('INVENTORY_TYPE_BACK', 16);
	DEFINE('INVENTORY_TYPE_TWOHAND', 17);
	DEFINE('INVENTORY_TYPE_BAG', 18);
	DEFINE('INVENTORY_TYPE_TABARD', 19);
	DEFINE('INVENTORY_TYPE_ROBE', 20);
	DEFINE('INVENTORY_TYPE_MAINHAND', 21);
	DEFINE('INVENTORY_TYPE_OFFHAND', 22);
	DEFINE('INVENTORY_TYPE_HELDINOFFHAND', 23);
	DEFINE('INVENTORY_TYPE_AMMO', 24);
	DEFINE('INVENTORY_TYPE_THROWN', 25);
	DEFINE('INVENTORY_TYPE_RANGEDRIGHT', 26);
	DEFINE('INVENTORY_TYPE_QUIVER', 27);
	DEFINE('INVENTORY_TYPE_RELIC', 28);

	// https://wow.tools/dbc/?dbc=enumeratedstring&build=2.5.1.39640#page=1&colFilter[4]=336
	define('INVENTORY_TYPE_NAME', [
		0 => 'Non-equippable',
		1 => 'Head',
		2 => 'Neck',
		3 => 'Shoulder',
		4 => 'Shirt',
		5 => 'Chest',
		6 => 'Waist',
		7 => 'Legs',
		8 => 'Feet',
		9 => 'Wrist',
		10 => 'Hands',
		11 => 'Finger',
		12 => 'Trinket',
		13 => 'One-Hand',
		14 => 'Off Hand',
		15 => 'Ranged',
		16 => 'Back',
		17 => 'Two-Hand',
		18 => 'Bag',
		19 => 'Tabard',
		20 => 'Chest',
		21 => 'Main Hand',
		22 => 'Off Hand',
		23 => 'Held in Off-hand',
		24 => 'Ammo',
		25 => 'Thrown',
		26 => 'Ranged',
		27 => 'Quiver',
		28 => 'Relic'
	]);

	define('ITEM_SUB_CLASS', [
		// https://wow.tools/dbc/?dbc=enumeratedstring&build=2.5.1.39640#page=1&colFilter[4]=953
		2 => [ // Weapon
			0 => 'Axe',
			1 => 'Axe', //2H
			2 => 'Bow',
			3 => 'Gun',
			4 => 'Mace',
			5 => 'Mace', //2H
			6 => 'Polearm',
			7 => 'Sword',
			8 => 'Sword', //2H
			9 => 'Warglaives',
			10 => 'Staff',
			11 => 'Bear Claws',
			12 => 'Cat Claws',
			13 => 'Fist Weapon',
			14 => 'Miscellaneous',
			15 => 'Dagger',
			16 => 'Thrown',
			17 => 'Spear',
			18 => 'Crossbow',
			19 => 'Wand',
			20 => 'Fishing Pole'
		],

		4 => [ // Armor
			0 => 'Miscellaneous',
			1 => 'Cloth',
			2 => 'Leather',
			3 => 'Mail',
			4 => 'Plate',
			5 => 'Cosmetic',
			6 => 'Shield',
			7 => 'Libram',
			8 => 'Idol',
			9 => 'Totem',
			10 => 'Sigil',
			11 => 'Relic'
		]
	]);