<?php
/**
 * Plugin Name: LazyCaptcha
 * Description: LazyCaptcha is a small and lazy plugin to prevent bots from spamming your comments.
 * Version: 0.6
 * Author: Hans Matzen
 * Author URI: https://www.tuxlog.de
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: lang
 * Text Domain: wp-lazycaptcha
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

// include lazycaptcha functions.
require_once 'lc-func.php';

// activating deactivating the plugin.
register_activation_hook( __FILE__, 'wp_lazy_captcha_activate' );
register_deactivation_hook( __FILE__, 'wp_lazy_captcha_deactivate' );

// init plugin.
add_action( 'init', 'wp_lazy_captcha_init' );
// add captcha field to comment form.
add_filter( 'comment_form_defaults', 'wp_lazy_captcha_comment_meta' );
// add action if comment is submitted.
add_filter( 'preprocess_comment', 'wp_lazy_captcha_check', 0, 1 );
