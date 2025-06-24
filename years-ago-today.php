<?php
/**
 * Plugin Name: Years Ago Today
 * Version:     1.6
 * Plugin URI:  https://coffee2code.com/wp-plugins/years-ago-today/
 * Author:      Scott Reilly
 * Author URI:  https://coffee2code.com/
 * Text Domain: years-ago-today
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Admin dashboard widget (and optional daily email) that lists posts published to your site on this day in years past.
 *
 * Compatible with WordPress 5.5 through 6.8+, and PHP through at least 8.3+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/years-ago-today/
 *
 * @package Years_Ago_Today
 * @author  Scott Reilly
 * @version 1.6
 */

/*
	Copyright (c) 2015-2025 by Scott Reilly (aka coffee2code)

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

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_YearsAgoToday' ) ) :

class c2c_YearsAgoToday {

	/**
	 * Meta key name for flag to indicate if user has opted into being notified of
	 * posts published on that day in years past.
	 *
	 * @var string
	 * @access public
	 */
	public static $option_name = 'c2c_years_ago_today_daily_email_optin';

	/**
	 * Name for the cron task to send out the daily email.
	 *
	 * @var string
	 * @access public
	 */
	public static $cron_name = 'c2c_years_ago_daily_cron';

	/**
	 * Name for the cache group for caching plugin values.
	 *
	 * @var string
	 * @access public
	 */
	public static $cache_group = 'c2c_years_ago_today';

	/**
	 * Default value for the meta value to indicate the user wants the daily email
	 * of posts published on that day in years past.
	 *
	 * @var string
	 * @access public
	 */
	public static $enabled_option_value = '1';

	/**
	 * Prevents instantiation.
	 *
	 * @since 1.2
	 */
	private function __construct() {}

	/**
	 * Prevents unserializing an instance.
	 *
	 * @since 1.2
	 * @since 1.5 Changed to public.
	 */
	public function __wakeup() {}

	/**
	 * Returns version of the plugin.
	 *
	 * @since 1.0
	 */
	public static function version() {
		return '1.6';
	}

	/**
	 * Hooks actions and filters.
	 *
	 * @since 1.0
	 */
	public static function init() {
		if ( is_multisite() ) {
			wp_cache_add_global_groups( self::$cache_group );
		}

		/* Register hooks. */

		// Register dashboard widget.
		add_action( 'wp_dashboard_setup',       array( __CLASS__, 'dashboard_setup' ) );

		// Adds the checkbox to user profiles.
		add_action( 'personal_options',         array( __CLASS__, 'add_daily_email_optin_checkbox' ) );

		// Saves the user preference for daily emails.
		add_action( 'personal_options_update',  array( __CLASS__, 'option_save' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'option_save' ) );

		// Enqueue CSS only when the main Dashboard loads.
		add_action( 'admin_enqueue_scripts',    array( __CLASS__, 'enqueue_admin_style' ) );

		// Maybe clear transients when a post gets published.
		add_action( 'save_post',                array( __CLASS__, 'clear_transient_on_publish' ), 10, 2 );
	}

	/**
	 * Initializes things necessary for the cron job.
	 *
	 * @since 1.5
	 */
	public static function cron_init() {
		// Register cron task.
		add_action( self::$cron_name, array( __CLASS__, 'cron_email' ) );
	}

	/**
	 * Handles activation tasks.
	 *
	 * @since 1.0
	 */
	public static function activate() {
		// Bail if cron task is already scheduled.
		if ( wp_next_scheduled( self::$cron_name ) ) {
			return;
		}

		$default_time_string = '9:00 am';

		/**
		 * Filters the time of the day that the daily Years Ago Today email is sent.
		 *
		 * @since 2.0
		 *
		 * @param string $time The time of day to email the Years Ago Today email to
		 *                     those who have opted-in to it. Default "9:00 am".
		 */
		$time_string = apply_filters( 'c2c_years_ago_today-email_cron_time', $default_time_string );

		// Get the site’s TZ.
		$tz = wp_timezone();

		// Parse the time in that TZ.
		$dt = date_create_immutable( $time_string, $tz );

		// Use default if the string couldn’t be parsed for some reason.
		if ( false === $dt ) {
			$dt = new DateTimeImmutable( $default_time_string, $tz );
		}

		// If the desired send time has already passed today, schedule for tomorrow.
		if ( $dt->getTimestamp() <= time() ) {
			$dt = $dt->modify( '+1 day' );
		}

		wp_schedule_event( $dt->getTimestamp(), 'daily', self::$cron_name );
	}

	/**
	 * Handles deactivation tasks.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( self::$cron_name );

		wp_cache_delete( 'first_published_year', self::$cache_group );
	}

	/**
	 * Clears post-related transients when a post gets published.
	 *
	 * @since 2.0
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	public static function clear_transient_on_publish( $post_id, $post ) {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$today_md = wp_date( 'm-d' );
		$post_md  = wp_date( 'm-d', strtotime( $post->post_date ) );

		$current_year = (int) wp_date( 'Y' );
		$post_year    = (int) wp_date( 'Y', strtotime( $post->post_date ) );

		if ( $post_md === $today_md && $post_year < $current_year ) {
			delete_transient( self::get_post_ids_cache_key() );
		}
	}

	/**
	 * Returns list of all users who have opted into receiving daily email.
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_users_to_email() {
		global $wpdb;

		$query = new WP_User_Query( array(
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'   => $wpdb->get_blog_prefix() . self::$option_name,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value' => self::$enabled_option_value,
		) );

		return $query->results;
	}

	/**
	 * Formats a timestamp according to the date format string to be used when
	 * referring to the given day.
	 *
	 * @since 1.3.0
	 *
	 * @param string $time The timestamp to be formatted. Default is the current
	 *                     time's timestamp.
	 * @return string      The timestamp formatted according to the date format
	 *                     string, which by default is "M jS".
	 */
	public static function get_formatted_date_string( $timestamp = '' ) {
		if ( ! $timestamp ) {
			$timestamp = current_time( 'timestamp' );
		}

		/* translators: date string for today */
		return date_i18n( __( 'M jS', 'years-ago-today' ), $timestamp );
	}

	/**
	 * Returns the body of the daily email.
	 *
	 * @since 1.2
	 *
	 * @return array
	 */
	public static function get_email_body() {
		// Get the list of posts from years ago.
		$query = self::get_posts();

		$site_name = wp_specialchars_decode( get_option('blogname'), ENT_QUOTES );

		$html_body = '<html><head><title>' . self::get_email_subject() . '</title></head><body>';

		// If there are no posts to include in the email.
		if ( ! $query->have_posts() ) {
			/**
			 * Filters if the daily Years Ago Today email is sent out on days that don't
			 * have any published posts in prior years.
			 *
			 * @since 1.0.0
			 *
			 * @param string $send Send daily email if there are no posts? Default false.
			 */
			$send_email_when_no_posts = (bool) apply_filters( 'c2c_years_ago_today-email-if-no-posts', false );

			// Define an email body if sending email despite not having posts.
			if ( $send_email_when_no_posts ) {
				$body = sprintf(
					/**
					 * Filters the email body for daily Years Ago Today email when no posts
					 * have been published in prior years.
					 *
					 * @since 1.0.0
					 *
					 * @param string $email_body The body of the email. Use "%1$s" as a
					 *                           placeholder for site name and "%2$s" for date.
					 *                           Default 'No posts were published to the site
					 *                           %1$s on %2$s in any past year.'.
					 */
					apply_filters(
						'c2c_years_ago_today-email-body-no-posts',
						/* translators: 1: name of the site, 2: date string for today */
						__( 'No posts were published to the site %1$s on %2$s in any past year.', 'years-ago-today' )
					),
					$site_name,
					self::get_formatted_date_string()
				);
				$html_body .= '<p>' . $body . '</p>';
			}
			// Else don't define an email body.
			else {
				$body = $html_body = '';
			}
		}
		// Else there are posts to include in the email.
		else {
			// Build out email body.
			$body = sprintf(
				/* translators: 1: number of posts, 2: site name, 3: date string for today */
				_n(
					'%1$d post has been published to the site %2$s on %3$s in a previous year:',
					'%1$d posts have been published to the site %2$s on %3$s in previous years:',
					$query->post_count,
					'years-ago-today'
				),
				$query->post_count,
				$site_name,
				self::get_formatted_date_string()
			);

			$html_body .= '<p>' . esc_html( $body ) . '</p>';

			$year = '';
			while ( $query->have_posts() ) :
				$query->the_post();
				$this_year = get_the_date( 'Y' );
				// Only output the year once.
				if ( $year !== $this_year ) {
					$year = $this_year;
					/* translators: %s: 4-digit year. */
					$body .= "\n\n" . sprintf( __( '== %s ==', 'years-ago-today' ), (int) $year ) . "\n";
					$html_body .= '<h2>' . $year . '</h2>';
				}
				$body .= '* ' . wp_strip_all_tags( get_the_title() ) .  ' : ' . esc_url( get_permalink() ) . "\n";
				$html_body .= '<h3><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h3>';
				$html_body .= wp_kses_post( self::get_resized_content() );
			endwhile;
		}

		if ( $html_body ) {
			$html_body .= '</body></html>';
		}

		return array(
			'text' => $body,
			'html' => $html_body,
		);

	}

	/**
	 * Replaces image file references with reduced-sized versions.
	 *
	 * @since 2.0
	 *
	 * @param string $size The size for resized images.
	 * @return string The post content with images resized.
	 */
	private static function get_resized_content( $size = 'medium' ) {
		$content = get_the_content();

		// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage -- This is just a regex used to find images in the post.
		$pattern = '/<img (.*?)src=["\'](.*?)["\'](.*?)>/i';

		$callback = function( $matches ) use ( $size ) {
			$full_image_url = $matches[2];

			$attachment_id = attachment_url_to_postid( $full_image_url );

			if ( $attachment_id ) {
				$image_src = wp_get_attachment_image_src( $attachment_id, $size );

				if ( $image_src ) {
					$attributes = $matches[3];
					$attributes = str_replace( 'size-full', 'size-' . $size, $attributes );
					$attributes = preg_replace( '/width=".*?"/', 'width="' . $image_src[1] . '"', $attributes );
					$attributes = preg_replace( '/height=".*?"/', 'height="' . $image_src[2] . '"', $attributes );

					// Add 'alt' attribute if not present.
					if ( ! preg_match( '/\balt\s*=/', $matches[0] ) ) {
						$alt = trim( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
						$attributes .= ' alt="' . esc_attr( $alt ) . '"';
					}

					return '<img ' . $matches[1] . 'src="' . esc_url($image_src[0]) . '" ' . $attributes . '>';
				}
			}

			return $matches[0];
		};

		$new_content = preg_replace_callback( $pattern, $callback, $content );

		return $new_content;
	}


	/**
	 * Returns the subject line for the daily email.
	 *
	 * @since 1.2
	 *
	 * @return string
	 */
	public static function get_email_subject() {
		return sprintf(
			/* translators: %s: site name in subject for daily email */
			__( '[%s] Years Ago Today daily update', 'years-ago-today' ),
			wp_specialchars_decode( get_option('blogname'), ENT_QUOTES )
		);
	}

	/**
	 * Amends a user-specific footer to an email body.
	 *
	 * Adds an explanation about the email to the recipient. Serves to remind
	 * the user why they are receiving the email, what it is about, and how to
	 * stop it.
	 *
	 * @since 1.2
	 * @since 2.0 Remove `$user_id` and `$html_body` arguments and simply return the footer.
	 *
	 * @param string $type The format for the footer. Either 'text' or 'html'. Default 'text'.
	 * @return string
	 */
	public static function get_email_footer( $format = 'text' ) {
		if ( 'html' !== $format ) {
			$format = 'text';
		}

		$footer = '';

		$is_html = ( 'html' === $format );

		$footer .= $is_html
			? "<br>\n<br>\n<hr>\n<p>"
			: "\n\n\n-------------------------------\n";;

		$footer .= sprintf(
			/* translators: %s: site name */
			__( 'You received this email because you have opted into receiving a daily email about posts published on this day in years past on the site %s, which is using the Years Ago Today plugin.', 'years-ago-today' ),
			wp_specialchars_decode( get_option('blogname'), ENT_QUOTES )
		);

		$footer .= $is_html
			? "</p>\n<p>"
			: "\n\n";

		$footer .= sprintf(
			/* translators: %s: URL to user profile on the site */
			__( 'If you wish to discontinue receiving these emails, simply log into the site and visit your profile at %s to uncheck the checkbox labeled "Email me daily about posts published on this day in years past."', 'years-ago-today' ),
			admin_url( 'profile.php' )
		);

		$footer .= $is_html
			? "</p>\n"
			: "\n";

		return $footer;
	}

	/**
	 * Sends out daily email.
	 *
	 * @since 1.0
	 * @todo  Handle large volume of users better, perhaps via chunked BCCs.
	 */
	public static function cron_email() {
		// Get list of users who want the daily email.
		$users = self::get_users_to_email();

		// If no one wants the email, there's nothing else to do.
		if ( ! $users ) {
			return;
		}

		// Get the content of the email.
		$subject = self::get_email_subject();
		$body    = self::get_email_body();
		$headers = array();

		// If no subject or body for the email, then there's nothing else to do.
		if ( ! $subject || ! $body['text'] ) {
			return;
		}

		if ( is_array( $body ) ) {
			if ( isset( $body['html'] ) ) {
				if ( isset( $body['text'] ) ) {
					$plain_text = $body['text'];
				} else {
					$plain_text = wp_strip_all_tags( $body['html'] );
				}

				$headers[]    = 'Content-type: text/html';
				$alt_function = function ( $mailer ) use ( $plain_text ) {
					$mailer->{'AltBody'} = $plain_text . self::get_email_footer( 'text' );
				};
				add_action(
					'phpmailer_init',
					$alt_function
				);

				$body = $body['html'] . self::get_email_footer( 'html' );
			} elseif ( isset( $body['text'] ) ) {
				$body = $body['text'] . self::get_email_footer( 'text' );
			}
		}

		// Send email to each user.
		foreach ( $users as $user ) {
			if ( $user->user_email ) {
				wp_mail( $user->user_email, $subject, $body, $headers );
			}
		}
	}

	/**
	 * Set up the admin dashboard.
	 *
	 * @since 1.0
	 */
	public static function dashboard_setup() {
		wp_add_dashboard_widget(
			'dashboard_years_ago_today',
			_x( 'Years Ago Today', 'Title of the dashboard widget', 'years-ago-today' ),
			array( __CLASS__, 'wp_dashboard_years_ago_today' )
		);
	}

	/**
	 * Outputs the admin dashboard.
	 *
	 * @since 1.0
	 */
	public static function wp_dashboard_years_ago_today() {
		$q = self::get_posts();

		echo '<div class="years-ago-today-widget">';

		// Output and return if no posts were published.
		if ( ! $q->have_posts() ) {
			printf(
				'<p>%s</p>',
				wp_kses(
					sprintf(
						/* translators: %s: date string for today */
						__( 'No posts were published on <strong>%s</strong> from any past year.', 'years-ago-today' ),
						esc_html( self::get_formatted_date_string() )
					),
					array( 'strong' => array() )
				)
			);
			echo '</div>';
			return;
		}

		// Print summary.
		printf(
			'<p>%s</p>',
			wp_kses(
				sprintf(
					/* translators: 1: site name, 2: date string for today */
					_n(
						'<strong>%1$d</strong> post has been published on <strong>%2$s</strong> in a previous year:',
						'<strong>%1$d</strong> posts have been published on <strong>%2$s</strong> in previous years:',
						(int) $q->post_count,
						'years-ago-today'
					),
					(int) $q->post_count,
					esc_html( self::get_formatted_date_string() )
				),
				array( 'strong' => array() )
			)
		);

		// Group posts by year.
		$year = '';
		$open = false;

		while ( $q->have_posts() ) :
			$q->the_post();
			$this_year = get_the_date( 'Y' );

			if ( $this_year !== $year ) {
				if ( $open ) {
					echo "</ul></section>\n";
				}

				$year = $this_year;

				printf(
					'<section class="years-ago-today-group" aria-labelledby="years-ago-today-year-%1$s">' .
					'<h3 id="years-ago-today-year-%1$s" class="years-ago-today-year">%1$s</h3>' .
					'<ul class="years-ago-today-posts">',
					esc_attr( $year )
				);

				$open = true;
			}

			echo '<li>';
			the_title( '<a href="' . esc_url( get_permalink() ) . '">', '</a>' );
			echo "</li>\n";

		endwhile;

		if ( $open ) {
			echo "</ul></section>\n";
		}

		echo "</div>\n";
	}

	/**
	 * Gets the year of the first published post on the site.
	 *
	 * @return string
	 */
	public static function get_first_published_year() {
		global $wpdb;

		/**
		 * Filters the year of the earliest published post.
		 *
		 * By default this is false, which causes the plugin to determine the earliest
		 * year via a database query. The queried value does get cached, though may
		 * not persist depending on your site setup. This filter can be used to
		 * prevent the need for the query or to set a year later than the earliest
		 * published year (in case you'd prefer not to feature or be reminded of the
		 * early years).
		 *
		 * @since 1.0.0
		 *
		 * @param string|false The year for the earlier published post. A value of
		 *                     `false` forces the actual value to be queried from the
		 *                     database. Default false.
		 */
		$first_year = apply_filters( 'c2c_years_ago_today-first_published_year', false );

		$cache_key = 'first_published_year_' . get_current_blog_id();

		// If not provided via filter, try to get it from the cache.
		if ( false === $first_year ) {
			$first_year = wp_cache_get( $cache_key, self::$cache_group );
		}

		// If not in the cache, figure it out.
		if ( false === $first_year ) {
			// Query for the earliest published year.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- This is the best way to get this value, which is then cached.
			$first_year = $wpdb->get_var( "SELECT YEAR(MIN(post_date)) FROM $wpdb->posts WHERE post_status IN ( 'publish', 'private' )" );

			// If nothing was found, assume current year.
			if ( ! $first_year ) {
				$first_year = wp_date( 'Y' );
			}

			// Cache the year.
			wp_cache_add( $cache_key, $first_year, self::$cache_group );
		}

		return $first_year;
	}

	/**
	 * Returns the post types that should be included in the widget.
	 *
	 * @since 2.0
	 *
	 * @return string[]
	 */
	public static function get_post_types() {
		$default = array( 'post' );

		/**
		 * Filters the post types that get included in Years Ago Today widgets.
		 *
		 * @since 2.0
		 *
		 * @param string[] $post_types The included post types. Default `['post']`.
		 */
		$post_types = (array) apply_filters( 'c2c_years_ago_today-post_types', $default );

		// Verify returned post types.
		$post_types = array_filter(
			$post_types,
			static function ( $pt ) {
				return is_string( $pt ) && post_type_exists( $pt );
			}
		);

		return $post_types ?: $default;
	}

	/**
	 * Generates and returns the cache key for today's post IDs.
	 *
	 * @since 2.0
	 *
	 * @return string
	 */
	public static function get_post_ids_cache_key() {
		// Unique per-site prefix + today's date.
		return 'yat_' . get_current_blog_id() . '_' . wp_date( 'Ymd' );
	}

	/**
	 * Returns the post IDs for posts published today.
	 *
	 * @since 2.0
	 *
	 * @return int[] Array of post IDs.
	 */
	public static function query_post_ids() {
		$first_year   = (int) self::get_first_published_year();
		$current_year = (int) wp_date( 'Y' );

		// Bail if this is the site's first year.
		if ( $first_year >= $current_year ) {
			return array();
		}

		$years = range( $first_year, $current_year - 1 );

		$q = new WP_Query( array(
			'fields'         => 'ids',
			'post_status'    => array( 'publish' ),
			'post_type'      => self::get_post_types(),
			'posts_per_page' => -1,
			'date_query'     => array(
				'year'  => $years,
				'month' => wp_date( 'm' ),
				'day'   => wp_date( 'd' ),
			),
		) );

		return $q->posts;
	}

	/**
	 * Returns the query object after a years ago post query, or the posts that
	 * were found.
	 *
	 * @since 1.0
	 *
	 * @param bool $return_posts Return array of queried posts or the WP_Query
	 *                           object? True to return array posts, false to
	 *                           return WP_Query object. Default false.
	 * @return array|WP_Query    Array if return_posts is true, WP_Query if false.
	 */
	public static function get_posts( $return_posts = false ) {
		if ( false === ( $ids = get_transient( self::get_post_ids_cache_key() ) ) ) {
			$ids = self::query_post_ids();
			set_transient( self::get_post_ids_cache_key(), $ids, 15 * MINUTE_IN_SECONDS );
		}

		// Bail early if there are no posts.
		if ( ! $ids ) {
			return $return_posts ? array() : new WP_Query( array( 'post__in' => array( 0 ) ) );
		}

		$query_args = array(
			'post__in'       => $ids,
			'post_status'    => array( 'publish' ),
			'orderby'        => 'post_date',
			'order'          => 'DESC',
			'posts_per_page' => -1,
		);

		if ( $return_posts ) {
			return get_posts( $query_args );
		}

		$query_args['post_type'] = self::get_post_types();

		return new WP_Query( $query_args );
	}

	/**
	 * Enqueues the admin CSS when on the dashboard page.
	 *
	 * @since 2.0
	 */
	public static function enqueue_admin_style( $hook_suffix ) {
		// Bail if not on the dashboard page.
		if ( 'index.php' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'c2c-years-ago-today-admin',
			plugins_url( 'assets/css/admin.css', __FILE__ ),
			array(),
			self::version()
		);
	}

	/**
	 * Adds the checkbox to user profiles to allow them to opt into receiving a
	 * daily email about posts published in years past.
	 *
	 * @since 1.0
	 * @since 1.4 Added $user arg.
	 *
	 * @param WP_User $user The user whose options are being shown.
	 */
	public static function add_daily_email_optin_checkbox( $user ) {
		$current_user = wp_get_current_user();
		$is_current_user_profile_page = ( $user->ID === $current_user->ID );

		// Only show on current user's own profile, or other user profiles if current
		// user has appropriate capabilities.
		if ( ! $is_current_user_profile_page && ! current_user_can( 'edit_users' ) ) {
			return;
		}

		$checked  = checked( get_user_option( self::$option_name, $user->ID ), self::$enabled_option_value, false );
		$disabled = disabled( true, defined( 'DISABLE_WP_CRON' ) && true === DISABLE_WP_CRON, false );
		$label = $is_current_user_profile_page
			// Note: This string is mentioned verbatim in the email footer, so reflect any changes there as well.
			? __( 'Email me daily about posts published on this day in years past.', 'years-ago-today' )
			: __( 'Email this user daily about posts published on this day in years past.', 'years-ago-today' );

		echo "\t\t<table class=\"form-table\">\n";
		echo "\t\t<tr>\n";
		echo "\t\t\t" . '<th scope="row">' . esc_html__( '"Years Ago Today" email', 'years-ago-today' ) . "</th>\n";
		echo "\t\t\t<td>\n";
		echo "\t\t\t\t" . sprintf( '<label for="%s">', esc_attr( self::$option_name ) ) . "\n";
		echo "\t\t\t\t\t" . sprintf(
			'<input name="%s" type="checkbox" id="%s" value="%s" aria-describedby="years-ago-today-explainer"%s%s />',
			esc_attr( self::$option_name ),
			esc_attr( self::$option_name ),
			esc_attr( self::$enabled_option_value ),
			checked( get_user_option( self::$option_name, $user->ID ), self::$enabled_option_value, false ),
			disabled( true, defined( 'DISABLE_WP_CRON' ) && true === DISABLE_WP_CRON, false )
		) . "\n";
		echo "\t\t\t\t\t" . esc_html( $label );
		echo "\t\t\t\t</label>\n";
		echo "\t\t\t\t<p id=\"years-ago-today-explainer\" class=\"description\">";
		$is_current_user_profile_page
			? esc_html_e( 'If checked, you\'ll be sent one email a day that lists posts published on this calendar day in previous years. You can opt out at any time via this checkbox.', 'years-ago-today' )
			: esc_html_e( 'If checked, they\'ll be sent one email a day that lists posts published on this calendar day in previous years. They can opt out at any time via this checkbox on the profile.', 'years-ago-today' );
		echo "</p>\n";
		echo "\t\t\t</td>\n";
		echo "\t\t</tr>\n";
		echo "\t\t</table>\n";
	}

	/**
	 * Saves value of checkbox to allow user to opt into receiving daily emails
	 * about posts published on this day in years past.
	 *
	 * @since 1.0
	 *
	 * @param  int  $user_id The user ID.
	 * @return bool          True if the option saved successfully.
	 */
	public static function option_save( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Core already verifies nonces, but also the value is only used for a comparison.
		if ( isset( $_POST[ self::$option_name ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Core already verifies nonces, but also the value is only used for a comparison.
			$value = sanitize_text_field( wp_unslash( $_POST[ self::$option_name ] ) );

			if ( self::$enabled_option_value === $value ) {
				return update_user_option( $user_id, self::$option_name, self::$enabled_option_value );
			}
		}

		return delete_user_option( $user_id, self::$option_name );
	}

} // end c2c_YearsAgoToday

add_action( 'plugins_loaded', array( 'c2c_YearsAgoToday', 'init' ) );
register_activation_hook( __FILE__, array( 'c2c_YearsAgoToday', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'c2c_YearsAgoToday', 'deactivate' ) );
c2c_YearsAgoToday::cron_init();

endif; // end if !class_exists()
