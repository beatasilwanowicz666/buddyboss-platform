<?php
/**
 * BuddyBoss Connections Activity Functions.
 *
 * These functions handle the recording, deleting and formatting of activity
 * for the user and for this specific component.
 *
 * @package BuddyBoss\Connections\Notifications
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Notification formatting callback for bp-friends notifications.
 *
 * @since BuddyPress 1.0.0
 *
 * @param string $action            The kind of notification being rendered.
 * @param int    $item_id           The primary item ID.
 * @param int    $secondary_item_id The secondary item ID.
 * @param int    $total_items       The total number of messaging-related notifications
 *                                  waiting for the user.
 * @param string $format            'string' for BuddyBar-compatible notifications;
 *                                  'array' for WP Toolbar. Default: 'string'.
 * @return array|string
 */
function friends_format_notifications( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {

	switch ( $action ) {
		case 'friendship_accepted':
			$link = trailingslashit( bp_loggedin_user_domain() . bp_get_friends_slug() . '/my-friends' );

			// $action and $amount are used to generate dynamic filter names.
			$action = 'accepted';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( '%d members accepted your connection requests', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text   = sprintf( __( '%s accepted your request to connect', 'buddyboss' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;

		case 'friendship_request':
			$link = bp_loggedin_user_domain() . bp_get_friends_slug() . '/requests/?new';

			$action = 'request';

			// Set up the string and the filter.
			if ( (int) $total_items > 1 ) {
				$text   = sprintf( __( 'You have %d pending requests to connect', 'buddyboss' ), (int) $total_items );
				$amount = 'multiple';
			} else {
				$text   = sprintf( __( '%s sent you an invitation to connect', 'buddyboss' ), bp_core_get_user_displayname( $item_id ) );
				$amount = 'single';
			}

			break;
	}

	// Return either an HTML link or an array, depending on the requested format.
	if ( 'string' == $format ) {

		/**
		 * Filters the format of friendship notifications based on type and amount * of notifications pending.
		 *
		 * This is a variable filter that has four possible versions.
		 * The four possible versions are:
		 *   - bp_friends_single_friendship_accepted_notification
		 *   - bp_friends_multiple_friendship_accepted_notification
		 *   - bp_friends_single_friendship_request_notification
		 *   - bp_friends_multiple_friendship_request_notification
		 *
		 * @since BuddyPress 1.0.0
		 *
		 * @param string|array $value       Depending on format, an HTML link to new requests profile
		 *                                  tab or array with link and text.
		 * @param int          $total_items The total number of messaging-related notifications
		 *                                  waiting for the user.
		 * @param int          $item_id     The primary item ID.
		 */
		$return = apply_filters( 'bp_friends_' . $amount . '_friendship_' . $action . '_notification', '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>', (int) $total_items, $item_id );
	} else {
		/** This filter is documented in bp-friends/bp-friends-notifications.php */
		$return = apply_filters(
			'bp_friends_' . $amount . '_friendship_' . $action . '_notification',
			array(
				'link' => $link,
				'text' => $text,
			),
			(int) $total_items,
			$item_id
		);
	}

	/**
	 * Fires at the end of the bp-friends notification format callback.
	 *
	 * @since BuddyPress 1.0.0
	 *
	 * @param string       $action            The kind of notification being rendered.
	 * @param int          $item_id           The primary item ID.
	 * @param int          $secondary_item_id The secondary item ID.
	 * @param int          $total_items       The total number of messaging-related notifications
	 *                                        waiting for the user.
	 * @param array|string $return            Notification text string or array of link and text.
	 */
	do_action( 'friends_format_notifications', $action, $item_id, $secondary_item_id, $total_items, $return );

	return $return;
}

/**
 * Clear friend-related notifications when ?new=1
 *
 * @since BuddyPress 1.2.0
 */
function friends_clear_friend_notifications() {
	if ( isset( $_GET['new'] ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
	}
}
add_action( 'bp_activity_screen_my_activity', 'friends_clear_friend_notifications' );

/**
 * Delete any friendship request notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bp_friends_mark_friendship_request_notifications_by_type() {
	if ( isset( $_GET['new'] ) ) {
		bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_request' );
	}
}
add_action( 'friends_screen_requests', 'bp_friends_mark_friendship_request_notifications_by_type' );

/**
 * Delete any friendship acceptance notifications for the logged in user.
 *
 * @since BuddyPress 1.9.0
 */
function bp_friends_mark_friendship_accepted_notifications_by_type() {
	bp_notifications_mark_notifications_by_type( bp_loggedin_user_id(), buddypress()->friends->id, 'friendship_accepted' );
}
add_action( 'friends_screen_my_friends', 'bp_friends_mark_friendship_accepted_notifications_by_type' );

/**
 * Notify one use that another user has requested their virtual friendship.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_friendship_requested_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	bp_notifications_add_notification(
		array(
			'user_id'           => $friend_user_id,
			'item_id'           => $initiator_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_request',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_requested', 'bp_friends_friendship_requested_notification', 10, 3 );

/**
 * Remove friend request notice when a member rejects another members
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship object.
 */
function bp_friends_mark_friendship_rejected_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_mark_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_friendship_rejected', 'bp_friends_mark_friendship_rejected_notifications_by_item_id', 10, 2 );

/**
 * Notify a member when another member accepts their virtual friendship request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $friendship_id     The unique ID of the friendship.
 * @param int $initiator_user_id The friendship initiator user ID.
 * @param int $friend_user_id    The friendship request receiver user ID.
 */
function bp_friends_add_friendship_accepted_notification( $friendship_id, $initiator_user_id, $friend_user_id ) {
	// Remove the friend request notice.
	bp_notifications_mark_notifications_by_item_id( $friend_user_id, $initiator_user_id, buddypress()->friends->id, 'friendship_request' );

	// Add a friend accepted notice for the initiating user.
	bp_notifications_add_notification(
		array(
			'user_id'           => $initiator_user_id,
			'item_id'           => $friend_user_id,
			'secondary_item_id' => $friendship_id,
			'component_name'    => buddypress()->friends->id,
			'component_action'  => 'friendship_accepted',
			'date_notified'     => bp_core_current_time(),
			'is_new'            => 1,
		)
	);
}
add_action( 'friends_friendship_accepted', 'bp_friends_add_friendship_accepted_notification', 10, 3 );

/**
 * Remove friend request notice when a member withdraws their friend request.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int    $friendship_id Friendship ID (not used).
 * @param object $friendship    Friendship Object.
 */
function bp_friends_mark_friendship_withdrawn_notifications_by_item_id( $friendship_id, $friendship ) {
	bp_notifications_delete_notifications_by_item_id( $friendship->friend_user_id, $friendship->initiator_user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_friendship_withdrawn', 'bp_friends_mark_friendship_withdrawn_notifications_by_item_id', 10, 2 );

/**
 * Remove connection requests FROM user, used primarily when a user is deleted.
 *
 * @since BuddyPress 1.9.0
 *
 * @param int $user_id ID of the user whose notifications are removed.
 */
function bp_friends_remove_notifications_data( $user_id = 0 ) {
	bp_notifications_delete_notifications_from_user( $user_id, buddypress()->friends->id, 'friendship_request' );
}
add_action( 'friends_remove_data', 'bp_friends_remove_notifications_data', 10, 1 );

/**
 * Add Connections-related settings to the Settings > Notifications page.
 *
 * @since BuddyPress 1.0.0
 */
function friends_screen_notification_settings() {

	$send_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_request', true );
	if ( ! $send_requests ) {
		$send_requests = 'yes';
	}

	$accept_requests = bp_get_user_meta( bp_displayed_user_id(), 'notification_friends_friendship_accepted', true );
	if ( ! $accept_requests ) {
		$accept_requests = 'yes';
	} ?>

	<table class="notification-settings" id="friends-notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php esc_html_e( 'Connections', 'buddyboss' ); ?></th>
				<th class="yes"><?php esc_html_e( 'Yes', 'buddyboss' ); ?></th>
				<th class="no"><?php esc_html_e( 'No', 'buddyboss' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<tr id="friends-notification-settings-request">
				<td></td>
				<td><?php esc_html_e( 'A member invites you to connect', 'buddyboss' ); ?></td>
				<td class="yes">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-yes" class="bs-styled-radio" value="yes" <?php checked( $send_requests, 'yes', true ); ?> />
						<label for="notification-friends-friendship-request-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
				<td class="no">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_friends_friendship_request]" id="notification-friends-friendship-request-no" class="bs-styled-radio" value="no" <?php checked( $send_requests, 'no', true ); ?> />
						<label for="notification-friends-friendship-request-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
			</tr>
			<tr id="friends-notification-settings-accepted">
				<td></td>
				<td><?php esc_html_e( 'A member accepts your connection request', 'buddyboss' ); ?></td>
				<td class="yes">
					<div class="bp-radio-wrap">
						<input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-yes" class="bs-styled-radio" value="yes" <?php checked( $accept_requests, 'yes', true ); ?> />
						<label for="notification-friends-friendship-accepted-yes"><span class="bp-screen-reader-text"><?php esc_html_e( 'Yes, send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
				<td class="no">
					<div class="bp-radio-wrap">
					<input type="radio" name="notifications[notification_friends_friendship_accepted]" id="notification-friends-friendship-accepted-no" class="bs-styled-radio" value="no" <?php checked( $accept_requests, 'no', true ); ?> />
					<label for="notification-friends-friendship-accepted-no"><span class="bp-screen-reader-text"><?php esc_html_e( 'No, do not send email', 'buddyboss' ); ?></span></label>
					</div>
				</td>
			</tr>

			<?php

			/**
			 * Fires after the last table row on the friends notification screen.
			 *
			 * @since BuddyPress 1.0.0
			 */
			do_action( 'friends_screen_notification_settings' );
			?>

		</tbody>
	</table>

	<?php
}

add_action( 'bp_notification_settings', 'friends_screen_notification_settings', 15 );

/**
 * Add Notifications for the friends.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $array Array of notifications.
 *
 * @return mixed
 */
function bb_friends_register_notifications( $array ) {
	$friends_notification = array(
		'label'  => esc_html__( 'Connections', 'buddyboss' ),
		'fields' => array(
			array(
				'key'         => 'notification_friends_friendship_request',
				'admin_label' => esc_html__( 'A member invites you to connect', 'buddyboss' ),
				'label'       => esc_html__( 'A member invites you to connect', 'buddyboss' ),
				'default'     => 'yes',
				'options'     => array(
					array(
						'name'  => esc_html__( 'Yes, send email', 'buddyboss' ),
						'value' => 'yes',
					),
					array(
						'name'  => esc_html__( 'No, do not send email', 'buddyboss' ),
						'value' => 'no',
					),
				),
			),
			array(
				'key'         => 'notification_friends_friendship_accepted',
				'admin_label' => esc_html__( 'A member accepts your connection request', 'buddyboss' ),
				'label'       => esc_html__( 'A member accepts your connection request', 'buddyboss' ),
				'default'     => 'yes',
				'options'     => array(
					array(
						'name'  => esc_html__( 'Yes, send email', 'buddyboss' ),
						'value' => 'yes',
					),
					array(
						'name'  => esc_html__( 'No, do not send email', 'buddyboss' ),
						'value' => 'no',
					),
				),
			),
		),
	);

	$array['friends'] = $friends_notification;

	return $array;
}

add_filter( 'bb_register_notifications_by_group', 'bb_friends_register_notifications', 14, 1 );
