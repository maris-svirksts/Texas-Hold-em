<?php
/**
 * Description:     Texas Hold'em hand comparison.
 * Version:         1.0.0
 *
 * @package         texas_hold_em
 */

/**
 * Log generator class, allows to save data to file.
 *
 * @access public
 * @var bool  $validation_ok - check if validation was passed.
 * @var array $rank_values - an array holding the cards and their values.
 * @return void
 */
class Game {
	public $validation_ok = true;
	private $rank_values  = array(
		'A' => 14,
		'K' => 13,
		'Q' => 12,
		'J' => 11,
		'T' => 10,
		'9' => 9,
		'8' => 8,
		'7' => 7,
		'6' => 6,
		'5' => 5,
		'4' => 4,
		'3' => 3,
		'2' => 2,
	);

	/**
	 * Constructor.
	 *
	 * @access public
	 * @param string $input_line - email identificator.
	 * @return void
	 */
	public function __construct( $input_line ) {
		$this->input_line = $input_line;
		$this->divide_into_parts();

		// Validate input for board.
		$this->board = $this->ms_validate_input( 10, $this->board );

		// Validate input for each of the hands.
		$hand_count = count( $this->hands );

		if ( ! empty( $hand_count ) ) {
			foreach ( $this->hands as $piece_location => $piece ) {
				unset( $this->hands[ $piece_location ] );
				$this->hands[ $piece ] = $this->ms_validate_input( 4, $piece, true );
			}
		} else {
			$this->report_errors( 'No Hands defined.' );
		}
	}

	/**
	 * Desctructor.
	 *
	 * @access public
	 * @return void
	 */
	public function __destruct() {}

	/**
	 * Divide line into board and hands.
	 *
	 * @access private
	 * @return void
	 */
	private function divide_into_parts() {
		$pieces = explode( ' ', $this->input_line );

		$this->board = $pieces[0];
		unset( $pieces[0] );
		$this->hands = $pieces;
	}

	/**
	 * Validate data.
	 *
	 * @access private
	 * @param int    $official_length - how many characters should be in the input string.
	 * @param string $piece - cards in question.
	 * @param bool   $is_hand - check if validating hand, add cards from board to hand if so.
	 * @return array
	 */
	private function ms_validate_input( $official_length, $piece, $is_hand = false ) {
		$ranks           = array( 'A', 'K', 'Q', 'J', 'T', '9', '8', '7', '6', '5', '4', '3', '2' );
		$suits           = array( 'h', 'd', 'c', 's' );
		$cards_to_return = array();

		$piece_length = strlen( $piece );

		if ( $piece_length !== $official_length ) {
			$error_element = 'Hand';

			if ( ! $is_hand ) {
				$error_element = 'Board';
			}
			$error_text = 'Character count wrong for ' . $error_element . '. Should be ' . $official_length . '.';
			$this->report_errors( $error_text );
		} else {
			if ( $is_hand ) {
				$cards_to_return = $this->board;
			}
			for ( $i = 0; $i < $piece_length; $i++ ) {
				if ( 0 === $i % 2 ) {
					$piece[ $i ] = strtoupper( $piece[ $i ] ); // Set all rank characters to uppercase.
					if ( ! in_array( $piece[ $i ], $ranks, true ) ) {
						$this->report_errors( 'Rank not recognized.' );
					}
				} else {
					$piece[ $i ] = strtolower( $piece[ $i ] ); // Set all suit characters to lowercase.
					if ( ! in_array( $piece[ $i ], $suits, true ) ) {
						$this->report_errors( 'Suit not recognized.' );
					} else {
						// Check for duplicate cards.
						if ( empty( $this->total_cards[ $piece[ $i ] ][ $piece[ $i - 1 ] ] ) ) {
							$this->total_cards[ $piece[ $i ] ][ $piece[ $i - 1 ] ]++;
						} else {
							$this->report_errors( 'Card already exists.' );
						}
						$cards_to_return[ $piece[ $i ] ][ $piece[ $i - 1 ] ]++;
					}
				}
			}

			return $cards_to_return;
		}
	}

	/**
	 * Process errors.
	 *
	 * @access private
	 * @param string $error_text - error text to output.
	 * @return void
	 */
	private function report_errors( $error_text ) {
		// Show only one error line as per requirements. Will change back later: to make things easier to fix.
		if ( $this->validation_ok ) {
			echo $error_text . PHP_EOL;
		}

		$this->validation_ok = false;
	}

	/**
	 * Check if any of the hands contain Flush. If they do, what kind.
	 *
	 * @access public
	 * @return void
	 */
	public function check_flush() {
		foreach ( $this->hands as $hand_key => $hand_values ) {
			$amount_of_suits = count( $hand_values );

			if ( 3 >= $amount_of_suits ) {
				// It's not possible to have more than three suits and have a Flush while playing Texas Hold'em.
				foreach ( $hand_values as $suit => $rank ) {
					$number_of_cards = count( $rank );

					if ( 5 <= $number_of_cards ) {

						// Check for Royal Flush.
						if ( isset( $rank['A'], $rank['K'], $rank['Q'], $rank['J'], $rank['T'] ) ) {
							$this->remove_used_cards(
								$hand_key,
								array(
									array(
										'suit' => $suit,
										'rank' => 'A',
									),
									array(
										'suit' => $suit,
										'rank' => 'K',
									),
									array(
										'suit' => $suit,
										'rank' => 'Q',
									),
									array(
										'suit' => $suit,
										'rank' => 'J',
									),
									array(
										'suit' => $suit,
										'rank' => 'T',
									),
								)
							);
							$this->remove_suits( $hand_key );

							$this->hand_order[9][] = $hand_key;
						} else {
							$result = $this->get_straight( $rank, $suit );

							$this->remove_used_cards( $hand_key, $result['cards_used'] );
							$this->remove_suits( $hand_key );

							if ( $result['straight_flush'] ) {
								$this->hand_order[8][ $result['highest_value'] ][] = $hand_key;
							} else {
								// Mark as flush - it can't be anything better: next thing it can be is three of a kind.
								$this->hand_order[5][ $result['highest_value'] ][] = $hand_key;
							}
						}

						unset( $this->hands[ $hand_key ] );
					}
				}
			}
		}
	}

	/**
	 * Remove those cards that are used in combination.
	 *
	 * @access private
	 * @param string $hand_key - identificator for hand.
	 * @param array  $values_to_remove - cards that should be removed.
	 * @return void
	 */
	private function remove_used_cards( $hand_key, $values_to_remove ) {
		foreach ( $values_to_remove as $values ) {
			unset( $this->hands[ $hand_key ][ $values['suit'] ][ $values['rank'] ] );
		}

		$this->hands[ $hand_key ] = array_filter( $this->hands[ $hand_key ] ); // Cleanup: removes any empty arrays.
	}

	/**
	 * Remove suits from object. After Flushes are filtered out they are no longer helpful.
	 *
	 * @access private
	 * @param string $hand_key - identificator for hand.
	 * @return void
	 */
	private function remove_suits( $hand_key ) {
		foreach ( $this->hands[ $hand_key ] as $suit => $ranks ) {

			if ( ! in_array( $suit, array( 'h', 'd', 'c', 's' ), true ) ) {
				continue;
			}

			foreach ( $ranks as $rank_key => $rank_value ) {
				$this->hands[ $hand_key ][ $rank_key ]++;
			}

			unset( $this->hands[ $hand_key ][ $suit ] );
		}
	}

	/**
	 * Get highest card value that's available.
	 *
	 * @access private
	 * @param array $cards - an array of cards to check.
	 * @return array
	 */
	private function get_highest_value( $cards ) {

		foreach ( $this->rank_values as $rank_key => $rank_value ) { // Go from the highest ranked to lowest.
			if ( ! empty( $cards[ $rank_key ] ) ) {
				return array(
					'value' => $rank_value,
					'key'   => $rank_key,
				);
			}
		}

		return array();
	}

	/**
	 * Get highest kicker card value that's available and remove it from the list of available values. Depends on remove_suits function.
	 *
	 * @access private
	 * @param string $hand_key - identificator for hand.
	 * @return int
	 */
	private function get_highest_outside_value( $hand_key ) {
		foreach ( $this->rank_values as $rank_key => $rank_value ) { // Go from the highest ranked to lowest.
			if ( ! empty( $this->hands[ $hand_key ][ $rank_key ] ) ) {

				$this->hands[ $hand_key ][ $rank_key ]--;
				if ( empty( $this->hands[ $hand_key ][ $rank_key ] ) ) {
					unset( $this->hands[ $hand_key ][ $rank_key ] );
				}

				return $rank_value;
			}
		}

		return 0;
	}

	/**
	 * Check if straight, return as array.
	 *
	 * @access private
	 * @param array  $values_to_check - values to compare for strightness.
	 * @param string $suit - give suit data if needed.
	 * @return array
	 */
	private function get_straight( $values_to_check, $suit = '' ) {
		$result              = array();
		$straight_cards_used = array();
		$flush_cards_used    = array();
		$straight_counter    = 0;
		$flush_counter       = 0;
		$last_key            = '';

		foreach ( $values_to_check as $key => $value ) {
			$ranked_list[ $this->rank_values[ $key ] ] = $key;
		}

		// Check for edge case where an A should be used as lowest card, be sure that there isn't a card with 6 as well.
		if ( isset( $ranked_list[5], $ranked_list[4], $ranked_list[3], $ranked_list[2], $ranked_list[14] ) && ! isset( $ranked_list[6] ) ) {
			unset( $ranked_list[14] );
			$ranked_list[1] = 1;
		}

		krsort( $ranked_list );

		$ranked_list_length = count( $ranked_list );

		// Array too short.
		if ( 5 > $ranked_list_length ) {
			return $result;
		}

		// Check if the elements follow each other.
		foreach ( $ranked_list as $key => $value ) {
			$straight_counter++;
			$flush_counter++;

			if ( 5 < $straight_counter ) {
				break;
			}

			if ( 5 >= $flush_counter && ! empty( $suit ) ) {
				$flush_cards_used[] = array(
					'rank' => $value,
					'suit' => $suit,
					'key'  => $key,
				);
			}

			if ( ! empty( $last_key ) ) {
				if ( 1 !== $last_key - $key ) {
					$straight_counter    = 1;
					$straight_cards_used = array(
						array(
							'rank' => $value,
							'suit' => $suit,
							'key'  => $key,
						),
					);
				} else {
					$straight_cards_used[] = array(
						'rank' => $value,
						'suit' => $suit,
						'key'  => $key,
					);
				}
			} else {
				$straight_cards_used = array(
					array(
						'rank' => $value,
						'suit' => $suit,
						'key'  => $key,
					),
				);
			}

			$last_key = $key;
		}

		if ( 5 <= $straight_counter ) {
			$result['highest_value'] = $straight_cards_used[0]['key'];
			$result['cards_used']    = $straight_cards_used;

			if ( ! empty( $suit ) ) {
				$result['straight_flush'] = true;
			}
		} elseif ( ! empty( $suit ) ) {
			$result['highest_value'] = $flush_cards_used[0]['key'];
			$result['cards_used']    = $flush_cards_used;
		}

		return $result;
	}

	/**
	 * Check if 4 of a kind.
	 *
	 * @access public
	 * @return void
	 */
	public function check_four_of_a_kind() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result = array_keys( $this->hands[ $hand_key ], 4, true );

			if ( ! empty( $result ) ) {
				unset( $this->hands[ $hand_key ][ $result[0] ] );
				$highest_kicker = $this->get_highest_outside_value( $hand_key );
				$this->hand_order[7][ $this->rank_values[ $result[0] ] ][ $highest_kicker ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Check if Full House.
	 * For Future: might be a good idea to set 3 of a kind and 2 of a kind as well - do have the data in $result array.
	 *
	 * @access public
	 * @return void
	 */
	public function check_full_house() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result[3] = array_keys( $this->hands[ $hand_key ], 3, true ); // Only one option available for Full House.
			$result[2] = array_keys( $this->hands[ $hand_key ], 2, true ); // Up to two options to choose from possible.

			if ( ! empty( $result[3] ) && ! empty( $result[2] ) ) {
				$result[2]   = array_combine( $result[2], $result[2] );
				$best_of_two = $this->get_highest_value( $result[2] );

				unset( $this->hands[ $hand_key ][ $result[3][0] ], $this->hands[ $hand_key ][ $best_of_two['key'] ] );
				$this->hand_order[6][ $this->rank_values[ $result[3][0] ] ][ $best_of_two['value'] ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Check if Straight.
	 *
	 * @access public
	 * @return void
	 */
	public function check_straight() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result = $this->get_straight( $hand_values );

			if ( ! empty( $result ) ) {
				foreach ( $result['cards_used'] as $used_card ) {
					$this->hands[ $hand_key ][ $used_card['rank'] ]--;
				}

				$this->hand_order[4][ $result['highest_value'] ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Check if 3 of a kind.
	 *
	 * @access public
	 * @return void
	 */
	public function check_three_of_a_kind() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result = array_keys( $this->hands[ $hand_key ], 3, true );

			if ( ! empty( $result ) ) {
				$result      = array_combine( $result, $result );
				$best_of_two = $this->get_highest_value( $result );

				unset( $this->hands[ $hand_key ][ $best_of_two['key'] ] );
				$highest_kicker        = $this->get_highest_outside_value( $hand_key );
				$second_highest_kicker = $this->get_highest_outside_value( $hand_key );
				$this->hand_order[3][ $best_of_two['value'] ][ $highest_kicker ][ $second_highest_kicker ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Check if 2 pairs.
	 *
	 * @access public
	 * @return void
	 */
	public function check_two_pairs() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result = array_keys( $this->hands[ $hand_key ], 2, true );

			$number_of_cards = count( $result );
			if ( 2 <= $number_of_cards ) {
				$result        = array_combine( $result, $result );
				$best_of_three = $this->get_highest_value( $result );

				unset( $result[ $best_of_three['key'] ] );
				$best_of_two = $this->get_highest_value( $result );

				unset( $this->hands[ $hand_key ][ $best_of_three['key'] ], $this->hands[ $hand_key ][ $best_of_two['key'] ] );
				$highest_kicker = $this->get_highest_outside_value( $hand_key );
				$this->hand_order[2][ $best_of_three['value'] ][ $best_of_two['value'] ][ $highest_kicker ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Check if 2 pairs.
	 *
	 * @access public
	 * @return void
	 */
	public function two_of_a_kind() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$result = array_keys( $this->hands[ $hand_key ], 2, true );

			if ( ! empty( $result ) ) {
				unset( $this->hands[ $hand_key ][ $result[0] ] );
				$highest_kicker        = $this->get_highest_outside_value( $hand_key );
				$second_highest_kicker = $this->get_highest_outside_value( $hand_key );
				$third_highest_kicker  = $this->get_highest_outside_value( $hand_key );
				$this->hand_order[1][ $this->rank_values[ $result[0] ] ][ $highest_kicker ][ $second_highest_kicker ][ $third_highest_kicker ][] = $hand_key;
				unset( $this->hands[ $hand_key ] );
			}
		}
	}

	/**
	 * Find highest card.
	 *
	 * @access public
	 * @return void
	 */
	public function highcard() {

		foreach ( $this->hands as $hand_key => $hand_values ) {

			$this->remove_suits( $hand_key );
			$highcard = $this->get_highest_value( $hand_values );
			unset( $this->hands[ $hand_key ][ $highcard['key'] ] );
			$highest_kicker        = $this->get_highest_outside_value( $hand_key );
			$second_highest_kicker = $this->get_highest_outside_value( $hand_key );
			$third_highest_kicker  = $this->get_highest_outside_value( $hand_key );
			$fourth_highest_kicker = $this->get_highest_outside_value( $hand_key );
			$this->hand_order[0][ $highcard['value'] ][ $highest_kicker ][ $second_highest_kicker ][ $third_highest_kicker ][ $fourth_highest_kicker ][] = $hand_key;
			unset( $this->hands[ $hand_key ] );
		}
	}

	/**
	 * Generate results.
	 *
	 * @access private
	 * @param array $data_to_transform - data used to generate results.
	 * @return string
	 */
	private function generate_results( $data_to_transform ) {
		$result = '';

		ksort( $data_to_transform );
		foreach ( $data_to_transform as $values ) {
			if ( empty( $values[0] ) ) {
				$result .= $this->generate_results( $values );
			} else {
				$value_count = count( $values );
				if ( 1 < $value_count ) {
					$count = 0;
					sort( $values );
					foreach ( $values as $value ) {
						if ( $count ) {
							$result .= '=';
						}
						$result .= $value;
						$count++;
					}
					$result .= ' ';
				} else {
					$result .= $values[0] . ' ';
				}
			}
		}

		return $result;
	}

	/**
	 * Print results.
	 *
	 * @access public
	 * @return void
	 */
	public function echo_results() {
		$results = $this->generate_results( $this->hand_order );

		echo trim( $results ) . PHP_EOL;
	}
}
