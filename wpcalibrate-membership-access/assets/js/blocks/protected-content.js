/**
 * Gutenberg Protected Content Block Registration.
 */
(function(wp) {
	'use strict';

	const { registerBlockType } = wp.blocks;
	const { InnerBlocks, InspectorControls } = wp.blockEditor || wp.editor;
	const { PanelBody, ToggleControl, TextControl } = wp.components;
	const { __ } = wp.i18n;
	const { el } = wp.element;

	registerBlockType('wpcalibrate/protected-content', {
		title: __('Protected Membership Content', 'wpcalibrate-membership-access'),
		description: __('Restrict inner blocks to members with valid active memberships.', 'wpcalibrate-membership-access'),
		icon: 'lock',
		category: 'widgets',
		attributes: {
			allowAnyMember: {
				type: 'boolean',
				default: true,
			},
			allowLoggedIn: {
				type: 'boolean',
				default: false,
			},
			allowedPlans: {
				type: 'array',
				default: [],
			},
			fallbackMessage: {
				type: 'string',
				default: __('This content is restricted to members only.', 'wpcalibrate-membership-access'),
			},
		},

		edit: function(props) {
			const { attributes, setAttributes } = props;

			return [
				el(
					InspectorControls,
					{ key: 'inspector' },
					el(
						PanelBody,
						{ title: __('Membership Protection Settings', 'wpcalibrate-membership-access'), initialOpen: true },
						el(ToggleControl, {
							label: __('Allow Any Active Membership', 'wpcalibrate-membership-access'),
							checked: attributes.allowAnyMember,
							onChange: function(val) {
								setAttributes({ allowAnyMember: val });
							},
						}),
						el(ToggleControl, {
							label: __('Allow Any Logged-In User', 'wpcalibrate-membership-access'),
							checked: attributes.allowLoggedIn,
							onChange: function(val) {
								setAttributes({ allowLoggedIn: val });
							},
						}),
						el(TextControl, {
							label: __('Fallback Notice Message', 'wpcalibrate-membership-access'),
							value: attributes.fallbackMessage,
							onChange: function(val) {
								setAttributes({ fallbackMessage: val });
							},
						})
					)
				),
				el(
					'div',
					{
						key: 'editor-wrap',
						style: {
							border: '2px dashed #0284c7',
							padding: '16px',
							borderRadius: '6px',
							background: '#f0f9ff',
						},
					},
					el(
						'div',
						{
							style: {
								display: 'flex',
								alignItems: 'center',
								gap: '6px',
								marginBottom: '12px',
								fontWeight: '600',
								color: '#0369a1',
								fontSize: '12px',
								textTransform: 'uppercase',
							},
						},
						'🔒 ' + __('WPCalibrate Protected Content Container', 'wpcalibrate-membership-access')
					),
					el(InnerBlocks, {})
				),
			];
		},

		save: function() {
			// Rendered server-side by ProtectedContentBlock.php
			return el(InnerBlocks.Content, {});
		},
	});
})(window.wp);
