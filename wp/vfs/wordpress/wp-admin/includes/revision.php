<?php
 function wp_get_revision_ui_diff( $post, $compare_from, $compare_to ) { $post = get_post( $post ); if ( ! $post ) { return false; } if ( $compare_from ) { $compare_from = get_post( $compare_from ); if ( ! $compare_from ) { return false; } } else { $compare_from = false; } $compare_to = get_post( $compare_to ); if ( ! $compare_to ) { return false; } if ( $compare_from && $compare_from->post_parent !== $post->ID && $compare_from->ID !== $post->ID ) { return false; } if ( $compare_to->post_parent !== $post->ID && $compare_to->ID !== $post->ID ) { return false; } if ( $compare_from && strtotime( $compare_from->post_date_gmt ) > strtotime( $compare_to->post_date_gmt ) ) { $temp = $compare_from; $compare_from = $compare_to; $compare_to = $temp; } if ( $compare_from && empty( $compare_from->post_title ) ) { $compare_from->post_title = __( '(no title)' ); } if ( empty( $compare_to->post_title ) ) { $compare_to->post_title = __( '(no title)' ); } $return = array(); foreach ( _wp_post_revision_fields( $post ) as $field => $name ) { $content_from = $compare_from ? apply_filters( "_wp_post_revision_field_{$field}", $compare_from->$field, $field, $compare_from, 'from' ) : ''; $content_to = apply_filters( "_wp_post_revision_field_{$field}", $compare_to->$field, $field, $compare_to, 'to' ); $args = array( 'show_split_view' => true, 'title_left' => __( 'Removed' ), 'title_right' => __( 'Added' ), ); $args = apply_filters( 'revision_text_diff_options', $args, $field, $compare_from, $compare_to ); $diff = wp_text_diff( $content_from, $content_to, $args ); if ( ! $diff && 'post_title' === $field ) { $diff = '<table class="diff"><colgroup><col class="content diffsplit left"><col class="content diffsplit middle"><col class="content diffsplit right"></colgroup><tbody><tr>'; if ( true === $args['show_split_view'] ) { $diff .= '<td>' . esc_html( $compare_from->post_title ) . '</td><td></td><td>' . esc_html( $compare_to->post_title ) . '</td>'; } else { $diff .= '<td>' . esc_html( $compare_from->post_title ) . '</td>'; if ( $compare_from->post_title !== $compare_to->post_title ) { $diff .= '</tr><tr><td>' . esc_html( $compare_to->post_title ) . '</td>'; } } $diff .= '</tr></tbody>'; $diff .= '</table>'; } if ( $diff ) { $return[] = array( 'id' => $field, 'name' => $name, 'diff' => $diff, ); } } return apply_filters( 'wp_get_revision_ui_diff', $return, $compare_from, $compare_to ); } function wp_prepare_revisions_for_js( $post, $selected_revision_id, $from = null ) { $post = get_post( $post ); $authors = array(); $now_gmt = time(); $revisions = wp_get_post_revisions( $post->ID, array( 'order' => 'ASC', 'check_enabled' => false, ) ); if ( ! wp_revisions_enabled( $post ) ) { foreach ( $revisions as $revision_id => $revision ) { if ( ! wp_is_post_autosave( $revision ) ) { unset( $revisions[ $revision_id ] ); } } $revisions = array( $post->ID => $post ) + $revisions; } $show_avatars = get_option( 'show_avatars' ); cache_users( wp_list_pluck( $revisions, 'post_author' ) ); $can_restore = current_user_can( 'edit_post', $post->ID ); $current_id = false; foreach ( $revisions as $revision ) { $modified = strtotime( $revision->post_modified ); $modified_gmt = strtotime( $revision->post_modified_gmt . ' +0000' ); if ( $can_restore ) { $restore_link = str_replace( '&amp;', '&', wp_nonce_url( add_query_arg( array( 'revision' => $revision->ID, 'action' => 'restore', ), admin_url( 'revision.php' ) ), "restore-post_{$revision->ID}" ) ); } if ( ! isset( $authors[ $revision->post_author ] ) ) { $authors[ $revision->post_author ] = array( 'id' => (int) $revision->post_author, 'avatar' => $show_avatars ? get_avatar( $revision->post_author, 32 ) : '', 'name' => get_the_author_meta( 'display_name', $revision->post_author ), ); } $autosave = (bool) wp_is_post_autosave( $revision ); $current = ! $autosave && $revision->post_modified_gmt === $post->post_modified_gmt; if ( $current && ! empty( $current_id ) ) { if ( $current_id < $revision->ID ) { $revisions[ $current_id ]['current'] = false; $current_id = $revision->ID; } else { $current = false; } } elseif ( $current ) { $current_id = $revision->ID; } $revisions_data = array( 'id' => $revision->ID, 'title' => get_the_title( $post->ID ), 'author' => $authors[ $revision->post_author ], 'date' => date_i18n( __( 'M j, Y @ H:i' ), $modified ), 'dateShort' => date_i18n( _x( 'j M @ H:i', 'revision date short format' ), $modified ), 'timeAgo' => sprintf( __( '%s ago' ), human_time_diff( $modified_gmt, $now_gmt ) ), 'autosave' => $autosave, 'current' => $current, 'restoreUrl' => $can_restore ? $restore_link : false, ); $revisions[ $revision->ID ] = apply_filters( 'wp_prepare_revision_for_js', $revisions_data, $revision, $post ); } if ( 1 === count( $revisions ) ) { $revisions[ $post->ID ] = array( 'id' => $post->ID, 'title' => get_the_title( $post->ID ), 'author' => $authors[ $revision->post_author ], 'date' => date_i18n( __( 'M j, Y @ H:i' ), strtotime( $post->post_modified ) ), 'dateShort' => date_i18n( _x( 'j M @ H:i', 'revision date short format' ), strtotime( $post->post_modified ) ), 'timeAgo' => sprintf( __( '%s ago' ), human_time_diff( strtotime( $post->post_modified_gmt ), $now_gmt ) ), 'autosave' => false, 'current' => true, 'restoreUrl' => false, ); $current_id = $post->ID; } if ( empty( $current_id ) ) { if ( $revisions[ $revision->ID ]['autosave'] ) { $revision = end( $revisions ); while ( $revision['autosave'] ) { $revision = prev( $revisions ); } $current_id = $revision['id']; } else { $current_id = $revision->ID; } $revisions[ $current_id ]['current'] = true; } $compare_two_mode = is_numeric( $from ); if ( ! $compare_two_mode ) { $found = array_search( $selected_revision_id, array_keys( $revisions ), true ); if ( $found ) { $from = array_keys( array_slice( $revisions, $found - 1, 1, true ) ); $from = reset( $from ); } else { $from = 0; } } $from = absint( $from ); $diffs = array( array( 'id' => $from . ':' . $selected_revision_id, 'fields' => wp_get_revision_ui_diff( $post->ID, $from, $selected_revision_id ), ), ); return array( 'postId' => $post->ID, 'nonce' => wp_create_nonce( 'revisions-ajax-nonce' ), 'revisionData' => array_values( $revisions ), 'to' => $selected_revision_id, 'from' => $from, 'diffData' => $diffs, 'baseUrl' => parse_url( admin_url( 'revision.php' ), PHP_URL_PATH ), 'compareTwoMode' => absint( $compare_two_mode ), 'revisionIds' => array_keys( $revisions ), ); } function wp_print_revision_templates() { global $post; ?><script id="tmpl-revisions-frame" type="text/html">
		<div class="revisions-control-frame"></div>
		<div class="revisions-diff-frame"></div>
	</script>

	<script id="tmpl-revisions-buttons" type="text/html">
		<div class="revisions-previous">
			<input class="button" type="button" value="<?php echo esc_attr_x( 'Previous', 'Button label for a previous revision' ); ?>" />
		</div>

		<div class="revisions-next">
			<input class="button" type="button" value="<?php echo esc_attr_x( 'Next', 'Button label for a next revision' ); ?>" />
		</div>
	</script>

	<script id="tmpl-revisions-checkbox" type="text/html">
		<div class="revision-toggle-compare-mode">
			<label>
				<input type="checkbox" class="compare-two-revisions"
				<#
				if ( 'undefined' !== typeof data && data.model.attributes.compareTwoMode ) {
					#> checked="checked"<#
				}
				#>
				/>
				<?php esc_html_e( 'Compare any two revisions' ); ?>
			</label>
		</div>
	</script>

	<script id="tmpl-revisions-meta" type="text/html">
		<# if ( ! _.isUndefined( data.attributes ) ) { #>
			<div class="diff-title">
				<# if ( 'from' === data.type ) { #>
					<strong><?php _ex( 'From:', 'Followed by post revision info' ); ?></strong>
				<# } else if ( 'to' === data.type ) { #>
					<strong><?php _ex( 'To:', 'Followed by post revision info' ); ?></strong>
				<# } #>
				<div class="author-card<# if ( data.attributes.autosave ) { #> autosave<# } #>">
					{{{ data.attributes.author.avatar }}}
					<div class="author-info">
					<# if ( data.attributes.autosave ) { #>
						<span class="byline">
						<?php
 printf( __( 'Autosave by %s' ), '<span class="author-name">{{ data.attributes.author.name }}</span>' ); ?>
							</span>
					<# } else if ( data.attributes.current ) { #>
						<span class="byline">
						<?php
 printf( __( 'Current Revision by %s' ), '<span class="author-name">{{ data.attributes.author.name }}</span>' ); ?>
							</span>
					<# } else { #>
						<span class="byline">
						<?php
 printf( __( 'Revision by %s' ), '<span class="author-name">{{ data.attributes.author.name }}</span>' ); ?>
							</span>
					<# } #>
						<span class="time-ago">{{ data.attributes.timeAgo }}</span>
						<span class="date">({{ data.attributes.dateShort }})</span>
					</div>
				<# if ( 'to' === data.type && data.attributes.restoreUrl ) { #>
					<input  <?php if ( wp_check_post_lock( $post->ID ) ) { ?>
						disabled="disabled"
					<?php } else { ?>
						<# if ( data.attributes.current ) { #>
							disabled="disabled"
						<# } #>
					<?php } ?>
					<# if ( data.attributes.autosave ) { #>
						type="button" class="restore-revision button button-primary" value="<?php esc_attr_e( 'Restore This Autosave' ); ?>" />
					<# } else { #>
						type="button" class="restore-revision button button-primary" value="<?php esc_attr_e( 'Restore This Revision' ); ?>" />
					<# } #>
				<# } #>
			</div>
		<# if ( 'tooltip' === data.type ) { #>
			<div class="revisions-tooltip-arrow"><span></span></div>
		<# } #>
	<# } #>
	</script>

	<script id="tmpl-revisions-diff" type="text/html">
		<div class="loading-indicator"><span class="spinner"></span></div>
		<div class="diff-error"><?php _e( 'Sorry, something went wrong. The requested comparison could not be loaded.' ); ?></div>
		<div class="diff">
		<# _.each( data.fields, function( field ) { #>
			<h3>{{ field.name }}</h3>
			{{{ field.diff }}}
		<# }); #>
		</div>
	</script>
	<?php
} 