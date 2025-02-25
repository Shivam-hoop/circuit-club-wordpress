<?php if ( ! defined( 'ABSPATH' ) ) exit;
/**
*   detect the origin of an email
*
**/
class Haet_Sender_Plugin_BuddyPress extends Haet_Sender_Plugin {
    public function __construct($mail) {
        if( strpos( $mail['message'], '<!-- [IS BUDDYPRESS EMAIL] -->' ) === false )
            throw new Haet_Different_Plugin_Exception();
    }


    /**
    *   modify_content()
    *   mofify the email content before applying the template
    **/
    public function modify_content($content){
        $content = str_replace( '<!-- [IS BUDDYPRESS EMAIL] -->', '', $content );
        return $content;
    }

    /**
    *   modify_template()
    *   mofify the email template before the content is added
    **/
    public function modify_template($template){
        return $template;
    } 



    // public static function apply_template_for_preview( $message ){
    //     if ( !empty( $_GET['buddypress_action'] ) && 'preview_email' == $_GET['buddypress_action'] ) {
    //         if( self::is_wphtmlmail_template_selected() ){
    //             $email = array(
    //                 'to'        => '', 
    //                 'subject'   => '', 
    //                 'message'   => $message, 
    //                 'headers'   => '', 
    //                 'attachments' => array()
    //             );

    //             $email = Haet_Mail()->style_mail( $email );
    //             $message = $email['message'];
    //         }
    //     }
    //     return $message;
    // }



    public static function plugin_actions_and_filters(){
        // force buddypress to use wp_mail()
        add_filter( 'bp_email_use_wp_mail', '__return_true' );

        // because of the filter above buddypress wants to send emails in plaintext so we change this back to html
        // have a look at bp-core-functions.php function bp_send_email
        add_filter( 'bp_email_get_content_plaintext', function($original_value, $property_name, $transform, $email){
            if( $transform == 'replace-tokens' )
                return $email->get( 'content_html', 'add-content' ) . '<!-- [IS BUDDYPRESS EMAIL] -->';
            else 
                return $original_value;
        }, 10, 4 );
        

        // remove email customizer admin menu
        if( function_exists( 'bp_get_email_post_type' ) ){
            add_action( 'admin_menu', function(){
                remove_submenu_page( 'edit.php?post_type=' . bp_get_email_post_type(), 'bp-emails-customizer-redirect' );
            }, 20 );
        }
        
        // register template for preview
        add_filter( 'bp_core_render_email_template', function( $template ){
            return HAET_MAIL_BUDDYPRESS_PATH.'/views/templates/buddypress-email-preview.php';
        });
    }

}