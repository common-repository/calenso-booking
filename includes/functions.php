<?php

//Initialize Funktion
function zpt_init_calenso_admin()
{
    add_menu_page('Calenso Booking', 'Calenso Booking', 'manage_options', 'calenso_wordpress_widget', 'zpt_calenso_widget_function', ZPT_CALENSO_DIR . 'assets/img/icon.png');
    add_submenu_page('calenso_wordpress_widget', 'Shortcodes', 'Shortcodes', 'manage_options', 'calenso_widget_shortcode', 'zpt_calenso_widget_shortcode_function');
}


function zpt_libraries_style()
{
    wp_enqueue_style('zpt-calenso-lib', ZPT_CALENSO_DIR . 'assets/css/zactonz-lib.css', array(), ZPT_CALENSO_VERSION, 'all');
    wp_enqueue_style('zpt-calenso-fa', ZPT_CALENSO_DIR . 'assets/css/font-awesome.min.css', array(), ZPT_CALENSO_VERSION, 'all');
    wp_enqueue_style('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/css/zpt-theme.css', array(), ZPT_CALENSO_VERSION, 'all');
    //wp_enqueue_script('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/js/scripts.js', array(), ZPT_CALENSO_VERSION, 'all');
    wp_enqueue_script('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/js/scripts.js', array('jquery'), '', true );

}

add_action('current_screen', 'detecting_current_screen');

function detecting_current_screen()
{
    $current_screen = get_current_screen();
    
    if($current_screen->base == 'toplevel_page_calenso_wordpress_widget'){
        add_action('admin_enqueue_scripts', 'zpt_libraries_style');
    }
    if($current_screen->base == 'dashboard'){
        add_action('admin_enqueue_scripts', 'zpt_libraries_style');
    }

}

function zpt_curl_connect_request($api_url, $access_token, $partnerUuid, $payload = null)
{

    if (!empty($access_token) && !empty($partnerUuid)) {
        $args = array(
            'headers' => array(
                'Accept' => "application/json",
                'Authorization' => "Bearer $access_token",
                'X-Calenso-Auth' => "true"
            )
        );
    } else if (!empty($partnerUuid)) {
        $args = array(
            'headers' => array(
                'Accept' => "application/json",
                'X-Calenso-Auth' => true
            )
        );
    } else if (!empty($access_token)) {
        $args = array(
            'headers' => array(
                'Accept' => "application/json",
                'Authorization' => "Bearer $access_token",
                'X-Calenso-Auth' => true
            )
        );
    } else {
        $args = array(
            'headers' => array(
                'Accept' => "application/json",
            )
        );
    }

    if (isset($payload) && $payload !== null) {
        $args['body'] = json_decode($payload, true);
        $result = wp_remote_retrieve_body(wp_remote_post($api_url, $args));
    } else {
        $result = wp_remote_retrieve_body(wp_remote_get($api_url, $args));
    }

    return json_decode($result);
}
//URL Variablen Setup funktion
function zpt_short_code_access($args = array())
{
    global $wpdb;
    if (isset($args['id']) && trim($args['id']) != "") {
        $args['id'] = esc_sql(sanitize_text_field($args['id']));

        $get_data = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE . " WHERE id = '" . $args['id'] . "'")[0];
        if ($get_data) {
            $sc = json_decode($get_data->function, true);
            $zpt_rtn = "";
            switch ($sc['iam']) {
                case 'iframe':
                    if (isset($sc['type']) && $sc['type'] == "appointment") {
                        if ($sc['store_id'] != '' && $sc['service'] != '') {
                        $zpt_rtn .= wp_enqueue_script('zpt-booking-iframe-resizer', ZPT_CALENSO_DIR . 'assets/js/4.3.2/iframeResizer.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= wp_enqueue_script('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/js/iframe-resize.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= '<iframe id="calenso-booking-widget" src="https://widget.calenso.com/?partner=' .
                            $sc['partner'] . '&type=' .
                            $sc['type'] . '&store_id=' .
                            $sc['store_id'] . '&worker_id=' . 
                            $sc['worker'] . '&service[]=' .
                            $sc['service'] . '&isFrame=true&lang=' .
                            $sc['lang'] . '" frameborder="0"style="height: 700px; width: 100%; max-width: 840px;" scrolling="yes"></iframe>';
                        } else {
                            $zpt_rtn .= wp_enqueue_script('zpt-booking-iframe-resizer', ZPT_CALENSO_DIR . 'assets/js/4.3.2/iframeResizer.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                            $zpt_rtn .= wp_enqueue_script('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/js/iframe-resize.js', array(), ZPT_CALENSO_VERSION, 'all');
                            $zpt_rtn .= '<iframe id="calenso-booking-widget" src="https://widget.calenso.com/?partner=' .
                            $sc['partner'] . '&type=' .
                            $sc['type'] . '&worker_id=' . 
                            $sc['worker'] . '&isFrame=true&lang=' .
                            $sc['lang'] . '" frameborder="0"style="height: 700px; width: 100%; max-width: 840px;" scrolling="yes"></iframe>';
                        }
                    } else {
                        $zpt_rtn .= wp_enqueue_script('zpt-booking-iframe-resizer', ZPT_CALENSO_DIR . 'assets/js/4.3.2/iframeResizer.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= wp_enqueue_script('zpt-calenso-zt', ZPT_CALENSO_DIR . 'assets/js/iframe-resize.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= '<iframe id="calenso-booking-widget" src="https://widget.calenso.com/?partner=' .
                            $sc['partner'] . '&type=' .
                            $sc['type'] . '&worker_id=' . 
                            $sc['worker'] . '&isFrame=true&lang=' .
                            $sc['lang'] . '&event-id=' .
                            $sc['event'] . '" frameborder="0"style="height: 700px; width: 100%;max-width: 840px;" scrolling="yes"></iframe>';
                    }

                    break;
                case 'webcomponent':
                    if (isset($sc['type']) && $sc['type'] == "appointment") {
                        if ($sc['store_id'] != '' && $sc['service'] != '') {
                        $zpt_rtn = wp_enqueue_script('zpt-html-imports-js', 'https://webcomponent.widget.calenso.com/html-imports.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= '<link rel="import" href="https://webcomponent.widget.calenso.com/booking.html">';
                        $zpt_rtn .= '<calenso-booking id="calenso-booking-widget" partner="' .
                            $sc['partner'] . '" type="' .
                            $sc['type'] . '" selected-store-id="' .
                            $sc['store_id'] . '" selected-appointment-service-ids="' .
                            $sc['service'] . '" selected-worker-id="' . 
                            $sc['worker'] . '" lang="' .
                            $sc['lang'] . '"></calenso-booking>';
                        } else {
                            $zpt_rtn = wp_enqueue_script('zpt-html-imports-js', 'https://webcomponent.widget.calenso.com/html-imports.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                            $zpt_rtn .= '<link rel="import" href="https://webcomponent.widget.calenso.com/booking.html">';
                            $zpt_rtn .= '<calenso-booking id="calenso-booking-widget" partner="' .
                            $sc['partner'] . '" type="' .
                            $sc['type'] . '" selected-worker-id="' . 
                            $sc['worker'] . '" lang="' .
                            $sc['lang'] . '"></calenso-booking>';
                        }
                    } else {
                        $zpt_rtn = wp_enqueue_script('zpt-html-imports-js', 'https://webcomponent.widget.calenso.com/html-imports.min.js', array(), ZPT_CALENSO_VERSION, 'all');
                        $zpt_rtn .= '<link rel="import" href="https://webcomponent.widget.calenso.com/booking.html">';
                        $zpt_rtn .= '<calenso-booking id="calenso-booking-widget" partner="' .
                            $sc['partner'] . '" type="' .
                            $sc['type'] . '" event-id="' .
                            $sc['event'] . '" selected-worker-id="' . 
                            $sc['worker'] . '" lang="' .
                            $sc['lang'] . '"></calenso-booking>';
                    }
                    break;
                default:
                    break;
            }

            return $zpt_rtn;
        } else {
            return 'Invalid Shortcode';
        }
    } else {
        return 'Please Enter Shortcode ID';
    }
}

add_shortcode('calenso_booking', 'zpt_short_code_access');

//Sidebar funktion
function zpt_calenso_sidebar_html()
{
    ?>
    <div class="zpt-calenso-sidebar">
        <div class="calenso-head">
            <img src="<?php echo ZPT_CALENSO_DIR ?>assets/img/support_ico.svg" alt="" class="zpt-image">
        </div>
        <div class="calenso-body">
            <div class="calenso-ptl">
                <img src="<?php echo ZPT_CALENSO_DIR ?>assets/img/play_ico.svg" alt="" class="zpt-image">
                <a target="_blank" href="https://support.calenso.com">
                    <h3><?php echo zpt_calenso__("Sidebar_Text_Description"); ?></h3></a>
            </div>
            <div class="calenso-separator"></div>
            <div class="calenso-lower">
                <h3><?php echo zpt_calenso__("Sidebar_Text_Setup_Question"); ?></h3><br>
                <a target="_blank" href="https://calenso.freshdesk.com/de/support/tickets/new">
                    <button type="button"
                            class="calenso-button"><?php echo zpt_calenso__("Sidebar_Button_Link_To_Setup_Help"); ?></button>
                </a>
            </div>
        </div>
    </div>

    <?php
}

function zpt_calenso__($string)
{
    global $ZPT_CALENSO_TRANS_TXT;
    if (isset($ZPT_CALENSO_TRANS_TXT) && !empty($ZPT_CALENSO_TRANS_TXT)) {
        return $ZPT_CALENSO_TRANS_TXT[$string];
    } else {
        $ln = strtolower(substr(get_bloginfo('language'), 0, 2));
        if (file_exists(ZPT_CALENSO_SLUG . "/multilingual/$ln.json")) {

            $ZPT_CALENSO_TRANS_TXT = json_decode(file_get_contents(ZPT_CALENSO_SLUG . "/multilingual/$ln.json"), true);

            return $ZPT_CALENSO_TRANS_TXT[$string];
        } else {
            if (file_exists(ZPT_CALENSO_SLUG . "/multilingual/en.json")) {

                $ZPT_CALENSO_TRANS_TXT = json_decode(file_get_contents(ZPT_CALENSO_SLUG . "/multilingual/en.json"), true);

                return $ZPT_CALENSO_TRANS_TXT[$string];

            } else {
                return $string;
            }
        }
    }

}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ZPT_Calenso_Booking_Table extends WP_List_Table
{

    function __construct()
    {
        global $status, $page;

        //Set parent defaults
        parent::__construct(array(
            'singular' => 'id',     //singular name of the listed records
            'plural' => 'ids',    //plural name of the listed records
            'ajax' => false        //does this table support ajax?
        ));

    }
    
    public function extra_tablenav( $which ) {
        if ( $which == 'top' ) {
            // Add "Add New" button
            echo '<div class="alignleft actions">';
            echo '<a href="'.add_query_arg( 'page', 'calenso_wordpress_widget', admin_url('admin.php') ).'" class="button button-primary">'.zpt_calenso__('Add_New_Button').'</a>';
            echo '</div>';
        }
    }

    function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'title':
            case 'shortcode':
                return $item[$column_name];
            case 'time_stamp':
                $timezone = get_option( 'timezone_string' );
                $date_format = get_option( 'date_format' );
                $time_format = get_option( 'time_format' );
                return date( $date_format . ' ' . $time_format , strtotime($item[$column_name]));
            case 'details':
                $json = json_decode($item['function'], true);
                $return = '<table class="wp-list-table widefat">
                <tbody>
                    <tr>
                        <th>'.zpt_calenso__("Page_3_Text_Integration_Type_Selector").'</th>
                        <td>'.ucwords($json['iam']).'</td>
                    </tr>
                    <tr>
                        <th>'.zpt_calenso__("Page_3_Text_Booking_Type_Selector").'</th>
                        <td>'.ucwords($json['type']).'</td>
                    </tr>
                </tbody>
                </table>';
                return $return;
            default:
                return print_r($item, true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_title($item)
    {
        $delete_nonce = wp_create_nonce('zpt_delete_booking');
        $edit_nonce = wp_create_nonce('zpt_edit_booking');
        //Build row actions
        $actions = array(
            'delete' => sprintf('<a href="?page=calenso_widget_shortcode&page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), $delete_nonce),
            'edit' => sprintf('<a href="?page=calenso_widget_shortcode&page=%s&action=%s&id=%s&_wpnonce=%s">Edit</a>', esc_attr($_REQUEST['page']), 'edit', absint($item['id']), $edit_nonce),
        );

        //Return the title contents
        return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
            /*$1%s*/ $item['title'],
            /*$2%s*/ $item['id'],
            /*$3%s*/ $this->row_actions($actions)
        );
    }

    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['id']                //The value of the checkbox should be the record's id
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', //Render a checkbox instead of text
            'title' => zpt_calenso__('Shortcode_Tab_Title'),
            'details' => zpt_calenso__('Shortcode_Tab_Details'),
            'shortcode' => zpt_calenso__('Shortcode_Tab_Shortcode'),
            'time_stamp' => zpt_calenso__('Shortcode_Tab_Date_&_Time')
        );
        return $columns;
    }

    function zpt_get_sortable_columns()
    {
        $sortable_columns = array(
            'id' => array('id', true),     //true means it's already sorted
            'title' => array('title', false),     //true means it's already sorted
            'shortcode' => array('shortcode', false),
            'time_stamp' => array('time_stamp', false)
        );
        return $sortable_columns;
    }

    function zpt_delete_booking($bookingId)
    {
        global $wpdb;
        if (isset($bookingId)) {
            if (is_array($bookingId)) {
                $ids = implode(",", $bookingId);
                $ids = esc_sql(sanitize_text_field($ids));
                $wpdb->query("DELETE FROM " . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE . " WHERE id IN($ids)");
            } else {
                $bookingId = esc_sql(sanitize_text_field($bookingId));
                $wpdb->query("DELETE FROM " . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE . " WHERE id = '$bookingId' LIMIT 1");
            }
        }
    }
    
    function zpt_edit_booking($bookingId)
    {
        global $wpdb;
        if (isset($bookingId)) {
            echo '<script type="text/javascript">window.location.href="admin.php?page=calenso_wordpress_widget&action=edit&booking_id='.esc_attr($bookingId).'";</script>';
        }
    }

    function get_bulk_actions()
    {
        $actions = array(
            'bulk-delete' => 'Delete'
        );
        return $actions;
    }

    function zpt_process_bulk_action()
    {

        //Detect when a edit action is being triggered...
        if ('edit' === $this->current_action()) {
            if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                $nonce = ($_REQUEST['_wpnonce']);
                if (!wp_verify_nonce($nonce, 'zpt_edit_booking')) {
                    die('Invalid security token. Try to reload the page!');
                } else {
                    self::zpt_edit_booking(sanitize_key($_REQUEST['id']));
                }
            }
        }
        
        //Detect when a delete action is being triggered...
        if ('delete' === $this->current_action()) {
            if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                $nonce = ($_REQUEST['_wpnonce']);
                if (!wp_verify_nonce($nonce, 'zpt_delete_booking')) {
                    die('Invalid security token. Try to reload the page!');
                } else {
                    self::zpt_delete_booking(sanitize_key($_REQUEST['id']));
                }
            }
        }

        // If the delete bulk action is triggered
        if ('bulk-delete' === $this->current_action()) {

            if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {

                $nonce = ($_REQUEST['zpt_bulk_wpnonce']);
                if (!wp_verify_nonce($nonce, 'zpt_bulk_delete_booking')) {
                    die('Invalid security token. Try to reload the page!');
                } else {
                    self::zpt_delete_booking(sanitize_key($_REQUEST['id']));
                }
            }
        }
    }

    function prepare_items()
    {
        global $wpdb; //This is used only if making any database queries
        $per_page = 5;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->zpt_get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->zpt_process_bulk_action();
        $get_item = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . ZPT_CALENSO_SHORTCODE_TABLE, ARRAY_A);

        $data = $get_item;

        function usort_reorder($a, $b)
        {
            $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_text_field($_REQUEST['orderby']) : 'title'; //If no sort, default to title
            $order = (!empty($_REQUEST['order'])) ? sanitize_text_field($_REQUEST['order']) : 'asc'; //If no order, default to asc
            $result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
            return ($order === 'asc') ? $result : -$result; //Send final sort direction to usort
        }

        usort($data, 'usort_reorder');

        $current_page = $this->get_pagenum();

        $total_items = count($data);


        $data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

        $this->items = $data;


        $this->set_pagination_args(array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page' => $per_page,                     //WE have to determine how many items to show on a page
            'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
        ));
    }
}
