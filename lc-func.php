<?php
/**
 * Functions for lazycaptcha plugin
 *
 * Copyright 2020-2024 - Hans Matzen
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @package lazycaptcha
 */

/**
 * Plugin activation function.
 */
function wp_lazy_captcha_activate() {
	global $wpdb;

	// check for db-table and create if necessary.
	$lctab = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}lazy_captcha" ) );

	if ( $lctab != $wpdb->prefix . 'lazy_captcha' ) {
		// create challenge table.
		$results = $wpdb->query(
			$wpdb->prepare(
				"create table {$wpdb->prefix}lazy_captcha
				(
					nid varchar(32) NOT NULL,
					task varchar(12) NOT NULL,
					result varchar(3) NOT NULL,
					chtime timestamp NOT NULL,
					primary key(nid)
				)"
			)
		);
	}
}

/**
 * Plugin deactivation function.
 */
function wp_lazy_captcha_deactivate() {
	global $wpdb;

	// check for db-table and delete if applicable.
	$lctab = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', "{$wpdb->prefix}lazy_captcha" ) );
	if ( $lctab == $wpdb->prefix . 'lazy_captcha' ) {
		// drop table.
		$sql     = 'drop table ' . $wpdb->prefix . 'lazy_captcha;';
		$results = $wpdb->query( "drop table {$wpdb->prefix}lazy_captcha;" );
	}

	// remove cron job for db-cleanup.
	wp_clear_scheduled_hook( 'wp_lazy_captcha_dbclean_event' );
}

/**
 * Plugin init function.
 */
function wp_lazy_captcha_init() {
	// get translation.
	load_plugin_textdomain( 'wp-lazycaptcha', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

	wp_register_style( 'wplc', plugins_url( 'wplc.css', __FILE__ ), false, '0.6' );
	wp_enqueue_style( 'wplc' );

	// add wp-cron event to regulary remove old entries in the db-table.
	if ( ! wp_next_scheduled( 'wp_lazy_captcha_dbclean_event' ) ) {
		wp_schedule_event( time(), 'twicedaily', 'wp_lazy_captcha_dbclean_event' );
	}
}

/**
 * Clean up database and remove all records older than 12 hours.
 */
function wp_lazy_captcha_dbclean() {
	global $wpdb;
	$results = $wpdb->query(
		$wpdb->prepare(
			"delete from {$wpdb->prefix}lazy_captcha where chtime < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 12 HOUR);"
		)
	);
}
add_action( 'wp_lazy_captcha_dbclean_event', 'wp_lazy_captcha_dbclean' );

/**
 * Show captcha task between textarea and submit button.
 *
 * @param array $defaults default comment settings.
 */
function wp_lazy_captcha_comment_meta( $defaults ) {
	// $defaults['comment_notes_after'] .= wp_lazy_captcha_show();.
	$defaults['fields']['wp_lazy_captcha'] = wp_lazy_captcha_show();

	return $defaults;
}

/**
 * Store the challenge into the database.
 *
 * @param int    $nid id of the challenge.
 * @param string $task the challenge.
 * @param string $result the result of the challenge.
 */
function wp_lazy_captcha_store( $nid, $task, $result ) {
	global $wpdb;

	$wpdb->insert(
		$wpdb->prefix . 'lazy_captcha',
		array(
			'nid'    => $nid,
			'task'   => $task,
			'result' => $result,
			'chtime' => current_time( 'mysql' ),
		)
	);
}

/**
 * Create html to show captcha and return it.
 */
function wp_lazy_captcha_show() {
	list($task, $result) = wp_lazy_captcha_generate_task();
	$nid                 = 'wplc_' . uniqid();

	// save task, result and id to database.
	wp_lazy_captcha_store( $nid, $task, $result );

	// create a 100*30 image.
	$im = imagecreate( 100, 30 );

	// Weißer Hintergrund und blauer Text.
	$bg        = imagecolorallocate( $im, 255, 255, 255 );
	$textcolor = imagecolorallocate( $im, 44, 44, 44 );

	// add text to image.
	$font = plugin_dir_path( __FILE__ ) . 'junkos/junkos_typewriter.ttf';

	imagettftext( $im, 14, 0, 10, 20, $textcolor, $font, $task );

	// capture image file as string.
	ob_start();
	imagepng( $im );
	$image_data = ob_get_contents();
	ob_end_clean();

	// Read image path, convert to base64 encoding.
	$imgdata = base64_encode( $image_data );

	// Format the image SRC:  data:{mime};base64,{data};.
	$src = 'data: image/png;base64,' . $imgdata;

	// Echo out a sample image.
	$html  = '<p class="comment-form-author"><label for="' . $nid . '">' . __( 'Enter Result', 'wp-lazycaptcha' ) . '<span class="required">*</span></label><br/>';
	$html .= '<img src="' . $src . '" width="200" height="45" class="imgwplc">';
	$html .= '<input id="authorwplc" name="' . $nid . '" type="text" value="" maxlength="2" size="5">';
	$html .= '</p>';

	imagedestroy( $im );

	return $html;
}

/**
 * Generate random arithmetic task and return it together with the result as a tuple.
 */
function wp_lazy_captcha_generate_task() {
	$op0 = wp_rand( 0, 1 );
	$op  = ( 1 === $op0 ? '+' : '-' );
	$op1 = wp_rand( 0, 20 );
	$op2 = wp_rand( 1, 10 );

	// avoid negative and zero results results.
	if ( '-' === $op && $op2 >= $op1 ) {
		$t1  = $op2;
		$op2 = $op1;
		$op1 = $t1;
	}

	if ( '+' === $op ) {
		$result = $op1 + $op2;
	} else {
		$result = $op1 - $op2;
	}

	$task = $op1 . ' ' . $op . ' ' . $op2 . ' =';

	return array( $task, $result );
}


/**
 * Check if result is correct and not empty.
 *
 * @param string $comment the comment object.
 */
function wp_lazy_captcha_check( $comment ) {
	// do nothing if user is logged in or we got a ping or trackback.
	if ( is_user_logged_in() ) {
		return $comment;
	}
	if ( 'comment' != $comment['comment_type'] ) {
		return $comment;
	}

	// pre filter $_POST to only get keys starting with wplc_.
	// no nonce verification needed since we are part of the default comment process in WordPress.
	// @codingStandardsIgnoreStart
	$hit = array_filter(
		$_POST,
		function ( $key ) {
			return substr( $key, 0, 5 ) === 'wplc_';
		},
		ARRAY_FILTER_USE_KEY
	);
	// @codingStandardsIgnoreEnd

	// we only take the first one (there should only be one).
	$key   = array_key_first( $hit );
	$value = intval( $hit[ $key ] );

	// did we get an incorrect result.
	if ( empty( $hit ) || ! wp_lazy_captcha_verify( $key, $value ) ) {
		wp_die(
			wp_kses(
				apply_filters(
					'pre_comment_content',
					'<strong>' . __( 'Error', 'wp-lazycaptcha' ) . '</strong>: ' . __( 'Please fill in the correct answer and try again.', 'wp-lazycaptcha' )
				),
				array( 'strong' => array() )
			),
			'Error',
			array( 'back_link' => true )
		);
	}

	return $comment;
}


/**
 * Verify if an answer is correct.
 *
 * @param string $nid the id of the task.
 * @param string $result the entered answer to verify.
 */
function wp_lazy_captcha_verify( $nid, $result ) {
	global $wpdb;

	// check for empty parameters.
	if ( '' == trim( $nid ) || '' == trim( $result ) ) {
		return false;
	}

	// lets get the result from the database.
	$res1  = $wpdb->get_results(
		$wpdb->prepare(
			"select result from {$wpdb->prefix}lazy_captcha where nid=%s and chtime > (CURRENT_TIMESTAMP() - 3600*12);",
			$nid
		)
	);

	$dbresult = '';
	if ( $res1 ) {
		$dbresult = $res1[0]->result;
	}

	// did we get an incorrect result.
	if ( (int) $dbresult !== $result ) {
		return false;
	}

	// delete captcha record from database if everything was okay.
	$res2  = $wpdb->query(
		$wpdb->prepare(
			"delete from {$wpdb->prefix}lazy_captcha where nid=%s;",
			$nid
		)
	);

	return true;
}
