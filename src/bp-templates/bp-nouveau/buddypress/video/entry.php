<?php
/**
 * BuddyBoss - Video Entry
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.5.7
 */

global $video_template;

$attachment_id = bp_get_video_attachment_id();
$download_url  = bp_video_download_link( $attachment_id, bp_get_video_id() );
$group_id      = bp_get_video_group_id();
$move_id       = '';
$move_type     = '';
$video_privacy = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
$can_manage    = true === (bool) $video_privacy['can_manage'];
$can_move      = true === (bool) $video_privacy['can_move'];

if ( $group_id > 0 ) {
	$move_id   = $group_id;
	$move_type = 'group';
} else {
	$move_id   = bp_get_video_user_id();
	$move_type = 'profile';
}

$is_comment_vid = bp_video_is_activity_comment_video( $video_template->video );

$attachment_urls = [];
$auto_generated_thumbnails = get_post_meta( bp_get_video_attachment_id(), 'video_preview_thumbnails', true );
$preview_thumbnail_id      = get_post_meta( bp_get_video_attachment_id(), 'bp_video_preview_thumbnail_id', true );
if ( $auto_generated_thumbnails ) {
	$auto_generated_thumbnails_arr = explode( ',', $auto_generated_thumbnails );
	if ( $auto_generated_thumbnails_arr ) {
		foreach ( $auto_generated_thumbnails_arr as $auto_generated_thumbnail ) {
			$attachment_urls['default_images'][] = array(
				'id'	=> $auto_generated_thumbnail,
				'url'	=> wp_get_attachment_image_url( $auto_generated_thumbnail, 'full' )
			);
		}
	}
	if ( $preview_thumbnail_id ) {
		$auto_generated_thumbnails_arr = explode( ',', $auto_generated_thumbnails );
		if ( ! in_array( $preview_thumbnail_id, $auto_generated_thumbnails_arr, true ) ) {
			$video                      = new BP_Video( bp_get_video_id() );
			$attachment_urls['preview'] = array(
				'id'            => bp_get_video_id(),
				'attachment_id' => $video->attachment_id,
				'thumb'         => wp_get_attachment_image_url( $preview_thumbnail_id, 'bp-media-thumbnail' ),
				'url'           => wp_get_attachment_image_url( $preview_thumbnail_id, 'full' ),
				'name'          => $video->title,
				'saved'         => true,
				'dropzone'      => true
			);
		} else {
			if ( $preview_thumbnail_id ) {
				$attachment_urls['preview'] = [
					'id'	=> $preview_thumbnail_id,
					'url'	=> wp_get_attachment_image_url( $preview_thumbnail_id, 'full' )
				];
			}
		}
	}
}

?>
<li class="lg-grid-1-5 md-grid-1-3 sm-grid-1-3" data-id="<?php bp_video_id(); ?>" data-date-created="<?php bp_video_date_created(); ?>">

	<div class="bb-video-thumb bb-item-thumb">
		<div class="video-action-wrap item-action-wrap">
			<?php
			$report_btn = bp_video_get_report_link( array( 'id' => bp_get_video_id() ) );
			if ( $can_manage || $report_btn ) {
				?>
				<a href="#" class="video-action_more item-action_more" data-balloon-pos="up" data-balloon="<?php esc_html_e( 'More actions', 'buddyboss' ); ?>">
					<i class="bb-icon-menu-dots-v"></i>
				</a>
				<div class="video-action_list item-action_list">
					<ul>
						<li class="edit_thumbnail_video">
							<a href="#" data-action="video" data-video-attachments="<?php echo esc_html(json_encode( $attachment_urls )); ?>" data-video-attachment-id="<?php bp_video_attachment_id(); ?>" data-video-id="<?php bp_video_id(); ?>" class="ac-video-thumbnail-edit"><?php esc_html_e( 'Add Thumbnail', 'buddyboss' ); ?></a>
						</li>
						<?php
						if ( $can_manage ) {
							if ( $is_comment_vid ) {
								?>
								<li class="move_video move-disabled" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Video inherits activity privacy in comment. You are not allowed to move.', 'buddyboss' ); ?>">
									<a href="#"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
								</li>
								<?php
							} else {
								if ( $can_move ) {
									?>
									<li class="move_video">
										<a href="#" data-action="video" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-type="<?php echo esc_attr( $move_type ); ?>" id="<?php echo esc_attr( $move_id ); ?>" class="ac-video-move"><?php esc_html_e( 'Move', 'buddyboss' ); ?></a>
									</li>
									<?php
								}
							}
						}
						?>

						<?php
						if ( $report_btn ) {
							?>
							<li class="report_file">
								<?php echo $report_btn; // phpcs:ignore ?>
							</li>
							<?php
						}
						?>

						<?php if ( $can_manage ) { ?>
							<li class="delete_file">
								<a class="video-file-delete" data-video-id="<?php bp_video_id(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-item-activity-id="<?php bp_video_activity_id(); ?>" data-item-from="video" data-item-id="<?php bp_video_id(); ?>" data-type="video" href="#"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
							</li>
						<?php } ?>

					</ul>
				</div>
			<?php } ?>
		</div>
		<?php if ( ! empty( bp_get_video_length() ) ) { ?>
		<p class="bb-video-duration"><?php bp_video_length(); ?></p>
		<?php } ?>
		<a class="bb-open-video-theatre bb-video-cover-wrap bb-item-cover-wrap" data-id="<?php bp_video_id(); ?>" data-attachment-full="<?php bp_video_attachment_image(); ?>" data-activity-id="<?php bp_video_activity_id(); ?>" data-privacy="<?php bp_video_privacy(); ?>" data-parent-activity-id="<?php bp_video_parent_activity_id(); ?>" data-album-id="<?php bp_video_album_id(); ?>" data-group-id="<?php bp_video_group_id(); ?>" data-attachment-id="<?php bp_video_attachment_id(); ?>" href="#">
			<img src="<?php echo esc_url( buddypress()->plugin_url ); ?>bp-templates/bp-nouveau/images/video-placeholder.jpg" data-src="<?php bp_video_attachment_image_thumbnail(); ?>" alt="<?php bp_video_title(); ?>" class="lazy"/>
		</a>
		<?php
		$video_privacy = bp_video_user_can_manage_video( bp_get_video_id(), bp_loggedin_user_id() );
		$can_manage    = true === (bool) $video_privacy['can_manage'];
		if ( ( ( bp_is_my_profile() || bp_current_user_can( 'bp_moderate' ) ) || ( bp_is_group() && ( ( bp_is_group_video() && $can_manage ) || ( bp_is_group_albums() && $can_manage ) ) ) ) && ! bp_is_video_directory() ) :
			?>
			<div class="bb-video-check-wrap bb-action-check-wrap">
				<input id="bb-video-<?php bp_video_id(); ?>" class="bb-custom-check" type="checkbox" value="<?php bp_video_id(); ?>" name="bb-video-select" />
				<label class="bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_html_e( 'Select', 'buddyboss' ); ?>" for="bb-video-<?php bp_video_id(); ?>"><span class="bb-icon bb-icon-check"></span></label>
			</div>
		<?php endif; ?>
	</div>

</li>
