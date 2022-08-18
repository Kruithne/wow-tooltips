<?php
	define('FLAG_IS_EXPRESSION', 0x1);
	define('FLAG_IS_GARR_AB', 0x2);
	define('FLAG_IS_PARAMS', 0x4);

	define('TOKEN_STRING', 0x1);
	define('TOKEN_VARIABLE', 0x2);
	define('TOKEN_CONDITION_SWITCH', 0x3);
	define('TOKEN_CONDITION_CHAIN', 0x4);
	define('TOKEN_EXPRESSION', 0x5);
	define('TOKEN_MATH_OPERATOR', 0x6);
	define('TOKEN_CONDITION', 0x7);
	define('TOKEN_CONDITIONAL_OPERATOR', 0x8);
	define('TOKEN_EXTERNAL_VARIABLE', 0x9);
	define('TOKEN_NAMED_VARIABLE', 0xA);
	define('TOKEN_DIFFICULTY_BLOCK', 0xB);
	define('TOKEN_GROUP', 0xC);
	define('TOKEN_ROOT', 0xD);
	define('TOKEN_NUMBER', 0xE);
	define('TOKEN_BULLET', 0xF);
	define('TOKEN_CONDITION_EQUALS', 0x10);
	define('TOKEN_FUNCTION_PARAMS', 0x11);
	define('TOKEN_MATH_FUNCTION', 0x12);
	define('TOKEN_UI_ESCAPE_SEQUENCE', 0x13);

	define('UI_ESCAPE_COLOR', 0x1);
	define('UI_ESCAPE_ICON', 0x2);
	define('UI_ESCAPE_RESET', 0x3);
	define('UI_ESCAPE_HYPERLINK', 0x4);
	define('UI_ESCAPE_HYPERLINK_END', 0x5);

	define('EXTERNAL_VAR_SPELL_NAME', 0x1);
	define('EXTERNAL_VAR_SPELL_DESC', 0x2);
	define('EXTERNAL_VAR_SPELL_ICON', 0x3);
	define('EXTERNAL_VAR_SPELL_TOOLTIP', 0x4);
	define('EXTERNAL_VAR_LOOT_SPEC', 0x5);
	define('EXTERNAL_VAR_SPELL_ID', 0x6);
	define('EXTERNAL_VAR_SPELL_AURA', 0x7);
	define('EXTERNAL_VAR_GARR_AB_DESC', 0x8);
	define('EXTERNAL_VAR_GARR_BUILDING', 0x9);
	define('EXTERNAL_VAR_AURA_CASTER', 0xA);
	define('EXTERNAL_VAR_GARR_SPELL_DESC', 0xB);
	define('EXTERNAL_VAR_SWITCH', 0xC);
	define('EXTERNAL_VAR_CLASS', 0xD);
	define('EXTERNAL_VAR_CLASS_SPEC', 0xE);
	define('EXTERNAL_VAR_VERS_DAMAGE', 0xF);
	define('EXTERNAL_VAR_NULL', 0x10);
	define('EXTERNAL_VAR_RUNECARVE_ABILITY', 0x11);

	define('MATH_DIVIDE', 0x1);
	define('MATH_MULTIPLY', 0x2);
	define('MATH_ADDITION', 0x3);
	define('MATH_SUBTRACT', 0x4);

	define('CONDITIONAL_OPERATOR_AND', 0x1);
	define('CONDITIONAL_OPERATOR_OR', 0x2);
	define('CONDITIONAL_OPERATOR_NOT', 0x3);

	define('SWITCH_TYPE_GENDER', 0x1);
	define('SWITCH_TYPE_PLURALITY', 0x2);

	define('CHARSET_PLURALITY', ['l', 'L']);
	define('CHARSET_GENDER', ['g', 'G']);
	define('CHARSET_CONDITIONS', ['&', '!', '|']);
	define('CHARSET_MATH', ['+', '/', '*', '-']);

	define('MAP_MATH_OPERATOR', [
		'+' => MATH_ADDITION,
		'*' => MATH_MULTIPLY,
		'-' => MATH_SUBTRACT,
		'/' => MATH_DIVIDE
	]);

	define('MATH_FUNCTION_FLOOR', 0x1);
	define('MATH_FUNCTION_CONDITION', 0x2);
	define('MATH_FUNCTION_GREATER_THAN', 0x3);
	define('MATH_FUNCTION_MAX', 0x4);

	define('MAP_MATH_FUNCTIONS', [
		'floor' => MATH_FUNCTION_FLOOR,
		'cond' => MATH_FUNCTION_CONDITION,
		'gt' => MATH_FUNCTION_GREATER_THAN,
		'max' => MATH_FUNCTION_MAX
	]);

	define('VAR_TYPE_UNKNOWN', 0x1);
	define('VAR_TYPE_EFFECT_AURA_PERIOD', 0x2);
	define('VAR_TYPE_EFFECT_AMPLITUDE', 0x3);
	define('VAR_TYPE_MISC_DURATION', 0x4);
	define('VAR_TYPE_EFFECT_BASE_POINTS', 0x5);
	define('VAR_TYPE_EFFECT_RADIUS_0', 0x6);
	define('VAR_TYPE_EFFECT_RADIUS_1', 0x7);
	define('VAR_TYPE_MISC_RANGE_MAX', 0x8);
	define('VAR_TYPE_MISC_RANGE_MIN', 0x9);
	define('VAR_TYPE_AURA_CUMULATIVE', 0xA);
	define('VAR_TYPE_EFFECT_CHAIN_TARGETS', 0xB);
	define('VAR_TYPE_TARGET_RESTRICTIONS_MAX_TARGETS', 0xC);
	define('VAR_TYPE_TARGET_RESTRICTIONS_MAX_LEVEL', 0xD);
	define('VAR_TYPE_POWER_MANA_COST', 0xE);
	define('VAR_TYPE_EFFECT_CHAIN_AMPLITUDE', 0xF);
	define('VAR_TYPE_EFFECT_VARIANCE_MIN', 0x10);
	define('VAR_TYPE_EFFECT_VARIANCE_MAX', 0x11);
	define('VAR_TYPE_AURA_PROC_CHANCE', 0x12);
	define('VAR_TYPE_ITEM_OWB', 0x13);
	define('VAR_TYPE_ITEM_OWS', 0x14);
	define('VAR_TYPE_ITEM_RWS', 0x15);
	define('VAR_TYPE_ITEM_MWB', 0x16);
	define('VAR_TYPE_ITEM_MWS', 0x17);
	define('VAR_TYPE_PRIMARY_STAT', 0x18);
	define('VAR_TYPE_ENCHANTMENT', 0x19);
	define('VAR_TYPE_ENCHANTMENT_MAX', 0x1A);
	define('VAR_TYPE_EFFECT', 0x1B);
	define('VAR_TYPE_PLAYER_LEVEL', 0x1C);
	define('VAR_TYPE_CONTENT_TUNING_MIN_LEVEL', 0x1D);
	define('VAR_TYPE_CONTENT_TUNING_MAX_LEVEL', 0x1E);
	define('VAR_TYPE_EFFECT_POINTS_PER_RESOURCE', 0x1F);
	define('VAR_TYPE_STAT_STRENGTH', 0x20);
	define('VAR_TYPE_STAT_AGILITY', 0x21);
	define('VAR_TYPE_STAT_INTELLECT', 0x22);
	define('VAR_TYPE_STAT_SPIRIT', 0x23);
	define('VAR_TYPE_STAT_STAMINA', 0x24);
	define('VAR_TYPE_AURA_PROC_RECOVERY', 0x25);
	define('VAR_TYPE_SPELL_AURA_PROC_PER_MINUTE', 0x26);
	define('VAR_TYPE_PLAYER_SPECIALIZATION', 0x27);
	define('VAR_TYPE_LEVEL_MAX', 0x28);
	define('VAR_TYPE_CONDITION_SPECIALIZATION', 0x29);
	define('VAR_TYPE_CONDITION_AURA_ACTIVE', 0x2A);
	define('VAR_TYPE_CONDITION_QUEST_COMPLETE', 0x2B);
	define('VAR_TYPE_CONDITION_SPELL_LEARNED', 0x2C);
	define('VAR_TYPE_STAT_RANGED_ATTACK_POWER', 0x2D);
	define('VAR_TYPE_STAT_ATTACK_POWER', 0x2E);
	define('VAR_TYPE_STAT_SPELL_POWER', 0x2F);
	define('VAR_TYPE_MAX_HEALTH', 0x30);
	define('VAR_TYPE_STAT_SHADOW_PCT', 0x31);
	define('VAR_TYPE_BONUS_COEFFICIENT', 0x32);
	define('VAR_TYPE_WEAPON_DAMAGE_AMOUNT', 0x33);
	define('VAR_TYPE_AURA_PROC_CHARGES', 0x34);
	define('VAR_TYPE_HEARTHSTONE_LOCATION', 0x35);
	define('VAR_TYPE_EFFECT_MISC_VALUE', 0x36);
	define('VAR_TYPE_GARR_AB_ACTION_FLAT', 0x37);
	define('VAR_TYPE_GARR_AB_COMBAT_MAX', 0x38);
	define('VAR_TYPE_GARR_AB_COMBAT_BASE', 0x39);
	define('VAR_TYPE_GARR_AB_ACTION_HOURS', 0x3A);

	define('MAP_GARR_AB_TYPES', [
		'a' => VAR_TYPE_GARR_AB_ACTION_FLAT,
		'm' => VAR_TYPE_GARR_AB_COMBAT_MAX,
		'b' => VAR_TYPE_GARR_AB_COMBAT_BASE,
		'h' => VAR_TYPE_GARR_AB_ACTION_HOURS
	]);

	define('MAP_CONDITION_TYPES', [
		'c' => VAR_TYPE_CONDITION_SPECIALIZATION,
		'a' => VAR_TYPE_CONDITION_AURA_ACTIVE,
		'q' => VAR_TYPE_CONDITION_QUEST_COMPLETE,
		's' => VAR_TYPE_CONDITION_SPELL_LEARNED
	]);

	define('MAP_RADIUS_INDEX', [
		VAR_TYPE_EFFECT_RADIUS_0 => 0,
		VAR_TYPE_EFFECT_RADIUS_1 => 1
	]);

	define('MAP_VARIABLE_TYPES', [
		't' => VAR_TYPE_EFFECT_AURA_PERIOD,
		'p' => VAR_TYPE_EFFECT_AURA_PERIOD, // Difference between p and t?
		'e' => VAR_TYPE_EFFECT_AMPLITUDE,
		'd' => VAR_TYPE_MISC_DURATION,
		'm' => VAR_TYPE_EFFECT_VARIANCE_MIN,
		'M' => VAR_TYPE_EFFECT_VARIANCE_MAX,
		's' => VAR_TYPE_EFFECT_BASE_POINTS, // Difference between s and S?
		'S' => VAR_TYPE_EFFECT_BASE_POINTS,
		'w' => VAR_TYPE_EFFECT_BASE_POINTS,
		'o' => VAR_TYPE_EFFECT_BASE_POINTS, // Difference between o and O?
		'a' => VAR_TYPE_EFFECT_RADIUS_0, // Radius index is defined by the letter, not a tailing index.
		'A' => VAR_TYPE_EFFECT_RADIUS_1, // Radius index is defined by the letter, not a tailing index.
		'R' => VAR_TYPE_MISC_RANGE_MIN,
		'r' => VAR_TYPE_MISC_RANGE_MAX,
		'u' => VAR_TYPE_AURA_CUMULATIVE,// Difference between u and U?
		'x' => VAR_TYPE_EFFECT_CHAIN_TARGETS, // *Bugged in-game, see note below.
		'i' => VAR_TYPE_TARGET_RESTRICTIONS_MAX_TARGETS, // Difference between i and I?
		'v' => VAR_TYPE_TARGET_RESTRICTIONS_MAX_LEVEL, // Difference between v and V?
		'c' => VAR_TYPE_POWER_MANA_COST,
		'f' => VAR_TYPE_EFFECT_CHAIN_AMPLITUDE,// Difference between f and F?
		'h' => VAR_TYPE_AURA_PROC_CHANCE,// Difference between h and H?
		'n' => VAR_TYPE_AURA_PROC_CHARGES,
		'owb' => VAR_TYPE_ITEM_OWB, // Assumed: Off-hand weapon base damage **
		'ows' => VAR_TYPE_ITEM_OWS, // Assumed: Off-hand weapon speed (not observed in-game) **
		'rws' => VAR_TYPE_ITEM_RWS, // Assumed: Ranged weapon speed (not observed in-game) **
		'mws' => VAR_TYPE_ITEM_MWS, // Assumed: Main-weapon speed (not observed in-game) **
		'mwb' => VAR_TYPE_ITEM_MWB, // Assumed: Main-weapon base damage (not observed in-game) **
		'pri' => VAR_TYPE_PRIMARY_STAT, // Intellect, Strength, etc
		'ec' => VAR_TYPE_ENCHANTMENT,
		'ecix' => VAR_TYPE_ENCHANTMENT_MAX, // SpellItemEnchantment.ItemLevelMax
		'sw' => VAR_TYPE_EFFECT, // Implementation based on SpellEffect.Effect
		'pl' => VAR_TYPE_PLAYER_LEVEL,
		'ctrmin' => VAR_TYPE_CONTENT_TUNING_MIN_LEVEL, // ContentTuning.MinLevel
		'ctrmax' => VAR_TYPE_CONTENT_TUNING_MAX_LEVEL, // ContentTuning.MaxLevel
		'b' => VAR_TYPE_EFFECT_POINTS_PER_RESOURCE, // SpellEffect.EffectPointsPerResource
		'maxcast' => VAR_TYPE_LEVEL_MAX, // SpellLevels.MaxLevel
		'str' => VAR_TYPE_STAT_STRENGTH,
		'int' => VAR_TYPE_STAT_INTELLECT,
		'sta' => VAR_TYPE_STAT_STAMINA,
		'spr' => VAR_TYPE_STAT_SPIRIT,
		'agi' => VAR_TYPE_STAT_AGILITY,
		'spec' => VAR_TYPE_PLAYER_SPECIALIZATION,
		'proccooldown' => VAR_TYPE_AURA_PROC_RECOVERY, // SpellAuraOptions.ProcCategoryRecovery
		'procrppm' => VAR_TYPE_SPELL_AURA_PROC_PER_MINUTE, // SpellProcsPerMinute.BaseProcRate
		'rap' => VAR_TYPE_STAT_RANGED_ATTACK_POWER,
		'ap' => VAR_TYPE_STAT_ATTACK_POWER,
		'sp' => VAR_TYPE_STAT_SPELL_POWER,
		'sph' => VAR_TYPE_STAT_SPELL_POWER, // Difference from sp?
		'spfr' => VAR_TYPE_STAT_SPELL_POWER, // Difference from sp?
		'sps' => VAR_TYPE_STAT_SPELL_POWER, // Difference from sp? (Seen on 27243)
		'mhp' => VAR_TYPE_MAX_HEALTH,
		'ps' => VAR_TYPE_STAT_SHADOW_PCT, // Seen on 209780
		'bc' => VAR_TYPE_BONUS_COEFFICIENT, // Seen on 209780
		'wdpa' => VAR_TYPE_WEAPON_DAMAGE_AMOUNT, // Seen on 233179
		'z' => VAR_TYPE_HEARTHSTONE_LOCATION,
		'q' => VAR_TYPE_EFFECT_MISC_VALUE, // Is prefix value or effect index?
	]);

	// * The in-game dungeon journal does not correctly parse the trailing effect index
	// literal on an effect chain targets variable. 1234x2 should be rendered using the
	// second (2) effect index for the spell 1234. Instead, the 2 is ignored and always
	// uses the first entry. This does not occur in spell tooltips, and as of writing 
	// there are no uses of anything higher than x1 in the dungeon sections, however
	// due to the index not being parsed, the trailing 1 is left as a text literal
	// causing *ALL* chain effect numbers in the in-game dungeon journal to appear
	// incorrectly. "Effects 3" targets will appear as "Effects 31" instead.

	// ** Assumptions for VAR_TYPE_ITEM_*** variables have been made based on de-compiling
	// the exe function that handles them. Only _OWB has been seen in-game (49020).

	// Due to reasons that make no sense, the game itself handles a handful of erroneous
	// variable names to cover designer misspellings. Checking them all against the actual
	// data, very few actually appear in-game, so we're not handling them.
	// In the event you do need to handle one of them, the known variants are listed in a
	// comment beside each variable, simply add a new mapping entry for it.
	// Additionally, the game also handles a few erroneous uses of the @ symbol to indicate
	// an external variable, those cannot be handled without additional parser work.
	define('MAP_EXTERNAL_VAR', [
		'spellname' => EXTERNAL_VAR_SPELL_NAME, // spellnamme, spelnamme
		'spelldesc' => EXTERNAL_VAR_SPELL_DESC, // speldesc, seplldesc, spelldec, spellesc, pelldesc
		'spellicon' => EXTERNAL_VAR_SPELL_ICON,
		'spelltooltip' => EXTERNAL_VAR_SPELL_TOOLTIP,
		'lootspec' => EXTERNAL_VAR_LOOT_SPEC,
		'spellid' => EXTERNAL_VAR_SPELL_ID,
		'spellaura' => EXTERNAL_VAR_SPELL_AURA,
		'garrabdesc' => EXTERNAL_VAR_GARR_AB_DESC,
		'garrbuilding' => EXTERNAL_VAR_GARR_BUILDING,
		//'garrspelldesc' => EXTERNAL_VAR_GARR_SPELL_DESC, // Not observed in-game.
		//'auracaster' => EXTERNAL_VAR_AURA_CASTER, // Not observed in-game.
		//'rank' => EXTERNAL_VAR_RANK, // Not observed in-game.
		//'rankswitch' => EXTERNAL_VAR_RANK_SWITCH, // Not observed in-game.
		'switch' => EXTERNAL_VAR_SWITCH, 
		'class' => EXTERNAL_VAR_CLASS,
		'classspec' => EXTERNAL_VAR_CLASS_SPEC,
		'versadmg' => EXTERNAL_VAR_VERS_DAMAGE,
		//'versadmgred' => EXTERNAL_VAR_VERS_DAMAGE_RED, // Not observed in-game.
		//'null' => EXTERNAL_VAR_NULL, // Not observed in-game.
		//'runecarveability' => EXTERNAL_VAR_RUNECARVE_ABILITY, // Exists in data, but not an implemented feature.
	]);

	// Defines the BODMAS order of arithmetic evaluation.
	define('EXPR_EVAL_ORDER', [
		MATH_DIVIDE => 0,
		MATH_MULTIPLY => 1,
		MATH_ADDITION => 2,
		MATH_SUBTRACT => 3
	]);

	// Defines time periods in milliseconds.
	define('MS_SECOND', 1000);
	define('MS_MINUTE', MS_SECOND * 60);
	define('MS_HOUR', MS_MINUTE * 60);
	define('MS_DAY', MS_HOUR * 24);