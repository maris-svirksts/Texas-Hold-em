<?php
/**
 * A script to evaluate Texas Hold'em hands and determine their strength in ascending order.
 *
 * Input from stdin, reads till EOF.
 * Example input: 4cKs4h8s7s Ad4s Ac4d As9s KhKd 5d6d
 *
 * Output to stdout, line per line.
 * Example output: Ac4d=Ad4s 5d6d As9s KhKd
 *
 * Version: 1.0
 *
 * @package texas_hold_em
 **/

// Per home task requirement: only card data or custom built error messages should be displayed.
error_reporting( E_ERROR );
ini_set( 'auto_detect_line_endings', true );

/**
 * Add required files.
 */
require_once __DIR__ . '/class-game.php';

while ( ! feof( STDIN ) ) {
	$input_line = trim( fgets( STDIN ) );

	$game = new Game( $input_line );

	if ( $game->validation_ok ) {

		$game->check_flush();
		$game->check_four_of_a_kind();
		$game->check_full_house();
		$game->check_straight();
		$game->check_three_of_a_kind();
		$game->check_two_pairs();
		$game->two_of_a_kind();
		$game->high_card();

		$game->echo_results();
	}
}
