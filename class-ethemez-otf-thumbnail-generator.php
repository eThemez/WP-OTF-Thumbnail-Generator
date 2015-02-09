<?php
	if ( ! defined( 'ABSPATH' ) ) exit;

	if ( ! class_exists( 'eThemez_OTF_Thumbnail_Generator' ) ) :
		/**
		 * eThemez OTF Thumbnail Generator Class
		 *
		 * This class handles all about images.
		 *
		 * @class		eThemez_OTF_Thumbnail_Generator
		 * @version		1.0.0
		 * @package		eThemez_OTF_Thumbnail_Generator
		 * @category	Class
		 * @author		eThemez
		 */
		class eThemez_OTF_Thumbnail_Generator {
			/**
			 * Constructor
			 *
			 * @since 1.0.0
			 * @access public
			 * @param object
			 * @return void
			 */
			public function __construct() {
				remove_all_filters( 'image_downsize' );
				add_filter( 'image_downsize', array($this, 'downsize'), 10, 3 );
			}

			/**
			 * The downsize method
			 *
			 * @since 1.0.0
			 * @access public
			 * @param boolean
			 * @param integer
			 * @param string|array
			 * @return boolean|array
			 */
			public function downsize( $out, $id, $size ) {
				list( $width, $height, $crop ) = $this->size( $size );

				$image = wp_get_attachment_metadata($id);

				if( ! wp_attachment_is_image($id) || ! $image )
					return false;

				$dim = image_resize_dimensions( $image['width'], $image['height'], $width, $height, $crop );
				list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dim;

				if( ( $width >= $image['width'] && $height >= $image['height'] )
					|| ( $width <= 0 && $height <= 0 )
				)
					return array(
						wp_get_attachment_url($id),
						$image['width'],
						$image['height'],
						false
					);

				foreach( $image['sizes'] as $image_size_name => $image_size ) {
					if( $image_size['width'] == $dst_w && $image_size['height'] == $dst_h )
						return array(
							dirname( wp_get_attachment_url($id) ) . '/' . $image_size['file'],
							$image_size['width'],
							$image_size['height'],
							true
						);
				}

				return $this->create_thumb($id, $size);
			}

			/**
			 * Get thumbnail size
			 *
			 * @since 1.0.0
			 * @access public
			 * @param string|array
			 * @return array
			 */
			private function size( $size = array() ) {
				switch( gettype($size) )
				{
					case 'array':
						list( $width, $height, $crop ) = array_pad( $size, 3, null );

						$width = intval($width);
						if( $width < 0 )
							$width = 0;

						$height = intval($height);
						if( $height < 0 )
							$height = 0;

						if( ! isset($crop) && $width > 0 && $height > 0  )
							$crop = true;
						else
							$crop = boolval($crop);

						break;
					case 'string':
						return $this->size( $this->get_sizes($size) );

						break;
					default:
						list( $width, $height, $crop ) = array(0, 0, false);

						break;
				}

				return array( $width, $height, $crop );
			}

			/**
			 * Get image sizes by size name
			 *
			 * @since 1.0.0
			 * @access public
			 * @param string
			 * @return boolean|array
			 */
			private function get_sizes( $size = '' ) {
				global $_wp_additional_image_sizes;

				if ( in_array( $size, array( 'thumbnail', 'medium', 'large' ) ) )
					return array(
						get_option( $size . '_size_w' ),
						get_option( $size . '_size_h' ),
						(bool) get_option( $size . '_crop' )
					);

				if( isset( $_wp_additional_image_sizes[$size] ) )
					return array(
						$_wp_additional_image_sizes[$size]['width'],
						$_wp_additional_image_sizes[$size]['height'],
						$_wp_additional_image_sizes[$size]['crop']
					);

				return false;
			}

			/**
			 * Create thumbnail
			 *
			 * @since 1.0.0
			 * @access public
			 * @param integer
			 * @param array
			 * @return string
			 */
			private function create_thumb( $id = null, $size = array() ) {
				list( $width, $height, $crop ) = $this->size( $size );

				$upload_dir = wp_upload_dir();
				$image = wp_get_attachment_metadata($id);

				if( ! wp_attachment_is_image($id) || ! $image )
					return false;

				$image_editor = wp_get_image_editor( $upload_dir['basedir'] . '/' . $image['file'] );

				if( is_wp_error($image_editor) )
					return false;

				$image_editor->resize($width, $height, $crop);
				$thumb = $image_editor->save();

				$image_size_name = sprintf( 'ethemez-%dx%d', $thumb['width'], $thumb['height'] );
				$image['sizes'][$image_size_name] = Array(
					'file' => $thumb['file'],
					'width' => $thumb['width'],
					'height' => $thumb['height'],
					'mime-type' => $thumb['mime-type']
				);

				wp_update_attachment_metadata($id, $image);

				return array(
					dirname( wp_get_attachment_url($id) ) . '/' . $thumb['file'],
					$thumb['width'],
					$thumb['height'],
					true
				);
			}
		}
	endif;