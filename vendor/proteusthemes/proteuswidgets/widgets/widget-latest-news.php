<?php
/**
 * Latest News Widget
 */

if ( ! class_exists( 'PW_Latest_News' ) ) {
	class PW_Latest_News extends PW_Widget {

		private $max_post_number = 10;
		private $current_widget_id;

		// Basic widget settings
		function widget_id_base() { return 'latest_news'; }
		function widget_name() { return esc_html__( 'Latest News', 'proteuswidgets' ); }
		function widget_description() { return esc_html__( 'Latest news widget for Page Builder.', 'proteuswidgets' ); }
		function widget_class() { return 'widget-latest-news'; }

		public function __construct() {
			parent::__construct();
		}

		/**
		 * Front-end display of widget.
		 *
		 * @see WP_Widget::widget()
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			$type      = ! empty( $instance['type'] ) ? $instance['type'] : '';
			$from      = ! empty( $instance['from'] ) ? $instance['from'] : '';
			$to        = ! empty( $instance['to'] ) ? $instance['to'] : '';
			$more_news = ! empty( $instance['more_news'] ) ? $instance['more_news'] : '';

			// prepare data for mustache template
			$instance['more_news_on']      = ! empty( $instance['more_news'] ) ? true : false;
			$instance['link_to_more_news'] = get_permalink( get_option( 'page_for_posts' ) );

			// Get/set cache data just once for multiple widgets
			$recent_posts_data = PW_Functions::get_cached_data( 'legalpress_recent_posts', $this->max_post_number );

			// Array with posts to display
			$recent_posts = array();

			switch ( $type ) {
				case 'block':
					$instance['block'] = true;
					if ( $from <= count( $recent_posts_data ) ) {
						$recent_posts[] = $recent_posts_data[ $from - 1 ];
					}
					break;

				case 'inline':
					$instance['block'] = false;
					$recent_posts = array_intersect_key( $recent_posts_data, array_flip( range( $from - 1, $to - 1 ) ) );
					break;
			}

			$text = array(
				'by'        => esc_html__( 'By', 'proteuswidgets' ),
				'more_news' => esc_html__( 'More news', 'proteuswidgets' ),
			);

			// Mustache widget-latest-news template rendering
			echo $this->mustache->render( apply_filters( 'pw/widget_latest_news_view', 'widget-latest-news' ), array(
				'args'     => $args,
				'instance' => $instance,
				'posts'    => array_values( (array) $recent_posts ),
				'text'     => $text,
			));
		}

		/**
		 * Sanitize widget form values as they are saved.
		 *
		 * @param array $new_instance The new options
		 * @param array $old_instance The previous options
		 */
		public function update( $new_instance, $old_instance ) {
			$instance = array();

			$instance['type'] = sanitize_key( $new_instance['type'] );
			$instance['from'] = sanitize_text_field( $new_instance['from'] );
			$instance['to']   = sanitize_text_field( $new_instance['to'] );

			if ( ! empty( $new_instance['more_news'] ) ) {
				$instance['more_news'] = sanitize_text_field( $new_instance['more_news'] );
			}
			else {
				$instance['more_news'] = '';
			}

			// Bound from and to between 1 and max_post_number
			$instance['from'] = PW_Functions::bound( $instance['from'], 1, $this->max_post_number );
			$instance['to']   = PW_Functions::bound( $instance['to'], 1, $this->max_post_number );

			// to can't be lower than from
			if ( $instance['from'] > $instance['to'] ) {
				$instance['to'] = $instance['from'];
			}

			return $instance;
		}

		/**
		 * Back-end widget form.
		 *
		 * @param array $instance The widget options
		 */
		public function form( $instance ) {
			$type      = ! empty( $instance['type'] ) ? $instance['type'] : '';
			$from      = ! empty( $instance['from'] ) ? $instance['from'] : '';
			$to        = ! empty( $instance['to'] ) ? $instance['to'] : '';
			$more_news = ! empty( $instance['more_news'] ) ? $instance['more_news'] : '';

			// Page Builder fix for widget id used in Backbone and in the surrounding div below
			if ( 'temp' === $this->id ) {
				$this->current_widget_id = $this->number;
			}
			else {
				$this->current_widget_id = $this->id;
			}

			?>

			<div id="<?php echo esc_attr( $this->current_widget_id ); ?>">
				<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>"><?php _ex( 'Display type:', 'backend', 'legalpress-pt' ); ?></label>
					<select id="<?php echo esc_attr( $this->get_field_id( 'type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'type' ) ); ?>" class="latest-news-select-type">
						<option value="block" <?php selected( $type, 'block' ); ?>><?php _ex( 'Box (one post)', 'backend', 'legalpress-pt' ); ?></option>
						<option value="inline" <?php selected( $type, 'inline' ); ?>><?php _ex( 'Inline (multiple posts)', 'backend', 'legalpress-pt' ); ?></option>
					</select>
				</p>

				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'from' ) ); ?>"><?php _ex( 'Post order number from:', 'backend', 'legalpress-pt' ); ?></label>
					<input id="<?php echo esc_attr( $this->get_field_id( 'from' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'from' ) ); ?>" type="number" min="1" max="<?php echo $this->max_post_number; ?>" value="<?php echo esc_attr( $from ); ?>" />
					<span class="latest-news-to-fields-group" id="<?php echo esc_attr( $this->get_field_id( 'to' ) ); ?>-fields-group">
					<label for="<?php echo esc_attr( $this->get_field_id( 'to' ) ); ?>"><?php _ex( 'To:', 'backend', 'legalpress-pt' ); ?></label>
					<input id="<?php echo esc_attr( $this->get_field_id( 'to' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'to' ) ); ?>" type="number" min="1" max="<?php echo esc_attr( $this->max_post_number ); ?>" value="<?php echo esc_attr( $to ); ?>" />
				</span>
				</p>

				<p>
					<label for="<?php echo esc_attr( $this->get_field_id( 'more_news' ) ); ?>"><?php _ex( 'More news link:', 'backend', 'legalpress-pt' ); ?></label>
					<input id="<?php echo esc_attr( $this->get_field_id( 'more_news' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'more_news' ) ); ?>" type="checkbox" <?php checked( $more_news, 'on' ); ?> />
				</p>
			</div>

			<script type="text/javascript">
				(function( $ ) {

					var toFieldsGroup = '<<?php echo esc_js( $this->get_field_id( "to" ) ); ?>>'.slice( 1, -1 );
					var selectedType  = '<<?php echo esc_js( $this->get_field_id( "type" ) ); ?>>'.slice( 1, -1 );
					var widgetId      = '<<?php echo esc_js( $this->current_widget_id ); ?>>'.slice( 1, -1 );

					toFieldsGroup = '#' + toFieldsGroup + '-fields-group';
					selectedType  = '#' + selectedType;


					var LatestNewsView = Backbone.View.extend({
						events: {
							'change .latest-news-select-type': 'toggle',
						},

						initialize: function( params ){
							this.toFieldsGroup = params.toFieldsGroup;
							this.selectedType  = params.selectedType;

							if ( 'block' == $(this.selectedType).val() ) {
								$(this.toFieldsGroup).hide();
								$(this.toFieldsGroup).siblings('label').html("<?php _ex( 'Post order number:', 'backend', 'legalpress-pt' ); ?>");
							}
						},

						toggle: function(event){
							if ( 'block' == event.target.value ) {
								$(this.toFieldsGroup).siblings('label').html("<?php _ex( 'Post order number:', 'backend', 'legalpress-pt' ); ?>");
								$(this.toFieldsGroup).hide();
							}
							else {
								$(this.toFieldsGroup).siblings('label').html("<?php _ex( 'Post order number from:', 'backend', 'legalpress-pt' ); ?>");
								$(this.toFieldsGroup).show();
							}
						},
					});

					new LatestNewsView( { el: $('#' + widgetId), toFieldsGroup: toFieldsGroup, selectedType: selectedType, } );

				})( jQuery );
			</script>

			<?php
		}

	}
	register_widget( 'PW_Latest_News' );
}