<?php
/*
Plugin Name: Sugar Event Calendar - Gravity Forms Integration
Plugin URI: http://pippinsplugins.com
Description: Allows you to create event registration forms with Gravity Forms
Author: Pippin Williamson
Author URI: http://pippinsplugins.com
Version: 1.0
*/


/**
 * Load text domain
 *
 * @since       1.0 
 */
function scgf_load_plugin_textdomain() {

	load_plugin_textdomain( 'pippin_scgf', false, dirname( plugin_basename( SC_PLUGIN_FILE ) ) . '/languages/' );

}
add_action( 'init', 'scgf_load_plugin_textdomain' );


/**
 * Add fields to meta box
 *
 * @since       1.0 
 */
function scgf_add_forms_meta_box() {

	global $post;

	echo '<tr class="sc_meta_box_row">';

		echo '<td class="sc_meta_box_td" colspan="2" valign="top"><label for="sc_event_gf_forms">' . __( 'Gravity Forms', 'pippin_scgf' ) . '</label></td>';

		$selected_form = get_post_meta( $post->ID, 'sc_event_gf_form', true );
		$title         = get_post_meta( $post->ID, 'sc_event_gf_form_title', true ) ? true : false;
		$description   = get_post_meta( $post->ID, 'sc_event_gf_form_description', true ) ? true : false;

		$forms = RGFormsModel::get_forms( null, 'title' );

		if ( sizeof( $forms ) > 0 ) {

			echo '<td class="sc_meta_box_td" colspan="4">';

				echo '<select name="sc_event_gf_form">';
					
					echo '<option value="none">' . __( 'None', 'pippin_scgf' ) . '</option>';

					foreach( $forms as $form ) {

						echo '<option value="' . esc_attr( $form->id ) . '" ' . selected( $selected_form, $form->id, false ) . '>' . esc_html( $form->title ) . '</option>';

					}

				echo '</select>&nbsp;';

				echo '<span class="description">' . __( 'Select a form to display on the details page for this event.', 'pippin_scgf' ) . '</span><br/>';

				echo '<p class="scgf_form_title">';
					echo '<input type="checkbox" name="sc_event_gf_form_title" value="1" ' . checked( $title, true, false ) . '/>&nbsp;';
					echo '<span class="description">' . __( 'Show the form title?', 'pippin_scgf' ) . '</span><br/>';
				echo '</p>';

				echo '<p class="scgf_form_desc">';
					echo '<input type="checkbox" name="sc_event_gf_form_description" value="1" ' . checked( $description, true, false ) . '/>&nbsp;';
					echo '<span class="description">' . __( 'Show the form description?', 'pippin_scgf' ) . '</span><br/>';
				echo '</p>';

					echo '<input type="hidden" name="scgf_meta_box_nonce" value="' . esc_attr( wp_create_nonce( basename( __FILE__ ) ) ) . '" />';

			echo '</td>';

		} else {

			echo '<td class="sc_meta_box_td" colspan="4">';
				echo '<p>' . __( 'You have not created any forms yet.', 'pippin_scgf' ) . '</p>';
			echo '</td>';

		}

	echo '</tr>';

}
add_action( 'sc_event_meta_box_after', 'scgf_add_forms_meta_box' );


/**
 * Save Gravity Form Field
 *
 * Save data from meta box.
 *
 * @access      private
 * @since       1.0 
 * @return      void
*/
function scgf_meta_box_save( $post_id ) {
	global $post;
	
	// verify nonce
	if ( ! isset($_POST['scgf_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['scgf_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// check autosave
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) 
		return $post_id;
	
	//don't save if only a revision
	if ( isset( $post->post_type ) && $post->post_type == 'revision' ) 
		return $post_id;

	// check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}
		
	$form_id     = sanitize_text_field( $_POST['sc_event_gf_form'] );
	$title       = isset( $_POST['sc_event_gf_form_title'] ) ? true : false;
	$description = isset( $_POST['sc_event_gf_form_description'] ) ? true : false;

	update_post_meta( $post_id, 'sc_event_gf_form', $form_id );
	update_post_meta( $post_id, 'sc_event_gf_form_title', $title );
	update_post_meta( $post_id, 'sc_event_gf_form_description', $description );
		
}
add_action( 'save_post', 'scgf_meta_box_save' );


/**
 * Display Gravity Form
 *
 * @since       1.0 
 */
function scgf_show_form( $event_id ) {

	if ( ( $form = get_post_meta( $event_id, 'sc_event_gf_form', true ) ) && $form != 'none' ) {

		$title        = get_post_meta( $event_id, 'sc_event_gf_form_title', true ) ? true : false;
		$description  = get_post_meta( $event_id, 'sc_event_gf_form_description', true ) ? true : false;
		$field_values = apply_filters( 'scgf_gravity_form_field_values', array( 
			'event_id'    => $event_id, 
			'event_title' => get_the_title( $event_id ), 
		) );

		gravity_form( $form, $title, $description, false, $field_values, true );

	}

}
add_action( 'sc_after_event_content', 'scgf_show_form' );
