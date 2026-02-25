<?php

/**
 * Fired during plugin deactivation.
 *
 * @link    https://plugins360.com
 * @since   1.0.0
 *
 * @package All_In_One_Video_Gallery
 */

// Exit if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * AIOVG_Deactivator class.
 *
 * @since 1.0.0
 */
class AIOVG_Deactivator {

	/**
	 * Called when the plugin is deactivated.
	 *
	 * @since 1.0.0
	 */
	public static function deactivate() {	
		if ( wp_next_scheduled( 'aiovg_hourly_scheduled_events' ) ) {
			wp_clear_scheduled_hook( 'aiovg_hourly_scheduled_events' );
		}

		if ( wp_next_scheduled( 'aiovg_schedule_every_five_minutes' ) ) {
			wp_clear_scheduled_hook( 'aiovg_schedule_every_five_minutes' );
		}
	}

}
