<?php
/**
 * Plugin Name: Mah Download Manager
 * Plugin URI: https://github.com/emeaguiar/mah-download-manager
 * Description: A simple download manager for WordPress
 * Version: 1.0
 * Author: Mario Aguiar
 * Author URI: http://www.marioaguiar.net
 * License: GPL2
 */
class Mah_Download_Manager {
    private $uploadsDirectory;

    function __construct() {
        $this->uploadsDirectory = wp_upload_dir( current_time( 'mysql' ) );
        add_action( 'admin_menu', array( $this, 'register_menu_pages' ) );
        add_action( 'mdm_display_messages', array( $this, 'display_messages' ) );
    }

    function register_menu_pages() {
        add_menu_page( __( 'Mah Download manager', 'mah-download-manager' ), __( 'Downloads', 'mah-download-manager' ), 'manage_options', 'mah-download-manager', array( $this, 'display_menu_page' ), 'dashicons-download', 12 );
        add_submenu_page( 'mah-download-manager', __( 'Add new file', 'mah-download-manager' ), __( 'Add new file', 'mah-download-manager' ), 'upload_files', 'mah-download-manager/new', array( $this, 'display_add_new_page' ) );
    }

    function display_menu_page() {

?>
        <div class="wrap">
            <h2><?php _e( 'Mah Download manager', 'mah_download_manager' ); ?> <a class="add-new-h2" href="<?php echo admin_url( 'admin.php?page=mah-download-manager/new' ); ?>"><?php _e( 'Add new file', 'mah_download_manager' ); ?></a></h2>
            <?php  do_action( 'mdm_display_messages' ); ?>
            <p>This space will display a list of files...</p>
        </div>
<?php
    }

    function display_add_new_page() {

        if ( $this->form_is_submitted() ) {
            return;
        }
?>
        <div class="wrap">
            <h2><?php _e( 'Add new file', 'mah-download-manager' ); ?></h2>
            <form action="" method="post" class="wp-upload-form" enctype="multipart/form-data">
                <?php wp_nonce_field( 'mah-download-manager' ); ?>
                <label for="mdm-file"><?php _e( 'File', 'mah-download-manager' ); ?>:</label>
                <input type="file" id="mdm-file" name="mdm-file">
                <input type="submit" value="<?php _e( 'Upload', 'mah-download-manager' ); ?>" name="mdm-upload">
            </form>
        </div>
<?php
    }

    function form_is_submitted() {
        if ( empty( $_POST ) ) {
            return false;
        }
        check_admin_referer( 'mah-download-manager' );

        $mdm_form_fields = array( 'mdm-file', 'mdm-upload' );
        $mdm_method = '';

        if ( isset( $_POST[ 'mdm-upload' ] ) ) {
            $url = wp_nonce_url( 'mah-download-manager/new', 'mah-download-manager' );
            if ( ! $creds = request_filesystem_credentials( $url, $mdm_method, false, false, $mdm_form_fields ) ) {
                return true;
            }

            if ( ! WP_Filesystem( $creds ) ) {
                request_filesystem_credentials( $url, $mdm_method, true, false, $mdm_form_fields );
                return true;
            }

            $fileTempData = $_FILES[ 'mdm-file' ];

            $this->upload_file( $fileTempData );
        }

        return true;

    }

    function upload_file( $file ) {
        $file = ( ! empty( $file ) ) ? $file : new WP_Error( 'empty_file', __( "Seemls like you didn't upload a file.", 'mah-download-manager' ) );

        if ( is_wp_error( $file ) ) {
            wp_die( $file->get_error_message(), __( 'Error uploading the file.', 'mah-download-manager' ) );
        }

        $fileTempDir = $file[ 'tmp_name' ];
        $filename = trailingslashit( $this->uploadsDirectory[ 'path' ] ) . $file[ 'name' ];

        $response = $this->move_file( $fileTempDir, $filename );

        if ( is_wp_error( $response ) ) {
            wp_die( $response->get_error_message(), __( 'Error uploading the file.', 'mah-download-manager' ) );
        }

        wp_redirect( admin_url( 'admin.php?page=mah-download-manager&message=1' ) );
        exit;
    }

    function move_file( $from, $to ) {
        global $wp_filesystem;
        if ( $wp_filesystem->move( $from, $to ) ) {
            return $to;
        } else {
            return WP_Error( 'moving_error', __( "Error trying to move the file to the new location.", 'mah-download-manager' ) );
        }
    }

    function display_messages() {
        if ( ! isset( $_GET[ 'message' ] ) ) {
            return;
        }

        $message = $_GET[ 'message' ];

        switch ( $message ) {
            case 1:
                $class = 'updated';
                $text = __( 'File uploaded succesfully.', 'mah-download-manager' );
                break;
        }

        echo '<div class="' . $class . '"><p>' . $text . '</p></div>';
    }
}

$mah_download_manager = new Mah_Download_Manager;