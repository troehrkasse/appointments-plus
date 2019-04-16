<?php

/*
Plugin Name: Awesome Transformations
Plugin URI: https://raddcreative.com
Description:  Custom WordPress Plugin Developed for Self Transformations
Version: 1.0
Author: Tyson Roehrkasse
Author URI: https://raddcreative.com
*/
if (!defined('ABSPATH')) exit;


if (!defined('AWESOME_TRANSFORMATIONS_DIR')) define('AWESOME_TRANSFORMATIONS_DIR', trailingslashit(plugin_dir_path(__FILE__)));
if (!defined('AWESOME_TRANSFORMATIONS_URI')) define('AWESOME_TRANSFORMATIONS_URI', trailingslashit(plugin_dir_url(__FILE__)));
if (!defined('AWESOME_TRANSFORMATIONS_MODULES_URL')) define('AWESOME_TRANSFORMATIONS_MODULES_URL', trailingslashit(AWESOME_TRANSFORMATIONS_URI . 'framework/modules'));
if (!defined('AWESOME_TRANSFORMATIONS_MODULES_BASE')) define('AWESOME_TRANSFORMATIONS_MODULES_BASE', trailingslashit(AWESOME_TRANSFORMATIONS_DIR . 'framework/modules'));
if (!defined('AWESOME_TRANSFORMATIONS_VER')) define('AWESOME_TRANSFORMATIONS_VER', '0.0.1');
if (!defined('AWESOME_TRANSFORMATIONS_SLUG')) define('AWESOME_TRANSFORMATIONS_SLUG', "awesome_transformations");


global $AWESOME_FRAMEWORK;
global $AWESOME_FRAMEWORK_PLUGIN_FILE_NAME;
$AWESOME_FRAMEWORK_PLUGIN_FILE_NAME = __FILE__;

/**
 * Awesome_Transformations
 * Primary plugin driver
 *
 * @category
 * @package
 * @author
 * @copyright
 * @license
 * @version
 * @link
 * @see
 * @since
 */
class Awesome_Transformations
{
    /**
     *
     */
    const TRANSIENT_PREFIX = "awesome_transformations_transient_";

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

    /**
     *
     */
    const SLUG = "Awesome_Transformations";

    /**
     * Instance of class
     *
     * Limit intance of class to one
     */
    protected static $instance;

    protected static $Mobile_Detect;

    private static $OPTIONS;

    private $timber;

    /**
     * Data about site's current visitor
     */
    public $visitor = array();

    /**
     * get_instance
     * Insert description here
     *
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
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * return transient key prefixed with plugin's prefix
     */
    public static function get_transient_key($append = "")
    {
        return self::TRANSIENT_PREFIX . $append;
    }

    /**
     * __construct
     * Class Contructor
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    protected function __construct()
    {
        $this->load_vendors();

        self::$Mobile_Detect = new Mobile_Detect;

        $this->load_includes();

        $this->load_requires();

        self::get_options();

        add_action('init', function () {
            $this->load_classes();
            $this->init();
        }, 20);

        //Load custom and potentially 3rd parties modules
        $this->load_modules();

    }


    /**
     * get_options
     * Bootstrap Awesome Framework options framework
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public static function get_options()
    {
        if (self::$OPTIONS != null) return self::$OPTIONS;

        self::$OPTIONS = get_option('awesome_transformations_settings', []);

        if (isset(self::$OPTIONS['modules']))
            self::$OPTIONS['modules'] = explode(',', self::$OPTIONS['modules']);

        global $AWESOME_FRAMEWORK;

        if (isset(self::$OPTIONS['modules']))
            $AWESOME_FRAMEWORK['ENABLED_MODULES'] = self::$OPTIONS['modules'];

        return self::$OPTIONS;
    }

    /**
     * _body_class
     * Added additional classes to the body element
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
    public function _body_class($classes)
    {
        $classes[] = 'awesome_transformations';

        if (Awesome_Transformations::$Mobile_Detect->isMobile())
            $classes[] = 'mobile';

        if (Awesome_Transformations::$Mobile_Detect->isTablet())
            $classes[] = 'tablet';

        if (!Awesome_Transformations::$Mobile_Detect->isMobile() && !Awesome_Transformations::$Mobile_Detect->isTablet())
            $classes[] = 'desktop';

        return $classes;
    }

    /**
     * add_to_twig_filters
     * Add custom filters to twig
     *
     * @param $twig
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    public function add_to_twig_filters($twig)
    {
        /** AR_STRING_REPLACEs */
        $twig->addFilter(new Twig_SimpleFilter('AWESOME_TRANSFORMATIONS_FILTER_STR_REPLACE', function ($str) {
            $str = $this->twig_filter_ar_this_year($str);
            $str = $this->twig_filter_ar_last_year($str);

            return $str;
        }));

        /** Format Phone filter */
        $twig->addFilter(new Twig_SimpleFilter('AWESOME_TRANSFORMATIONS_FORMATTED_PHONE', function ($str) {
            return csip_get_formatted_phone($str, 'US');
        }));

        $twig->addFilter(new Twig_SimpleFilter('AWESOME_TRANSFORMATIONS_UNESCAPE', function ($str) {
            return html_entity_decode($str);
        }));

        /** Pretty text */
        $twig->addFilter(new Twig_SimpleFilter('AWESOME_TRANSFORMATIONS_BEAUTIFY', function ($str) {
            return csip_beautify($str);
        }));

        /** Ugly db friendly text */
        $twig->addFilter(new Twig_SimpleFilter('AWESOME_TRANSFORMATIONS_UGLIFY', function ($str) {
            return csip_uglify($str, "_");
        }));

        $twig->addExtension(new Twig_Extensions_Extension_Array());

        $twig->addExtension(new Twig_Extension_StringLoader());

        $twig->addExtension(new Twig_Extension_Core());

        return $twig;
    }

    public function load_frontend_scripts()
    {
        wp_enqueue_script('awesome_transformations-core');
    }

    /**
     *
     *
     */
    public function load_global_styles_scripts()
    {
        wp_enqueue_style('select2', AWESOME_TRANSFORMATIONS_URI . 'assets/css/vendor/select2/select2.min.css', array(), AWESOME_TRANSFORMATIONS_VER);
        wp_register_style('slick-theme', AWESOME_TRANSFORMATIONS_URI . "assets/css/vendor/slick/slick-theme.css", array(), AWESOME_TRANSFORMATIONS_VER);
        wp_register_style('slick', AWESOME_TRANSFORMATIONS_URI . "assets/css/vendor/slick/slick.css", array('slick-theme'), AWESOME_TRANSFORMATIONS_VER);
        wp_register_style('simplemde', AWESOME_TRANSFORMATIONS_URI . "assets/css/vendor/simplemde/simplemde.min.css", array(), AWESOME_TRANSFORMATIONS_VER);
        wp_register_style('vue-slider-component', AWESOME_TRANSFORMATIONS_URI . "assets/css/vendor/awesoome-vue-es5-slider-component/vue-slider-component.css", array(), AWESOME_TRANSFORMATIONS_VER);

        if (defined('WP_DEBUG') && WP_DEBUG) {
            $js_ext = '.js';
        } else {
            $js_ext = '.min.js';
        }

        $OPTIONS = self::get_options();

        $maps_api_key = isset($OPTIONS['api']) && isset($OPTIONS['api']['google']) && isset($OPTIONS['api']['google']['maps_api_key']) ? $OPTIONS['api']['google']['maps_api_key'] : '';

        if (!is_admin())
            wp_register_script('lodash', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/lodash/lodash{$js_ext}", array(), AWESOME_TRANSFORMATIONS_VER, true);

        wp_register_script('google-places', '//maps.googleapis.com/maps/api/js?libraries=places&key=' . $maps_api_key, array(), AWESOME_TRANSFORMATIONS_VER);
        wp_register_script('vue', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/vue/vue{$js_ext}", array(), AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('vuex', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/vuex/vuex{$js_ext}", ['vue'], AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('vue-router', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/vue-router/vue-router{$js_ext}", array('vue'), AWESOME_TRANSFORMATIONS_VER, true);

        wp_register_script('awesome-vue-toolbox', AWESOME_TRANSFORMATIONS_URI . 'assets/js/awesome-vue-toolbox.js', array('vue', 'jquery'), AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('tether', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/tether/tether{$js_ext}", array('jquery'), AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('bootstrap-slider', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/bootstrap-slider/bootstrap-slider{$js_ext}", array('jquery', 'bootstrap'), AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('jquery-numeral', AWESOME_TRANSFORMATIONS_URI . 'assets/js/vendor/numeral/numeral.min.js', array('jquery'), AWESOME_TRANSFORMATIONS_VER, true);

        wp_register_script('bootstrap', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/bootstrap/bootstrap{$js_ext}", array('jquery', 'tether'), AWESOME_TRANSFORMATIONS_VER, true);

        wp_register_script('slick', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/slick/slick{$js_ext}", array('jquery'), AWESOME_TRANSFORMATIONS_VER);
        wp_register_script('vue-select', AWESOME_TRANSFORMATIONS_URI . "assets/js/vendor/vue-select/vue-select.js", array('jquery'), AWESOME_TRANSFORMATIONS_VER);

        wp_register_script('awesome_transformations-core', AWESOME_TRANSFORMATIONS_URI . 'assets/js/app.js', array('bootstrap', 'jquery', 'masonry', 'jquery-select2', 'slick', 'vue'), AWESOME_TRANSFORMATIONS_VER, true);
        wp_localize_script('awesome_transformations-core', 'AWESOME_TRANSFORMATIONS_ARGS', array(
                'API_BASE' => site_url('/wp-json/cap')
            )
        );

        wp_register_script('faqs-viewer', AWESOME_TRANSFORMATIONS_URI . 'assets/js/faqs-viewer.js', array('awesome_transformations-core'), AWESOME_TRANSFORMATIONS_VER, true);
        wp_register_script('services-viewer', AWESOME_TRANSFORMATIONS_URI . 'assets/js/services-viewer.js', array('awesome_transformations-core'), AWESOME_TRANSFORMATIONS_VER, true);
    }

    /**
     * Populate the visitor's location based on their ip address
     */
    private function get_visitor()
    {
        if (is_array($this->visitor) && !empty($this->visitor))
            return $this->visitor;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        $this->visitor['geo_location'] = TimberHelper::transient(Awesome_Transformations::get_transient_key("ip_geo_location_" . str_replace(".", "_", $ip)), function () {

            if (isset($_SERVER['GEOIP_LATITUDE'])):
                return [
                    'ip' => isset($_SERVER['GEOIP_ADDR']) ? $_SERVER['GEOIP_ADDR'] : $_SERVER['REMOTE_ADDR'],
                    'city' => $_SERVER['GEOIP_CITY'],
                    'region' => $_SERVER['GEOIP_REGION'],
                    'region_name' => $_SERVER['GEOIP_REGION_NAME'],
                    'postal_code' => $_SERVER['GEOIP_POSTAL_CODE'],
                    'country' => $_SERVER['GEOIP_COUNTRY_CODE'],
                    'country_name' => $_SERVER['GEOIP_COUNTRY_NAME'],
                    'loc' => $_SERVER['GEOIP_LATITUDE'] . ',' . $_SERVER['GEOIP_LONGITUDE'],
                    'longitude' => floatval($_SERVER['GEOIP_LONGITUDE']),
                    'latitude' => floatval($_SERVER['GEOIP_LATITUDE'])
                ];
            else:
                return [
                    'country' => 'US',
                    'country_name' => 'United States',
                    'loc' => '39.8282' . ',' . '-98.5795',
                    'longitude' => -98.5795,
                    'latitude' => 39.8282
                ];
            endif;
        }, Awesome_Transformations::DATA_CACHE_TIME_SHORT);

        if (isset($this->visitor['geo_location']['loc'])) {
            $loc = explode(',', $this->visitor['geo_location']['loc']);
            if (is_array($loc) && isset($loc[0]))
                $this->visitor['geo_location']['lat'] = $loc[0];

            if (is_array($loc) && isset($loc[1]))
                $this->visitor['geo_location']['lng'] = $loc[1];
        }

        return $this->visitor;
    }

    /**
     *
     *
     */
    public function add_to_context($context)
    {
        global $post;

        $data = TimberHelper::transient(Awesome_Transformations::get_transient_key("csip_global_context"), function () {
            $data = array();

            $data['site'] = new TimberSite();
            $data['cap']['AWESOME_TRANSFORMATIONS_URI'] = AWESOME_TRANSFORMATIONS_URI;
            $data['meta']['url'] = site_url('/');
            $data['meta']['type'] = 'website';
            $data['options'] = self::get_options();

            return $data;
        }, Awesome_Transformations::DATA_CACHE_TIME_LONG);

        $context = array_merge($context, $data);
        $context['http_host'] = 'https://' . TimberURLHelper::get_host();

        if (!is_admin()):
            $context['wp_title'] = TimberHelper::get_wp_title();
            $context['is_user_logged_in'] = is_user_logged_in();
            $context['user'] = get_object_vars(new TimberUser());
        endif;

        $context['_POST'] = $_POST;
        $context['_GET'] = $_GET;
        $context['_REQUEST'] = $_REQUEST;

        if (!is_admin() && is_archive())
            $context['posts'] = Timber::query_posts();

        if (isset($post->ID))
            $context['post'] = new AWESOME_TRANSFORMATIONS_Post($post->ID);

        $context['cap']['is_tablet'] = Awesome_Transformations::$Mobile_Detect->isTablet();
        $context['cap']['is_mobile'] = Awesome_Transformations::$Mobile_Detect->isMobile();
        $context['cap']['is_desktop'] = (!Awesome_Transformations::$Mobile_Detect->isTablet() && !Awesome_Transformations::$Mobile_Detect->isMobile());

        return array_merge((array)$data, $context);
    }

    /**
     * Category thumbnail fields.
     */
    public function add_term_fields()
    {
        $context = array();

        Timber::render('admin/partials/category-term-meta.twig', $context);
    }

    /**
     * Edit category thumbnail field.
     *
     * @param mixed $term Term (category) being edited
     */
    public function edit_term_fields($term)
    {
        $thumbnail_id = absint(get_woocommerce_term_meta($term->term_id, '_csip_term_meta_category_thumbnail_id', true));
        if ($thumbnail_id) {
            $image = wp_get_attachment_thumb_url($thumbnail_id);
        } else {
            $image = wc_placeholder_img_src();
        }

        $context = array(
            'image' => esc_url($image),
            'featured' => get_term_meta($term->term_id, '_csip_term_meta_category_featured', true),
            'excerpt' => get_term_meta($term->term_id, '_csip_term_meta_category_excerpt', true),
            'menu_label' => get_term_meta($term->term_id, '_csip_term_meta_menu_label', true),
            'menu_url' => get_term_meta($term->term_id, '_csip_term_meta_url_override', true),
            'non_catalog_link' => get_term_meta($term->term_id, '_csip_term_meta_non_catalog_link', true),
        );

        Timber::render('admin/partials/category-term-meta.twig', $context);
    }

    /**
     *
     */
    public function save_term_metas($term_id, $tt_id = '', $taxonomy = '')
    {
        foreach ($_POST as $f_key => $field):
            if (!preg_match("/_csip_term_meta_/", $f_key)) continue;

            $field = trim($field);

            update_term_meta($term_id, $f_key, csip_sanitize_input_KSES($field));
        endforeach;
    }

    public function bootstrap_api()
    {
        foreach (glob(AWESOME_TRANSFORMATIONS_DIR . "/framework/api/*.php") as $file):
            require_once($file);
        endforeach;
    }

    /**
     * bootstrap awesome framework modules
     */
    protected function load_modules()
    {
        $module_dirs = $dirs = array_filter(glob(trailingslashit(AWESOME_TRANSFORMATIONS_DIR) . 'framework/modules/*'), 'is_dir');

        $module_dirs = apply_filters('awesome_framework_module_dirs', $module_dirs);

        if (!is_array($module_dirs) && !empty($module_dirs))
            return;

        foreach ($module_dirs as $dir) {
            $dir = explode('/', trim($dir, '/'));
            $dir = $dir[count($dir) - 1];

            if (!file_exists(trailingslashit(AWESOME_TRANSFORMATIONS_DIR) . "framework/modules/$dir/$dir.php"))
                continue;

            include_once(trailingslashit(AWESOME_TRANSFORMATIONS_DIR) . "framework/modules/$dir/$dir.php");
        }
    }

    /**
     * load_includes
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    private function load_includes()
    {
        foreach (glob(AWESOME_TRANSFORMATIONS_DIR . "/framework/inc/*.php") as $file):
            include_once($file);
        endforeach;
    }

    /**
     * load_requires
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    private function load_requires()
    {
        foreach (glob(AWESOME_TRANSFORMATIONS_DIR . "framework/req/*.php") as $file):
            require_once($file);
        endforeach;
    }

    /**
     * load_classes
     * Insert description here
     *
     *
     * @return
     *
     * @access
     * @static
     * @see
     * @since
     */
    private function load_classes()
    {
        foreach (glob(AWESOME_TRANSFORMATIONS_DIR . "framework/classes/*.php") as $file):
            require_once($file);

            /** Load Widgets */
            if (strpos(strtolower($file), 'widget') !== false) {
                add_action('widgets_init', function () use ($file) {
                    register_widget(str_replace('.php', '', basename($file)));
                });
            }
        endforeach;
    }

    /**
     *
     */
    private function load_vendors()
    {
        require_once(AWESOME_TRANSFORMATIONS_DIR . "framework/vendor/autoload.php");
    }

    /**
     * Bootstrap awesome framework modules views override To allow theme to override module views
     */
    private function bootstrap_view_locations()
    {
        $views = [
            AWESOME_TRANSFORMATIONS_DIR . 'views'
        ];

        if (isset(self::$OPTIONS['modules']) && !empty(self::$OPTIONS['modules'])) {
            $views = array_merge($views, array_map(function ($m) {
                $m = str_replace('_', '-', $m);
                $dir = trailingslashit(get_stylesheet_directory()) . "views/awesome-framework/{$m}";

                if (is_dir($dir))
                    return $dir;

                return false;
            }, self::$OPTIONS['modules']));
        }

        if (isset(self::$OPTIONS['modules']) && !empty(self::$OPTIONS['modules'])) {
            $views = array_merge($views, array_map(function ($m) {
                $m = str_replace('_', '-', $m);
                return trailingslashit(AWESOME_TRANSFORMATIONS_DIR) . "framework/modules/$m/views";
            }, self::$OPTIONS['modules']));
        }

        //Filter to only valid directories
        $views = array_filter($views, function ($v) {
            if ($v === false)
                return false;

            return true;
        });

        // echo '<pre>'; var_dump($views); die();

        Timber::$locations = apply_filters('af_views', $views);
        Timber::$locations = apply_filters('awesome_framework_views', $views);
    }

    /**
     * Initialize plugin and all wp hooks and filters
     */
    private function init()
    {
        $this->bootstrap_view_locations();
        $loader = new TimberLoader();

        add_action('wp_enqueue_scripts', array(&$this, 'load_global_styles_scripts'));
        add_action('wp_enqueue_scripts', array(&$this, 'load_frontend_scripts'));
        add_action('admin_enqueue_scripts', array(&$this, 'load_global_styles_scripts'));
        add_action('rest_api_init', array(&$this, 'bootstrap_api'));
        remove_action('wp_head', 'rel_canonical');

        //Add default Visitor location for Awesome Directory module
        add_filter('awesome_framework_awesome_directory_jars', function ($jars) {
            $jars['VISITOR'] = $this->get_visitor();
            $jars['MAP_TILE'] = "//cartodb-basemaps-{s}.global.ssl.fastly.net/dark_all/{z}/{x}/{y}.png";

            return $jars;
        });

        add_filter('timber_context', array(&$this, 'add_to_context'), 10);

        /** Add Extra classes to body */
        add_filter('body_class', array(&$this, '_body_class'));

        unset($loader);

        //Admin only hooks and scripts
        if (!is_admin()) return;

        add_action('category_add_form_fields', array($this, 'add_term_fields'));
        add_action('category_edit_form_fields', array($this, 'edit_term_fields'), 10);

        add_action('created_term', array(&$this, 'save_term_metas'), 10, 3);
        add_action('edit_term', array(&$this, 'save_term_metas'), 10, 3);
    }
}

Awesome_Transformations::get_instance();
