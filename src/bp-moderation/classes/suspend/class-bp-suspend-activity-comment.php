<?php
/**
 * BuddyBoss Suspend Activity Comment Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Suspend
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss Suspend Activity comment.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Suspend_Activity_Comment extends BP_Suspend_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $type = 'activity_comment';

	/**
	 * BP_Suspend_Activity_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {
		parent::__construct();
		$this->item_type = self::$type;

		// Manage hidden list.
		add_action( "bp_suspend_hide_{$this->item_type}", array( $this, 'manage_hidden_activity_comment' ), 10, 3 );
		add_action( "bp_suspend_unhide_{$this->item_type}", array( $this, 'manage_unhidden_activity_comment' ), 10, 4 );

		// Add moderation data when actual activity added.
		add_action( 'bp_activity_after_save', array( $this, 'sync_moderation_data_on_save' ), 10, 1 );

		// Delete moderation data when actual activity deleted.
		add_action( 'bp_activity_after_delete', array( $this, 'sync_moderation_data_on_delete' ), 10, 1 );

		/**
		 * Suspend code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		add_filter( 'bp_activity_comments_search_join_sql', array( $this, 'update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_comments_search_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		add_filter( 'bb_activity_comments_count_get_join_sql', array( $this, 'bb_update_join_sql' ), 10, 2 );
		add_filter( 'bb_activity_comments_count_get_where_conditions', array( $this, 'bb_update_where_sql' ), 10, 2 );

		add_filter( 'bp_activity_comments_get_join_sql', array( $this, 'bb_update_join_sql' ), 10, 2 );
		add_filter( 'bp_activity_comments_get_where_conditions', array( $this, 'bb_update_where_sql' ), 10, 2 );

		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );
	}

	/**
	 * Get Blocked member's activity ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int    $member_id member id.
	 * @param string $action    Action name to perform.
	 * @param int    $page      Number of page.
	 *
	 * @return array
	 */
	public static function get_member_activity_comment_ids( $member_id, $action = '', $page = - 1 ) {
		$activities_ids = array();

		$args = array(
			'moderation_query' => false,
			'per_page'         => 0,
			'fields'           => 'ids',
			'show_hidden'      => true,
			'display_comments' => true,
			'filter'           => array(
				'user_id' => $member_id,
				'action'  => 'activity_comment',
			),
		);

		if ( $page > 0 ) {
			$args['per_page'] = self::$item_per_page;
			$args['page']     = $page;
		}

		$activities = BP_Activity_Activity::get( $args );

		if ( ! empty( $activities['activities'] ) ) {
			$activities_ids = $activities['activities'];
		}

		if ( 'hide' === $action && ! empty( $activities_ids ) ) {
			foreach ( $activities_ids as $k => $activity_id ) {
				if ( BP_Core_Suspend::check_suspended_content( $activity_id, self::$type, true ) ) {
					unset( $activities_ids[ $k ] );
				}
			}
		}

		return $activities_ids;
	}

	/**
	 * Prepare activity Join SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $join_sql Activity Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'a.id' );

		/**
		 * Filters the hidden activity Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_activity_comment_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare activity comment Where SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $where_conditions Activity Where sql.
	 * @param string $args             Search terms.
	 *
	 * @return mixed Where SQL
	 */
	public function update_where_sql( $where_conditions, $args = '' ) {

		$where = array();
		if ( function_exists( 'bb_did_filter' ) && ! bb_did_filter( 'bp_activity_comments_search_where_conditions' ) ) {
			$where['suspend_where'] = $this->exclude_where_query();
		}

		/**
		 * Filters the hidden activity comment Where SQL statement.
		 *
		 * @since BuddyBoss 1.5.6
		 *
		 * @since BuddyBoss 2.3.50
		 * Introduce new params $where_conditions and $search_term.
		 *
		 * @param array  $where            Query to hide suspended user's activity comment.
		 * @param array  $class            current class object.
		 * @param array  $where_conditions Where condition for activity comment search.
		 * @param string $search_term      Search term.
		 */
		$where = apply_filters( 'bp_suspend_activity_comment_get_where_conditions', $where, $this, $where_conditions, $args );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}

	/**
	 * Hide related content of activity comment
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $acomment_id   activity comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param array    $args          parent args.
	 */
	public function manage_hidden_activity_comment( $acomment_id, $hide_sitewide, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $acomment_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $acomment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'hide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::add_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $acomment_id;

		if ( empty( $args['disable_background'] ) ) {
			if ( $this->background_disabled ) {
				$this->hide_related_content( $acomment_id, $hide_sitewide, $args );
			} else {
				$bb_background_updater->data(
					array(
						'type'              => $this->item_type,
						'group'             => $group_name,
						'data_id'           => $acomment_id,
						'secondary_data_id' => $args['parent_id'],
						'callback'          => array( $this, 'hide_related_content' ),
						'args'              => array( $acomment_id, $hide_sitewide, $args ),
					),
				);
				$bb_background_updater->save()->schedule_event();
			}
		}
	}

	/**
	 * Un-hide related content of activity
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int      $acomment_id   activity comment id.
	 * @param int|null $hide_sitewide item hidden sitewide or user specific.
	 * @param int      $force_all     un-hide for all users.
	 * @param array    $args          parent args.
	 */
	public function manage_unhidden_activity_comment( $acomment_id, $hide_sitewide, $force_all, $args = array() ) {
		global $bb_background_updater;

		if ( empty( $acomment_id ) ) {
			return;
		}

		$suspend_args = bp_parse_args(
			$args,
			array(
				'item_id'   => $acomment_id,
				'item_type' => self::$type,
			)
		);

		if ( ! is_null( $hide_sitewide ) ) {
			$suspend_args['hide_sitewide'] = $hide_sitewide;
		}

		if (
			isset( $suspend_args['author_compare'] ) &&
			true === (bool) $suspend_args['author_compare'] &&
			isset( $suspend_args['type'] ) &&
			$suspend_args['type'] !== self::$type
		) {
			$ac_comment_user = BP_Moderation_Activity_Comment::get_content_owner_id( $acomment_id );
			if ( isset( $suspend_args['blocked_user'] ) && $ac_comment_user === $suspend_args['blocked_user'] ) {
				unset( $suspend_args['blocked_user'] );
			}
		}

		$suspend_args = self::validate_keys( $suspend_args );

		$group_name_args = array_merge(
			$suspend_args,
			array(
				'custom_action' => 'unhide',
			)
		);
		$group_name      = $this->bb_moderation_get_action_type( $group_name_args );

		BP_Core_Suspend::remove_suspend( $suspend_args );

		$args['parent_id'] = ! empty( $args['parent_id'] ) ? $args['parent_id'] : $this->item_type . '_' . $acomment_id;

		if ( empty( $args['disable_background'] ) ) {
			if ( $this->background_disabled ) {
				$this->unhide_related_content( $acomment_id, $hide_sitewide, $force_all, $args );
			} else {
				$bb_background_updater->data(
					array(
						'type'              => $this->item_type,
						'group'             => $group_name,
						'data_id'           => $acomment_id,
						'secondary_data_id' => $args['parent_id'],
						'callback'          => array( $this, 'unhide_related_content' ),
						'args'              => array( $acomment_id, $hide_sitewide, $force_all, $args ),
					),
				);
				$bb_background_updater->save()->schedule_event();
			}
		}
	}

	/**
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $template_names Template name.
	 *
	 * @return string
	 */
	public function locate_blocked_template( $template_names ) {
		global $activities_template;

		if ( 'activity/comment.php' !== $template_names ) {
			if ( ! is_array( $template_names ) || ! in_array( 'activity/comment.php', $template_names, true ) ) {
				return $template_names;
			}
		}

		if ( BP_Core_Suspend::check_suspended_content( $activities_template->activity->current_comment->id, self::$type, true ) ) {
			return 'activity/blocked-comment.php';
		}

		$author_id = BP_Moderation_Activity_Comment::get_content_owner_id( $activities_template->activity->current_comment->id );
		if ( bp_moderation_is_user_suspended( $author_id ) ) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}

	/**
	 * Get Activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int   $acomment_id activity comment id.
	 * @param array $args        parent args.
	 *
	 * @return array
	 */
	protected function get_related_contents( $acomment_id, $args = array() ) {
		$action       = ! empty( $args['action'] ) ? $args['action'] : '';
		$blocked_user = ! empty( $args['blocked_user'] ) ? $args['blocked_user'] : '';
		$page         = ! empty( $args['page'] ) ? $args['page'] : - 1;

		$related_contents = array();

		if ( $page <= 1 ) {
			// related activity comment only hide if parent activity hide or comment's/parent activity's author blocked or suspended.
			if ( ! empty( $args ) && ( isset( $args['blocked_user'] ) || isset( $args['user_suspended'] ) || isset( $args['hide_parent'] ) ) ) {
				$related_contents[ self::$type ] = self::get_activity_comment_ids( $acomment_id );
			}

			if ( bp_is_active( 'document' ) ) {
				$related_contents[ BP_Suspend_Document::$type ] = BP_Suspend_Document::get_document_ids_meta( $acomment_id, 'bp_activity_get_meta', $action );
			}

			if ( bp_is_active( 'media' ) ) {
				$related_contents[ BP_Suspend_Media::$type ] = BP_Suspend_Media::get_media_ids_meta( $acomment_id, 'bp_activity_get_meta', $action );
			}

			if ( bp_is_active( 'video' ) ) {
				$related_contents[ BP_Suspend_Video::$type ] = BP_Suspend_Video::get_video_ids_meta( $acomment_id, 'bp_activity_get_meta', $action );
			}
		}

		if (
			! empty( $args['parent_id'] ) &&
			self::$type . '_' . $acomment_id === $args['parent_id'] &&
			! empty( $args['action'] ) &&
			in_array( $args['action'], array( 'hide', 'unhide' ), true )
		) {
			$page           = $args['page'] ?? 1;
			$child_comments = self::fetch_all_child_activity( $acomment_id, $page );
			$document_ids   = array();
			$media_ids      = array();
			$video_ids      = array();

			if ( ! empty( $child_comments['comments'] ) ) {
				foreach ( $child_comments['comments'] as $child_comment ) {
					if ( 'activity_comment' === $child_comment->type ) {
						$related_contents[ self::$type ][] = $child_comment->id;
					} else {
						$related_contents[ BP_Suspend_Activity::$type ][] = $child_comment->id;
					}

					if ( bp_is_active( 'document' ) ) {
						$document_ids = array_merge( $document_ids, BP_Suspend_Document::get_document_ids_meta( $child_comment->id, 'bp_activity_get_meta', $action ) );
					}
					if ( bp_is_active( 'media' ) ) {
						$media_ids = array_merge( $media_ids, BP_Suspend_Media::get_media_ids_meta( $child_comment->id, 'bp_activity_get_meta', $action ) );
					}
					if ( bp_is_active( 'video' ) ) {
						$video_ids = array_merge( $video_ids, BP_Suspend_Video::get_video_ids_meta( $child_comment->id, 'bp_activity_get_meta', $action ) );
					}
				}

				$args['next_page'] = $child_comments['has_more'] ?? false;

				if ( ! empty( $related_contents[ BP_Suspend_Activity::$type ] ) ) {
					$related_contents[ BP_Suspend_Activity::$type ] = array_unique( $related_contents[ BP_Suspend_Activity::$type ] );
				}

				if ( ! empty( $related_contents[ self::$type ] ) ) {
					$related_contents[ self::$type ] = array_unique( $related_contents[ self::$type ] );
				}

				unset( $child_comments );
			}

			if ( bp_is_active( 'document' ) && ! empty( $document_ids ) ) {
				$related_contents[ BP_Suspend_Document::$type ] = array_unique( ( ! empty( $related_contents[ BP_Suspend_Document::$type ] ) ? array_merge( $related_contents[ BP_Suspend_Document::$type ], $document_ids ) : $document_ids ) );
				unset( $document_ids );
			}

			if ( bp_is_active( 'media' ) && ! empty( $media_ids ) ) {
				$related_contents[ BP_Suspend_Media::$type ] = array_unique( ( ! empty( $related_contents[ BP_Suspend_Media::$type ] ) ? array_merge( $related_contents[ BP_Suspend_Document::$type ], $media_ids ) : $media_ids ) );
				unset( $media_ids );
			}

			if ( bp_is_active( 'video' ) && ! empty( $video_ids ) ) {
				$related_contents[ BP_Suspend_Video::$type ] = array_unique( ( ! empty( $related_contents[ BP_Suspend_Video::$type ] ) ? array_merge( $related_contents[ BP_Suspend_Document::$type ], $video_ids ) : $video_ids ) );
				unset( $video_ids );
			}

			$hide_sitewide = $args['hide_sitewide'] ?? 0;

			$args['disable_background'] = true;

			if ( 'hide' === $args['action'] ) {
				$this->loop_hide_related_content( $related_contents, $acomment_id, $hide_sitewide, $args );
			} elseif ( 'unhide' === $args['action'] ) {
				$this->loop_unhide_related_content( $related_contents, $acomment_id, $hide_sitewide, 0, $args );
			}

			unset( $related_contents );
			$related_contents = array();
		}

		return $related_contents;
	}

	/**
	 * Get Blocked activity's comment ids
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $activity_id Activity id.
	 *
	 * @return array
	 */
	public static function get_activity_comment_ids( $activity_id ) {

		$comments_ids = array();

		$activity_comments = BP_Activity_Activity::get_child_comments( $activity_id );

		if ( ! empty( $activity_comments ) ) {
			$comments_ids = wp_list_pluck( $activity_comments, 'id' );
		}

		return $comments_ids;
	}

	/**
	 * Update the suspend table to add new entries.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param BP_Activity_Activity $activity Current instance of activity item being saved. Passed by reference.
	 */
	public function sync_moderation_data_on_save( $activity ) {

		if ( empty( $activity ) || empty( $activity->id ) ) {
			return;
		}

		if ( 'activity_comment' !== $activity->type ) {
			return;
		}

		$sub_items     = bp_moderation_get_sub_items( $activity->id, BP_Moderation_Activity::$moderation_type );
		$item_sub_id   = isset( $sub_items['id'] ) ? $sub_items['id'] : $activity->item_id;
		$item_sub_type = isset( $sub_items['type'] ) ? $sub_items['type'] : BP_Moderation_Activity::$moderation_type;

		$suspended_record = BP_Core_Suspend::get_recode( $item_sub_id, $item_sub_type );

		if ( empty( $suspended_record ) ) {
			$suspended_record = BP_Core_Suspend::get_recode( $activity->user_id, BP_Moderation_Members::$moderation_type );
		}

		if ( empty( $suspended_record ) || bp_moderation_is_content_hidden( $activity->id, self::$type ) ) {
			return;
		}

		self::handle_new_suspend_entry( $suspended_record, $activity->id, $activity->user_id );
	}

	/**
	 * Update the suspend table to delete an activity.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $activities Array of activities.
	 */
	public function sync_moderation_data_on_delete( $activities ) {

		if ( empty( $activities ) ) {
			return;
		}

		if ( is_array( $activities ) ) {
			foreach ( $activities as $activity ) {

				if ( 'activity_comment' !== $activity->type ) {
					continue;
				}

				/**
				 * Fires before activity suspend record delete.
				 *
				 * @since BuddyBoss 1.7.5
				 *
				 * @param object $activity_data activity data.
				 */

				do_action( 'bb_moderation_' . $this->item_type . '_before_delete_suspend', $activity );

				BP_Core_Suspend::delete_suspend( $activity->id, $this->item_type );
			}
		}
	}

	/**
	 * Prepare activity comment join SQL query to filter blocked Activity.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @param string $join_sql Activity Join sql.
	 * @param array  $args     Query arguments.
	 *
	 * @return string Join sql
	 */
	public function bb_update_join_sql( $join_sql, $args = array() ) {

		if ( isset( $args['moderation_query'] ) && false === $args['moderation_query'] ) {
			return $join_sql;
		}

		$join_sql .= $this->exclude_joint_query( 'a.id' );

		/**
		 * Filters the hidden activity comment count Where SQL statement.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param array $join_sql Join sql query
		 * @param array $class    current class object.
		 */
		$join_sql = apply_filters( 'bp_suspend_activity_comment_count_get_join', $join_sql, $this );

		return $join_sql;
	}

	/**
	 * Prepare activity comment count Where SQL query to filter blocked Activity
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @param array  $where_conditions Activity Where sql.
	 * @param string $args             Search terms.
	 *
	 * @return mixed Where SQL
	 */
	public function bb_update_where_sql( $where_conditions, $args = '' ) {

		$where                  = array();
		$where['suspend_where'] = $this->exclude_where_query();

		if ( is_string( $where_conditions ) ) {
			$where_conditions_explode = explode( 'WHERE ', $where_conditions );
			if ( isset( $where_conditions_explode[1] ) ) {
				$and_conditions_explode = explode( ' AND ', $where_conditions_explode[1] );
				$where_conditions       = $and_conditions_explode;
			}
		}

		/**
		 * Filters the hidden activity comment count Where SQL statement.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param array  $where            Query to hide suspended user's activity comment.
		 * @param array  $class            current class object.
		 * @param array  $where_conditions Where condition for activity comment search.
		 * @param string $search_term      Search term.
		 */
		$where = apply_filters( 'bb_suspend_activity_comment_count_get_where_conditions', $where, $this, $where_conditions, $args );

		if ( ! empty( array_filter( $where ) ) ) {
			$where_conditions['suspend_where'] = '( ' . implode( ' AND ', $where ) . ' )';
		}

		return $where_conditions;
	}
}
