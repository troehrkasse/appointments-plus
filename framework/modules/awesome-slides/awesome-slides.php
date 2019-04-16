<?php

define('AWESOME_SLIDES_URL', trailingslashit(str_replace(WP_CONTENT_DIR, WP_CONTENT_URL, dirname(__FILE__))));
define('AWESOME_SLIDES_DIR', trailingslashit(dirname(__FILE__)));

/**
 * Hook into Awesome Framework Customizer Menu
 */
include_once(AWESOME_SLIDES_DIR . 'settings.php');

/**
 * Module primary driver class
 *
 */
class Awesome_Slides
{
    /**
     *
     */
    const TRANSIENT_PREFIX = "awesome_slides_transient_";

    /**
     *
     */
    const DATA_CACHE_TIME_SHORT = 30 * MINUTE_IN_SECONDS;

    /**
     *
     */
    const DATA_CACHE_TIME_MEDIUM = 8 * HOUR_IN_SECONDS;

    /**
     *
     */
    const DATA_CACHE_TIME_LONG = DAY_IN_SECONDS;

    protected static $instance;

    protected static $options;

    /**
     * return transient key prefixed with plugin's prefix
     */
    public static function get_transient_key($append = "")
    {
        return self::TRANSIENT_PREFIX . $append;
    }

    public static function get_options()
    {
        if (self::$options)
            return self::$options;

        self::$options = get_option('awesome_slides', []);

        if (!isset(self::$options['enable_ads']))
            self::$options['enable_ads'] = false;

        return self::$options;
    }

    /**
     * get_instance
     * Return previously instantiated instace of framework or new up and return a new instance.
     * Mitigates have multiple instances in memory.
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public static function get_instance()
    {
        global $AWESOME_FRAMEWORK;

        if ($AWESOME_FRAMEWORK['ENABLED_MODULES'] == null)
            return 'Module Not Enabled';

        if (
            (!isset($AWESOME_FRAMEWORK['ENABLED_MODULES']) && is_array($AWESOME_FRAMEWORK['ENABLED_MODULES']))
            || !in_array('awesome_slides', (array)$AWESOME_FRAMEWORK['ENABLED_MODULES'])
        ) {
            return 'Module Not Enabled';
        }

        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * __construct
     * Class' constructor
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function __construct()
    {
        $this->load_dependencies();

        $this->init();
    }

    public function bootstrap_api()
    {
        foreach (glob(AWESOME_SLIDES_DIR . "framework/api/AR_API*.php") as $file):
            require_once($file);
        endforeach;
    }

    public function wp_enqueue_scripts()
    {
        // wp_enqueue_style('awesome-slides', trailingslashit(AWESOME_SLIDES_URL) . 'assets/css/awesome-slides.min.css', array(), '1');

        wp_enqueue_script('awesome-slides', trailingslashit(AWESOME_SLIDES_URL) . 'assets/js/main.js#defer', array('jquery'), '5', true);
    }

    /**
     * add_to_timber_context
     * append module specific data to global timber context collection
     *
     * @param $context
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function add_to_timber_context($context): array
    {
        $adding = array();
        $options = self::get_options();

        if (is_array($options) && !empty($options) && isset($context['options']))
            $context['options'] = array_merge((array)$context['options'], $options);

        if (!isset($context['options']))
            $context['options'] = $options;

        if (is_admin()) {
            return array_merge($context, $adding);
        }

        return array_merge($context, $adding);
    }

    /**
     * browser_body_class
     * Add module specific classes to htom dom body
     *
     * @param $classes
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function browser_body_class($classes): array
    {
        return $classes;
    }

    /**
     * Load Requires.
     * Loads all files that are required by the theme.
     *
     * @return none
     * @see __construct
     */
    private function load_dependencies()
    {
        /** Load includes */
        foreach (glob(AWESOME_SLIDES_DIR . "/framework/inc/*.php") as $file):
            require_once($file);
        endforeach;

        /** Load Classes */
        foreach (glob(AWESOME_SLIDES_DIR . "/framework/classes/*.php") as $file):
            require_once($file);
        endforeach;
    }

    protected function init()
    {
        add_filter('timber_context', array(&$this, 'add_to_timber_context'), 60);

        add_action('rest_api_init', array(&$this, 'bootstrap_api'), 60);
        add_action('wp_enqueue_scripts', array(&$this, 'wp_enqueue_scripts'), 60);
    }
}

Awesome_Slides::get_instance();