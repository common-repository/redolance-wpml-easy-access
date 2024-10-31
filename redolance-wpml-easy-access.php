<?php
/*
Plugin Name: Redolance WPML Easy Access
Version: 1.0.0
Stable tag: 1.0.0
Description: This plugin WPML menu on the admin bar so you can access and edit the post translation fast and easy. 
Author: Redolance
Author URI: https://redolance.com
License: GPLv2 or later
Text Domain: redolance
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


/* add custom edit post menu to front end admin bar */
add_action( 'admin_bar_menu', 'redo_rwea_translation_edit_admin_bar', 500 );

function redo_rwea_translation_edit_admin_bar() {

	/* Only for admins and editors */
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}

	/* Only If WPML plugin activated */
	if ( ! class_exists( 'Sitepress' ) ) {
		return('WPML plugin is not avilable');
	}

	global $wp_admin_bar, $wp_the_query, $sitepress, $post_status_display;

	$p_translations = $sitepress->post_translations();

	$queriedObject = $wp_the_query->get_queried_object();

	if ( ! empty( $queriedObject ) && ! empty( $queriedObject->post_type ) ) {

		$trid = $p_translations->get_element_trid( $queriedObject->ID );
		if ( $trid ) {
			$original_id = $p_translations->get_original_post_ID( $trid );
			$original_lang = $p_translations->get_element_lang_code( $original_id );
			$original_flag = '<img style="display: inline-block; margin: 0 2px;" src="' . esc_url( $sitepress->get_flag_url( $original_lang ) ) .'">';

			/* wpml icons style */ 
			wp_enqueue_style( 'otgs-ico' );

			/* get active languages */
			$active_languages = $sitepress->get_active_languages();
			$active_languages = apply_filters( 'wpml_active_languages_access', $active_languages, array( 'action' => 'edit' ) );

			/* translation status for all active languages */ 
			if ( null === $post_status_display ) {
				$post_status_display = new WPML_Post_Status_Display( $active_languages );
			}

		    // Top Level Menu Node.
		    $wp_admin_bar->add_menu( array(
		        'id' => 'redolnce_product-menu',
		        'title' => '<span class="ab-icon dashicons dashicons-translation"></span><span class="ab-label"> Advance Edit</span>',
		        'href' => false
		    ) );

			// Edit Original Menu Node.
		   	$wp_admin_bar->add_menu( array(
		        'id' => 'edit_'. $original_lang .'-translation',
		        'parent' => 'redolnce_product-menu',
		        'title' =>  __( 'Edit Original ', 'wpml-translation-management' ). $original_flag,
		        'href' => get_edit_post_link($original_id),
		        'meta' => array( 'target'=>'_blank' )
		    ) );

			// exclude original language from active languages array.
			unset( $active_languages[ $original_lang ] );

		    // Add Sub menu to Edit Post Translations for current front end page.
			foreach ( $active_languages as $language_data ) {
				// get translation statu data.
				$lang = $language_data['code'];

				$status_data = $post_status_display->get_status_data( $original_id, $lang );
				list( $text, $link, $trid, $css_class ) = $status_data;

				// Link to edit translation.
				$link = apply_filters( 'wpml_link_to_translation', $link, $original_id, $lang, $trid );
				// split link after 'return_url'.
				$link_split = explode('return_url',$link);

				// parse_url to seperate parameters.
				$link_parts = parse_url($link);
				parse_str($link_parts['query'], $query);

				$return_url = 'return_url=/wp-admin/edit.php?post_type=' . $queriedObject->post_type;
				$job_id = '&job_id=' . $query['job_id'];

				// in case of product the translation not updated untill we visit the edit products page, so we add the return_url parametr to the link.
				if ( $queriedObject->post_type === 'product' ){
					$return_url = 'return_url=/wp-admin/admin.php?page=wpml-wcml';
				}

				// Rebuild the link.
				$link = $link_split[0] . $return_url . $job_id;
				
				// Css for translition status icon.
				$css_class = apply_filters( 'wpml_css_class_to_translation', $css_class, $original_id, $lang, $trid );

				// Text for translition status.
				$text = apply_filters( 'wpml_text_to_translation', $text, $original_id, $lang, $trid ); 
				$text = str_replace(array('translation', 'the', 'to'), '', $text);

				// Create Icon html to add to menu.
				$icon_html = '<i class="' . $css_class . ' js-otgs-popover-tooltip" title="' . esc_attr( $text ) . '"></i>';
				
				// language flag.
				$flag = '<img style="display: inline-block; margin: 0 2px;" src="' . esc_url( $sitepress->get_flag_url( $language_data['code'] ) ) .'">';

				if ( $link ) {
					// adding the sub menu.
				    $wp_admin_bar->add_menu( array(
				        'id' => 'edit_'. $language_data['code'] .'-translation',
				        'parent' => 'redolnce_product-menu',
				        'title' => $icon_html . ' ' . $text . $flag,
				        'href' =>  admin_url() . $link,
				        'meta' => array( 'target'=>'_blank')
				    ) );
				}
			}
		}
	}
}