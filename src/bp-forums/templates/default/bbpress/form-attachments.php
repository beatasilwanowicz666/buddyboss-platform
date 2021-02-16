<?php

/**
 * New/Edit Forum Form Attachments
 *
 * @package BuddyBoss\Theme
 */

$group_id = 0;
$forum_id = 0;
if ( bp_is_active( 'groups' ) && bp_is_group_single() ) {
	$group_id = bp_get_current_group_id();
}
if ( bbp_is_single_forum() ) {
	$forum_id = bbp_get_forum_id();
} elseif ( bbp_is_single_topic() ) {
	$forum_id = bbp_get_topic_forum_id( bbp_get_topic_id() );
} elseif ( bbp_is_single_reply() ) {
	$topic_id = bbp_get_reply_topic_id( bbp_get_reply_id() );
	$forum_id = bbp_get_topic_forum_id( $topic_id );
}
$extensions       = bp_is_active( 'media' ) ? bp_document_get_allowed_extension() : false;
$video_extensions = bp_is_active( 'media' ) ? bp_video_get_allowed_extension() : false;
?>

<?php do_action( 'bbp_theme_before_forums_form_attachments' ); ?>

<div id="whats-new-attachments">

	<?php if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) : ?>
		<div class="dropzone closed" id="forums-post-media-uploader" data-key="<?php echo esc_attr( bp_unique_id( 'forums_media_uploader_' ) ); ?>"></div>
		<input name="bbp_media" id="bbp_media" type="hidden" value=""/>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) :
		?>
		<div class="forums-attached-gif-container closed" data-key="<?php echo esc_attr( bp_unique_id( 'forums_attached_gif_container_' ) ); ?>">
			<div class="gif-image-container">
				<img src="" alt="">
			</div>
			<div class="gif-image-remove gif-image-overlay">
				<i class="bb-icon-close"></i>
			</div>
		</div>
		<input name="bbp_media_gif" id="bbp_media_gif" type="hidden" value=""/>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0 ) ) :
		?>
		<div class="dropzone closed" id="forums-post-document-uploader" data-key="<?php echo esc_attr( wp_unique_id( 'forums_document_uploader_' ) ); ?>"></div>
		<input name="bbp_document" id="bbp_document" type="hidden" value=""/>
	<?php endif; ?>

	<?php if ( bp_is_active( 'media' ) && bp_is_forums_video_support_enabled() ) : ?>
		<div class="dropzone closed" id="forums-post-video-uploader" data-key="<?php echo esc_attr( bp_unique_id( 'forums_video_uploader_' ) ); ?>"></div>
		<input name="bbp_video" id="bbp_video" type="hidden" value=""/>
		<div class="forum-post-video-template" style="display:none;">
			<div class="dz-preview dz-file-preview well" id="dz-preview-template">
				<div class="dz-details">
					<div class="dz-filename"><span data-dz-name></span></div>
					<div class="dz-size" data-dz-size></div>
				</div>
				<div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
				<div class="dz-error-message"><span data-dz-errormessage></span></div>
				<div class="dz-success-mark">
					<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Check</title>
						<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
							<path d="M23.5,31.8431458 L17.5852419,25.9283877 C16.0248253,24.3679711 13.4910294,24.366835 11.9289322,25.9289322 C10.3700136,27.4878508 10.3665912,30.0234455 11.9283877,31.5852419 L20.4147581,40.0716123 C20.5133999,40.1702541 20.6159315,40.2626649 20.7218615,40.3488435 C22.2835669,41.8725651 24.794234,41.8626202 26.3461564,40.3106978 L43.3106978,23.3461564 C44.8771021,21.7797521 44.8758057,19.2483887 43.3137085,17.6862915 C41.7547899,16.1273729 39.2176035,16.1255422 37.6538436,17.6893022 L23.5,31.8431458 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z" stroke-opacity="0.198794158" stroke="#747474" fill-opacity="0.816519475" fill="#FFFFFF"></path>
						</g>
					</svg>
				</div>
				<div class="dz-error-mark">
					<svg width="54px" height="54px" viewBox="0 0 54 54" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Error</title>
						<g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
							<g stroke="#747474" stroke-opacity="0.198794158" fill="#FFFFFF" fill-opacity="0.816519475">
								<path d="M32.6568542,29 L38.3106978,23.3461564 C39.8771021,21.7797521 39.8758057,19.2483887 38.3137085,17.6862915 C36.7547899,16.1273729 34.2176035,16.1255422 32.6538436,17.6893022 L27,23.3431458 L21.3461564,17.6893022 C19.7823965,16.1255422 17.2452101,16.1273729 15.6862915,17.6862915 C14.1241943,19.2483887 14.1228979,21.7797521 15.6893022,23.3461564 L21.3431458,29 L15.6893022,34.6538436 C14.1228979,36.2202479 14.1241943,38.7516113 15.6862915,40.3137085 C17.2452101,41.8726271 19.7823965,41.8744578 21.3461564,40.3106978 L27,34.6568542 L32.6538436,40.3106978 C34.2176035,41.8744578 36.7547899,41.8726271 38.3137085,40.3137085 C39.8758057,38.7516113 39.8771021,36.2202479 38.3106978,34.6538436 L32.6568542,29 Z M27,53 C41.3594035,53 53,41.3594035 53,27 C53,12.6405965 41.3594035,1 27,1 C12.6405965,1 1,12.6405965 1,27 C1,41.3594035 12.6405965,53 27,53 Z"></path>
							</g>
						</g>
					</svg>
				</div>
				<div class="dz-progress-count"></span></div>
				<div class="dz-video-thumbnail"></span></div>
			</div>
		</div>
	<?php endif; ?>

</div>

<div id="whats-new-toolbar" class="<?php echo ( ! bp_is_active( 'media' ) ) ? esc_attr( 'media-off' ) : ''; ?> ">

	<?php if ( bp_is_active( 'media' ) ) : ?>
		<div class="post-elements-buttons-item show-toolbar" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>" data-bp-tooltip-hide="<?php esc_html_e( 'Hide formatting', 'buddyboss' ); ?>" data-bp-tooltip-show="<?php esc_html_e( 'Show formatting', 'buddyboss' ); ?>">
			<a href="#" id="show-toolbar-button" class="toolbar-button bp-tooltip">
				<span class="bb-icon bb-icon-text-format"></span>
			</a>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_media( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-media media-support">
			<a href="#" id="forums-media-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a photo', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-camera-small"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && ! empty( $video_extensions ) && bp_is_forums_video_support_enabled() ) :
		?>
        <div class="post-elements-buttons-item post-video video-support">
            <a href="#" id="forums-video-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a video', 'buddyboss' ); ?>">
                <i class="bb-icon bb-icon-video-alt"></i>
            </a>
        </div>
	    <?php
    endif;

	if ( bp_is_active( 'media' ) && ! empty( $extensions ) && bb_user_has_access_upload_document( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>

		<div class="post-elements-buttons-item post-media document-support">
			<a href="#" id="forums-document-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Attach a document', 'buddyboss' ); ?>">
				<i class="bb-icon bb-icon-attach"></i>
			</a>
		</div>

		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_gif( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-gif">
			<div class="gif-media-search">
				<a href="#" id="forums-gif-button" class="toolbar-button bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Post a GIF', 'buddyboss' ); ?>">
					<i class="bb-icon bb-icon-gif"></i>
				</a>
				<div class="gif-media-search-dropdown">
					<div class="gif-search-content">
						<div class="gif-search-query">
							<input type="search" placeholder="<?php esc_html_e( 'Search GIFs', 'buddyboss' ); ?>" class="search-query-input" />
							<span class="search-icon"></span>
						</div>
						<div class="gif-search-results" id="gif-search-results">
							<ul class="gif-search-results-list" >
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	endif;

	if ( bp_is_active( 'media' ) && bb_user_has_access_upload_emoji( $group_id, bp_loggedin_user_id(), $forum_id, 0, 'forum' ) ) :
		?>
		<div class="post-elements-buttons-item post-emoji bp-tooltip" data-bp-tooltip-pos="down-left" data-bp-tooltip="<?php esc_html_e( 'Insert an emoji', 'buddyboss' ); ?>"></div>
	<?php endif; ?>

</div>

<?php do_action( 'bbp_theme_after_forums_form_attachments' ); ?>
