<?php

/**
 * @wordpress-plugin
 * Plugin Name:       S Job Scraper
 * Plugin URI:        https://www.visualdesigner.io/
 * Description:       
 * Version:           1.00
 * Author:            Sam Zielke-Ryner
 * Author URI:        https://www.visualdesigner.io
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aw_fe
 * Domain Path:       /languages
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

if (!class_exists('S_Job_Scraper'))
  require_once('includes/s_job_scraper.php');

require_once('vendor/simple_html_dom/simple_html_dom.php');

function run_s_job_scraper() {
  register_activation_hook( __FILE__, 'S_Job_Scraper::on_activate' );
  register_deactivation_hook( __FILE__, 'S_Job_Scraper::on_deactivate' );
};
run_s_job_scraper();
