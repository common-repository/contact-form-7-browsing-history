<?php
/**
 * Plugin Name: Browsing History
 * Description: Add visitor page histories in Contact Form 7 mail body.
 * Author: biztechc
 * Author URI: https://www.appjetty.com/
 * Version: 4.0.0
 * WordPress Tested up to: 5.8
 */

/*if (!session_id()) {
    add_action('init', 'session_start');    // start session
}*/

/**
 * Added by Dhruvi-biztech on 07/11/2019.
 * To enable session_start beacuse above code for session_start not worked.
 */
add_action('init', 'bt_start_session', 1);
function bt_start_session() {
    if(!session_id()) {
        session_start();
}
}

add_action('wp_footer', 'bt_add_histories');    // add in footer

function bt_add_histories() {
    if (isset($_SERVER['HTTP_REFERER'])) {
        $link_url = $_SERVER['HTTP_REFERER'];
        if ((isset($_SESSION['currentpageurl']) && $_SESSION['currentpageurl'] != '') || $link_url != '') {
            $pos = strpos($_SESSION['currentpageurl'], $link_url);
            if ($pos == false) {
                if (isset($link_url) && $link_url != "") {
                    if (isset($_SESSION['currentpageurl']) && $_SESSION['currentpageurl'] != '') {
                        $_SESSION['currentpageurl'] = $_SESSION['currentpageurl'] . "@@" . $link_url;
                    } else {
                        $_SESSION['currentpageurl'] = $link_url;
                    }
                }
            }
        }
    }
}

//  add wpcf7 extra body
add_action('wpcf7_before_send_mail', 'bt_wpcf7_extra_body');   // hooks: wpcf7_before_send_mail, wpcf7_mail_sent

function bt_wpcf7_extra_body($contact_form) {
    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $mail = $contact_form->prop('mail');

        $current_url = isset($_SESSION['currentpageurl']) && $_SESSION['currentpageurl'] != '' ? $_SESSION['currentpageurl'] : '';
        if (isset($current_url) && $current_url != "") {
            $currentpage_list = explode('@@', $current_url);
            $visitor_page_history = '';
            $url_no = 0;
            for ($i = 0; $i < count($currentpage_list); $i++) {

                if (isset($currentpage_list[$i]) && $currentpage_list[$i] != '') {
                    $url_no = $url_no + 1;
                    $visitor_page_history .= "($url_no) " . $currentpage_list[$i] . "<br/>";
                }
            }
            $mail['body'] .= "<br/><br/>Visitor Page Histories :<br/>" . $visitor_page_history;
        }
        $current_link_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        /**
         * Added by Hinal Kapatel on 02/08/2021.
         * To correct Visitor Post URL previously was showing WP-JSON url (http://localhost/wordpress-5.8/wp-json/contact-form-7/v1/contact-forms/181/feedback)
         */
        $current_link_url = $submission->get_meta('url');
        /* 02/08/2021 Modification Done */
        $mail['body'] .= "<br/>Visitor Post Query From This URL " . $current_link_url;

        $myip = $_SERVER['REMOTE_ADDR'];
        if (isset($myip) && $myip != "") {
            $user_ip = "Click on Following Link to know more about this IP : http://whatismyipaddress.com/ip/$myip";
            $mail['body'] .= "<br/>" . $user_ip;
        }

        $contact_form->set_properties(array('mail' => $mail));
    }
}

add_action('admin_init', 'bt_contact_form_7_deactivate_extra_body');   // deactive plug-in when contact form 7 is deactive

function bt_contact_form_7_deactivate_extra_body() {
    $bt_wp_active_plugins = get_option('active_plugins');
    if (in_array("contact-form-7/wp-contact-form-7.php", $bt_wp_active_plugins) != true) {
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

if (!function_exists('cf7_history_init')) {
    add_action('admin_init', 'cf7_history_init');

    function cf7_history_init() {

        $active_plugins = get_option('active_plugins');

        if (in_array('contact-form-7/wp-contact-form-7.php', $active_plugins) != true) {
            add_action('admin_notices', 'not_found_CF7', 1);
            deactivate_plugins(plugin_basename(__FILE__));

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

}

function not_found_CF7() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><a href="https://wordpress.org/plugins/contact-form-7/">Contact Form 7</a> is necessary for <a href="https://wordpress.org/plugins/contact-form-7-browsing-history/"> Browsing History </a> plugin to work.</p>
    </div>

    <?php
}
