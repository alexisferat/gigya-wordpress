<?php

/**
 * Adds ScreenSetWidget widget
 */
class GigyaScreenSet_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		$args = array(
			'description' => __( 'SAP Customer Data Cloud Screen-Set' )
		);
		parent::__construct( 'gigya_screenset', __( 'SAP CDC ScreenSet' ), $args );
	}

	protected function setWidgetMachineName( $widget_id ) {
		$pattern = '/[^a-zA-Z0-9]/';

		return trim( preg_replace( $pattern, '', (string) $widget_id ) );
	}

	/**
	 * Front-end display of widget.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		if ( ! empty( $instance['container_id'] ) and ! empty( $instance['title'] ) ) {
			wp_register_script( $args['widget_id'], GIGYA__PLUGIN_URL . 'features/raas/gigya_custom_screenset.js' );

			$widget_machine_name = $this->setWidgetMachineName( $args['widget_id'] );

			echo '<div id="' . $instance['container_id'] . '" class="gigya-screenset-widget-outer-div" data-machine-name="' . $widget_machine_name . '">';

			if ( ! empty( $instance['type'] ) and $instance['type'] == 'popup' ) {
				if ( empty( $instance['link_id'] ) ) {
					$instance['link_id'] = 'gigya-screenset-popup-' . rand( 1000, 9999 );
				}

				echo '<a id="' . $instance['link_id'] . '" class="' . ( ! empty( $instance['link_class'] ) ? $instance['link_class'] : '' ) . '" href="#">' . $instance['title'] . '</a>';
			}

			echo '</div>';

			$custom_screen_sets = get_option( GIGYA__SETTINGS_SCREENSETS )['custom_screen_sets'];
			foreach ( $custom_screen_sets as $screen_set ) {
				if ( $screen_set['id'] == $instance['screenset_id'] ) {
					$instance['screenset_id']        = $screen_set['desktop'];
					$instance['mobile_screenset_id'] = ( $instance['screenset_id'] !== 'Use Desktop Screen-Set' ) ? $screen_set['mobile'] : $screen_set['desktop'];
					$instance['is_sync_data']        = ( ! empty( $screen_set['is_sync'] ) );
				}
			}

			wp_localize_script( $args['widget_id'], '_gig_' . $widget_machine_name, $instance );
			wp_enqueue_script( $args['widget_id'] );
		}
	}

	public function form( $instance ) {
		$form                          = array();
		$select_attrs                  = array();
		$select_attrs['data-required'] = 'empty-selection';
		$select_markup                 = null;
		$custom_screen_sets            = get_option( GIGYA__SETTINGS_SCREENSETS )['custom_screen_sets'];
		$custom_screen_sets            = array_combine( array_column( $custom_screen_sets, 'id' ), array_column( $custom_screen_sets, 'desktop' ) );

		if ( empty( esc_attr( _gigParam( $instance, 'screenset_id', '' ) ) ) ) {
			$custom_screen_sets = array_merge( array(
				array(
					'value' => '',
					'attrs' => array(
						'disabled' => 'disabled',
						'style'    => 'display: none;',
					)
				)
			), $custom_screen_sets );
		} else {
			if ( ! array_key_exists( esc_attr( _gigParam( $instance, 'screenset_id', '' ) ), $custom_screen_sets ) ) {
				$select_attrs['class'] = 'gigya-wp-field-error';
				$select_markup         = '<div id="setting-error-api_validate" class="error notice settings-error notice is-dismissible style" style = "border-left-color : #dc3232 "> 
							<p>
							<strong>' . __( esc_attr( _gigParam( $instance, 'screenset_id', '' ) ) . '  Screen-Set found in the widgets below has been removed by your administrator, and might not work on your website. Please check your configuration or contact your administrator.' ) . ' </strong>
							</p>
							<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
						</div>' . '<h6 id="setting-error-api_validate" class="error notice settings-error notice is-dismissible style" style = "border-left-color : #dc3232 "> 
							<p>
							<strong>' . __( 'Screen-Set not defined' ) . ' </strong>
							</p>
							<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
						</h6>';
				$custom_screen_sets    = array_merge( array(
					array(
						'value' => esc_attr( _gigParam( $instance, 'screenset_id', '' ) ),
						'attrs' => array(
							'class' => 'invalid_gigya_Screen-Set_option',
						)
					)
				), $custom_screen_sets );
			}
		}

		$form[ $this->get_field_id( 'title' ) ] = array(
			'type'     => 'text',
			'name'     => $this->get_field_name( 'title' ),
			'value'    => ( empty( esc_attr( _gigParam( $instance, 'title', '' ) ) ) ) ? '' : esc_attr( _gigParam( $instance, 'title', '' ) ),
			'label'    => __( 'Title' ),
			'class'    => 'size',
			'required' => true,
		);


		$form[ $this->get_field_id( 'screenset_id' ) ] = array(
			'type'     => 'select',
			'name'     => $this->get_field_name( 'screenset_id' ),
			'label'    => __( 'Screen-Set ID' ),
			'options'  => $custom_screen_sets,
			'value'    => esc_attr( _gigParam( $instance, 'screenset_id', '' ) ),
			'required' => true,
			'class'    => 'size',
			'attrs'    => $select_attrs,
			'markup'   => $select_markup,
		);


		$form[ $this->get_field_id( 'container_id' ) ] = array(
			'type'     => 'text',
			'name'     => $this->get_field_name( 'container_id' ),
			'value'    => ( empty( esc_attr( _gigParam( $instance, 'container_id', '' ) ) ) ) ? '' : esc_attr( _gigParam( $instance, 'container_id', '' ) ),
			'label'    => __( 'Container ID' ),
			'required' => true,
			'class'    => 'size',
		);

		$form[ $this->get_field_id( 'type' ) ] = array(
			'type'     => 'select',
			'name'     => $this->get_field_name( 'type' ),
			'label'    => __( 'Type' ),
			'options'  => array(
				'embed' => __( 'Embed' ),
				'popup' => __( 'Popup' ),
			),
			'class'    => 'size',
			'value'    => empty( esc_attr( _gigParam( $instance, 'type', '' ) ) ) ? '0' : esc_attr( _gigParam( $instance, 'type', '' ) ),
			'required' => true,
		);

		echo _gigya_form_render( $form );
	}

	/**
	 * @param array $input_values
	 * @param array $db_values
	 *
	 * @return array
	 */
	public function update( $input_values, $db_values ) {
		$valid = true;

		$instance = array();
		if ( ! empty( $input_values ) and ! empty( $db_values ) ) {
			$instance = array_merge( $db_values, $input_values );
		} elseif ( ! empty( $input_values ) ) {
			$instance = $input_values;
		} elseif ( ! empty( $db_values ) ) /* If all values were reset it will just return to the previous state without further validation */ {
			return $db_values;
		}

		if ( empty( $instance['title'] ) or empty( $instance['screenset_id'] ) or empty( $instance['container_id'] ) ) {
			$valid = false;
		}

		if ( ! $valid ) {
			return ( empty( $db_values ) ) ? array() : $db_values;
		}

		return $instance;
	}
}