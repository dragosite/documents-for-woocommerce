<?php
/**
 * Downloadable documents for products in WooCommerce
 *
 * @package           wpharvest
 * @author            Dragos Micu
 * @copyright         2021 Dragos Micu
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Documents for WooCommerce
 * Description:       Downloadable documents for products in WooCommerce.
 * Version:           1.0
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            Dragos Micu
 * Author URI:        https://wpharvest.com/
 * Text Domain:       wpharvest
 *
 * WC requires at least: 3.4
 * WC tested up to: 5.0.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if( !class_exists( 'WPHRV_Documents' ) ){ // && class_exists( 'WooCommerce' )

	class WPHRV_Documents{

        public $version;

        // Singleton Pattern
        public static $instance;

        public static function getInstance(){
            if( !isset(WPHRV_Documents::$instance) ){
                WPHRV_Documents::$instance = new WPHRV_Documents();
            }
            return WPHRV_Documents::$instance;
        }

        private function __construct(){
            $this->version = "1.0.0";

            // Create API endpoint
            add_action('parse_request', array($this, 'endpoint_ba_product_documents'));

            // Add a custom product data tab
            add_filter('woocommerce_product_tabs', array($this, 'documents_product_tab'));
            add_filter('woocommerce_product_data_tabs', array($this, 'documents_product_data_tab') , 99 , 1);
            add_action('woocommerce_product_data_panels', array($this, 'documents_product_panels'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_ajax_add_document', array($this, 'add_document_template'));
            add_action('wp_ajax_nopriv_add_document', array($this, 'add_document_template'));
            add_action('woocommerce_process_product_meta_simple', array($this, 'save_documents'));
            add_action('woocommerce_process_product_meta_variable', array($this, 'save_documents'));
        }

        /**
         * Enqueue scripts and styles
         */
        public function enqueue_scripts(){
            wp_enqueue_script( 'documents-scripts', plugin_dir_url( __FILE__ ) . 'assets/main.js', array('jquery'), time(), true );
            wp_enqueue_style( 'documents-style', plugin_dir_url( __FILE__ ) . 'assets/main.css', array(), time() );
        }

        /**
         * Add tab to the frontend
         */
        public function documents_product_tab($tabs){
            // Adds the new tab
            global $product;
            $tab_title = get_post_meta( $product->get_id(), 'document_main_title', true ) ? get_post_meta( $product->get_id(), 'document_main_title', true ) : 'Documents';

            $tabs['documents_tab'] = array(
                'title' 	=> $tab_title,
                'priority' 	=> 50,
                'callback' 	=> array($this, 'documents_tab_content')
            );

            return $tabs;
        }

        /**
         * Add tab content to the frontend
         */
        public function documents_tab_content(){
            // The new tab content
            global $product;
            $tab_title = get_post_meta( $product->get_id(), 'document_main_title', true ) ? get_post_meta( $product->get_id(), 'document_main_title', true ) : 'Documents';

            echo '<h2>' . $tab_title . '</h2>';
            $documents = get_post_meta( $product->get_id(), 'documents', true );
            if( $documents ){
                echo '<ul class="woocommerce_documents">';
                foreach( $documents as $key => $each_document ) {
                    echo '<li><a href="'.$each_document["url"].'" target="_blank">'.$each_document["name"].'</a></li>';
                }
                echo '</ul>';
            }
        }

        /**
         * Add Documents in product dashboard option
         */
        public function documents_product_data_tab( $product_data_tabs ){
            $product_data_tabs['ba-documents'] = array(
                'label' => __( 'Documents', 'ba-international' ),
                'target' => 'documents_product_data',
                'priority' => '21'
            );
            return $product_data_tabs;
        }

        /**
         * Add Documents content in product dashboard option
         */
        public function documents_product_panels(){
            global $post;
            $tab_title = get_post_meta( $post->ID, 'document_main_title', true ) ? get_post_meta( $post->ID, 'document_main_title', true ) : 'Documents';

            echo '<div id="documents_product_data" class="panel woocommerce_options_panel hidden">';

            woocommerce_wp_text_input( array(
                'id'                => 'document_main_title',
                'value'             => $tab_title,
                'label'             => 'Documents Tab Title',
                'description'       => 'This is the name of the tab shown on the frontend',
                'desc_tip'          => true,
            ) );

            $documents = get_post_meta( get_the_ID(), 'documents', true );
            if( $documents ){
                $i = 1;
                echo '<table class="widefat woocommerce_documents">';
                    echo '<thead>';
                        echo '<tr>';
                            echo '<th></th>';
                            echo '<th colspan="2">Name'. wc_help_tip("This is the name of the document shown to the customer") . '</th>';
                            echo '<th colspan="2">File URL'. wc_help_tip("This is the URL or absolute path to the file which customers will get access to. URLs entered here should already be encoded.") . '</th>';
                            echo '<th></th>';
                            echo '<th></th>';
                        echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                        foreach( $documents as $key => $each_document ) {
                            echo '<tr>';
                            include __DIR__ . '/templates/html-product-document.php';
                            $i++;
                            echo '</tr>';
                        }
                    echo '</tbody>';
                echo '</table>';
                echo '<a href="#" class="button add_doc_button">' . esc_html__( "Add document", "woocommerce" ) . '</a>';
            }

            echo '</div>';
        }

        /**
         * Ajax call for creating new document row
         */
        public function add_document_template(){
            echo include __DIR__ . '/templates/html-product-document.php';
            die();
        }

        public function save_documents($post_id){
            $document_main_title = isset( $_POST['document_main_title'] ) ? sanitize_text_field($_POST['document_main_title']) : '';
            $document_title = isset( $_POST['document_title'] ) ? sanitize_text_field($_POST['document_title']) : array();
            $document_url = isset( $_POST['document_url'] ) ? sanitize_text_field($_POST['document_url']) : array();
            $all_documents = array();

            for( $i = 0; $i < count($document_title); $i++ ){

                if( null !== $document_title[$i] && null !== $document_url[$i] ){
                    $all_documents[] = array(
                        'name' => $document_title[$i],
                        'url' => $document_url[$i]
                    );
                }
            }
	        update_post_meta( $post_id, 'documents', $all_documents );
	        update_post_meta( $post_id, 'document_main_title', $document_main_title );
        }
    }
}

$WPHRV_Documents = WPHRV_Documents::getInstance();