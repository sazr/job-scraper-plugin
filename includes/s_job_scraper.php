<?php
/*
  https://wpshout.com/wp_schedule_event-examples/
*/

if (defined('CLS_S_JOB_SCRAPER'))
  return;
define('CLS_S_JOB_SCRAPER', true);

class S_Job_Scraper {

  // Configs
  const ROOT_SLUG             = 'sjs-admin';
  const NOTIF_EMAILS          = ['foobar@gmail.com'];
  const SCHEDULER_INTERVAL    = 21600; // unit=seconds

  // Simply hired specific
  const SH_URLS = [
    'https://www.simplyhired.com.au/search?q=security+guard&l=sydney+nsw&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=sydney+nsw&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=sydney+nsw&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=melbourne+vic&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=melbourne+vic&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=melbourne+vic&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=brisbane+qld&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=brisbane+qld&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=brisbane+qld&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=perth+wa&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=perth+wa&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=perth+wa&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=adelaide+sa&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=adelaide+sa&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=adelaide+sa&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=darwin-nt&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=darwin-nt&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=darwin-nt&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=canberra+act&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=canberra+act&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=canberra+act&pn=3',

    'https://www.simplyhired.com.au/search?q=security+guard&l=hobart+tas&pn=1',
    'https://www.simplyhired.com.au/search?q=security+guard&l=hobart+tas&pn=2',
    'https://www.simplyhired.com.au/search?q=security+guard&l=hobart+tas&pn=3',
  ];


  // Indeed specific
  const INDEED_URLS = [
    'https://api.indeed.com/ads/apisearch'
  ];
  const INDEED_SEARCH_QUERIES = [
    [
      'q'         => 'security guard',
      'co'        => 'au',
      'sort'      => '',  // relevance, date.
      'st'        => '',
      'jt'        => 'fulltime',  // fulltime, parttime, contract, internship, or temporary
      'start'     => '',
      'limit'     => '',
      'fromage'   => '',
      'filter'    => '1',
      'latlong'   => '1',
      'chnl'      => '',
      'userip'    => '',
      'useragent' => '',
      'v'         => '2',
      'publisher' => '',
      'format'    => 'json',
      //'l'         => 'sydney',
    ],
  ];
  

  public static function initialise() {
    add_action('admin_menu', 'S_Job_Scraper::add_admin_pages', 1);
    add_action('init', 'S_Job_Scraper::setup', 20);
    add_action('wp_ajax_sjs_run_scraper', 'S_Job_Scraper::post_run_scraper');

    add_filter('cron_schedules', 'S_Job_Scraper::add_custom_cron_interval');
    add_action('sjs_cron_hook', 'S_Job_Scraper::cron_exec');
	}

  // Executes when WordPress is ready and has completed all its setup tasks
  public static function setup() {
    // Schedule task
    if (!wp_next_scheduled( 'sjs_cron_hook' )) {
        wp_schedule_event(time(), 's_job_scraper', 'sjs_cron_hook');
    }
  }

  public static function on_activate() {
    
  }

  public static function on_deactivate() {
    // Unschedule task
    $timestamp = wp_next_scheduled('sjs_cron_hook');
    wp_unschedule_event($timestamp, 'sjs_cron_hook');
    wp_clear_scheduled_hook('S_Job_Scraper::cron_exec');
  }

  public static function add_admin_pages() {
    add_menu_page( 
      'Job Scraper', 
      'Job Scraper', 
      'edit_pages',
      self::ROOT_SLUG, 
      'S_Job_Scraper::get_admin_settings_page'
    );
  }

  public static function get_admin_settings_page() {
    include_once('partials/settings.php');
  }

  public static function add_custom_cron_interval($schedules) { 
    // Note WP Cron is not guaranteed to run on time if visits to your website are low. See https://wpshout.com/wp_schedule_event-examples/ 

    $schedules['s_job_scraper'] = array(
      'interval' => self::SCHEDULER_INTERVAL, 
      'display'  => esc_html__( 'Weekly' ), 
    );
    return $schedules;
  }

  public static function cron_exec() {
    error_log("Scheduler Running");
    wp_mail(self::NOTIF_EMAILS, 'S-Job-Scaper Running', "Job Scraper is executing", ['Content-Type: text/html; charset=UTF-8']);

    foreach(self::SH_URLS as $url) {
      try {
        $html = file_get_html($url);

        foreach($html->find('#job-list article') as $element) {
          $job_data = [];
          $job_data['jobtitle']           = $element->find('.jobposting-title a', 0)->plaintext;
          $job_data['snippet']            = $element->find('.jobposting-snippet', 0)->plaintext;
          $job_data['company']            = $element->find('.jobposting-company', 0)->plaintext;
          $job_data['formattedLocation']  = $element->find('.jobposting-location .jobposting-location', 0)->plaintext;
          $job_data['url']                = "https://www.simplyhired.com.au" . $element->find('.jobposting-title a', 0)->href;

          self::add_job($job_data, []);
        }
      }
      catch (exception $e) {
        wp_mail(self::NOTIF_EMAILS, 'S-Job-Scaper: Failed to scrape Simply Hired.', "Failed to scrape $url. The html may have changed", ['Content-Type: text/html; charset=UTF-8']);
      }
    }
  }

  public static function add_job($data, $qry) {
    $job_data = [
      'post_title'     => $data['jobtitle'],
      'post_content'   => $data['snippet'],
      'post_type'      => 'job_listing',
      'comment_status' => 'closed',
      'post_name'      => $data['company'] . '-' . $data['formattedLocation'] . '-' . $data['jobtitle'],
      'post_status'    => 'publish'
    ];

    if (self::job_exists($job_data)) {
      // error_log("JOB EXISTS ALREADY");
      return;
    }

    $id = wp_insert_post( $job_data );

    $job_meta = [
      '_job_title'         => $data['jobtitle'],
      '_job_location'      => $data['formattedLocation'],
      '_job_description'   => $data['snippet'],
      '_application'       => $data['url'],
      '_company_name'      => $data['company'],
      '_job_expires'       => date('Y-m-d', strtotime('+1 months')), // Todays date plus 1 month
      // '_job_type'          => $data[''],
      // '_company_website'   => $data[''],
      // '_company_tagline'   => $data[''],
      // '_company_video'     => $data[''],
      // '_company_twitter'   => $data[''],
      // '_company_logo'      => $data[''],
    ];

    foreach ($job_meta as $key => $value) {
      update_post_meta($id, $key, $value);
    }
  }

  public static function job_exists($job_data) {
    $postId = post_exists($job_data['post_title'], '', '', 'job_listing');
    $postStatus = get_post_status($postId);
    return (!empty($postId) && !in_array($postStatus, ['trash',FALSE]));
  }

  public static function post_run_scraper() {
    if (!isset($_REQUEST['nonce']) || !wp_verify_nonce( $_REQUEST['nonce'], "sjs_run_scraper"))
      exit("No naughty business please");
    if (!is_user_logged_in())
      exit("User not logged in");

    self::cron_exec();

    $result = json_encode(['type' => 'success']);
    echo $result;
    die();
  }

  /* Indeed scraper
  public static function cron_exec() {
    foreach (self::INDEED_SEARCH_QUERIES as $qry) {
      $url = path_join(self::INDEED_URLS[0], '?' . http_build_query($qry));
      $res = file_get_contents($url);
      $json = json_decode($res, true);

      if (isset($json) && isset($json['results']) && count($json['results']) > 0 ) {
        foreach ($json['results'] as $job_data) {
          self::add_job($job_data, $qry);
        }
      }
    }
  }
  */
}

S_Job_Scraper::initialise();
